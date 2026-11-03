<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use InvalidArgumentException;
use PDOException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerDAO;

final class WalletService
{
    private WalletDAO $walletDao;
    private ?LedgerDAO $ledgerDao;
    private ExchangeRateService $rateService;

    public function __construct(
        ?WalletDAO $walletDao = null,
        ?LedgerDAO $ledgerDao = null,
        ?ExchangeRateService $rateService = null
    )
    {
        $this->walletDao = $walletDao ?? new WalletDAO();
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO($this->walletDao->getPdo());
        $this->rateService = $rateService ?? new ExchangeRateService($this->walletDao->getPdo());
    }

    /** @return array<int, array<string, mixed>> */
    public function getWallets(int $userId, bool $includeArchived = false): array
    {
        return $this->walletDao->findAllByUser($userId, $includeArchived);
    }

    /** @return array<string, mixed> */
    public function getWallet(int $walletId, int $userId): array
    {
        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        return $wallet;
    }

    /** @return array<int, array<string, mixed>> */
    public function getWalletHistory(int $walletId, int $userId): array
    {
        $this->getWallet($walletId, $userId);
        return $this->walletDao->listHistory($walletId, $userId);
    }

    /**
     * Create a new wallet and optionally post an opening balance (BR01, BR02).
     *
     * @param array{
     *     name: string,
     *     walletType: string,
     *     currency?: string,
     *     initialBalance?: float|string|int
     * } $dto
     * @return array<string, mixed>
     */
    public function createWallet(int $userId, array $dto, ?string $idempotencyKey = null): array
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) ($dto['name'] ?? ''))) ?? '';
        $walletType = strtoupper(trim((string) ($dto['walletType'] ?? $dto['type'] ?? '')));
        $currency = strtoupper(trim((string) ($dto['currency'] ?? 'VND')));
        $initialBalanceInput = $dto['initialBalance'] ?? $dto['openingBalance'] ?? '0';

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Tên ví phải từ 1 đến 120 ký tự.');
        }

        $validTypes = ['CASH', 'BANK', 'E_WALLET', 'SAVINGS', 'CREDIT'];
        if (!in_array($walletType, $validTypes, true)) {
            throw new InvalidArgumentException('Loại ví không hợp lệ: ' . implode(', ', $validTypes));
        }

        if (!in_array($currency, ['VND', 'USD', 'EUR'], true)) {
            throw new InvalidArgumentException('Loại tiền tệ chưa được hỗ trợ.');
        }
        $initialBalance = $this->normalizeAmount($initialBalanceInput, $currency, 'initialBalance');
        $key = $this->normalizeIdempotencyKey($idempotencyKey);
        $payload = compact('name', 'walletType', 'currency', 'initialBalance');

        $pdo = $this->walletDao->getPdo();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }

        try {
            $cached = $this->replayIdempotentRequest($userId, $key, $payload);
            if ($cached !== null) {
                if ($ownsTx) {
                    $pdo->commit();
                }
                return $cached;
            }
            if ($this->walletDao->nameExists($userId, $name)) {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Hãy dùng tên khác; tên ví đang hoạt động hoặc đã lưu trữ không được trùng.',
                ]);
            }
            $baseCurrency = $this->walletDao->getUserBaseCurrency($userId);
            if ($baseCurrency === null) {
                throw new InvalidArgumentException('Không tìm thấy tài khoản người dùng đang hoạt động.');
            }
            $openingRate = '1.0000000000';
            if (bccomp($initialBalance, '0', 8) > 0 && $currency !== $baseCurrency) {
                try {
                    $openingRate = $this->rateService->getSnapshotRate($currency, $baseCurrency);
                } catch (\RuntimeException) {
                    throw new InvalidArgumentException(
                        "Chưa có tỷ giá hợp lệ từ {$currency} sang {$baseCurrency}. Hãy cập nhật tỷ giá Vietcombank trước."
                    );
                }
            }

            $storedOpeningBalance = $walletType === 'CREDIT'
                ? bcmul($initialBalance, '-1', 8)
                : $initialBalance;
            $walletId = $this->walletDao->create(
                $userId,
                $name,
                $walletType,
                $currency,
                $storedOpeningBalance
            );

            $openingTransactionId = null;
            if (bccomp($initialBalance, '0', 8) > 0) {
                $amountBase = $this->rateService->convertToBase($initialBalance, $openingRate);
                $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);
                $equityAccountId = $this->walletDao->getOrCreateOpeningEquityAccountId($userId);

                $txId = $this->ledgerDao->createTransaction([
                    'userId' => $userId,
                    'categoryId' => null,
                    'type' => 'OTHER', // Equity opening, not user income.
                    'status' => 'POSTED',
                    'description' => 'Số dư mở sổ ví: ' . $name,
                    'currency' => $currency,
                    'amount' => $initialBalance,
                    'fxRateSnapshot' => $openingRate,
                    'occurredOn' => date('Y-m-d'),
                    'postedAt' => date('Y-m-d H:i:s'),
                    'originalTransactionId' => null,
                ]);
                $openingTransactionId = $txId;

                $isCreditWallet = $walletType === 'CREDIT';
                $this->ledgerDao->createLedgerEntry([
                    'transactionId' => $txId,
                    'ledgerAccountId' => $isCreditWallet ? $equityAccountId : $walletAccountId,
                    'entrySide' => 'DEBIT',
                    'amountOriginal' => $initialBalance,
                    'currency' => $currency,
                    'amountBase' => $amountBase,
                    'fxRateSnapshot' => $openingRate,
                ]);

                $this->ledgerDao->createLedgerEntry([
                    'transactionId' => $txId,
                    'ledgerAccountId' => $isCreditWallet ? $walletAccountId : $equityAccountId,
                    'entrySide' => 'CREDIT',
                    'amountOriginal' => $initialBalance,
                    'currency' => $currency,
                    'amountBase' => $amountBase,
                    'fxRateSnapshot' => $openingRate,
                ]);
            }

            $response = $this->getWallet($walletId, $userId);
            if ($key !== null) {
                $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
                $this->ledgerDao->saveIdempotency(
                    $userId,
                    $key,
                    $hash,
                    $openingTransactionId,
                    201,
                    json_encode($response, JSON_THROW_ON_ERROR)
                );
            }

            if ($ownsTx) {
                $pdo->commit();
            }

            return $response;
        } catch (Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $driverCode = $e instanceof PDOException ? (int) ($e->errorInfo[1] ?? 0) : 0;
            $uniqueViolation = $e instanceof PDOException && (
                $e->getCode() === '23505'
                || ($e->getCode() === '23000' && ($driverCode === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')))
            );
            if ($uniqueViolation) {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Hãy dùng tên khác; tên ví đang hoạt động hoặc đã lưu trữ không được trùng.',
                ]);
            }
            throw $e;
        }
    }

    public function updateWallet(int $walletId, int $userId, array $dto): array
    {
        $wallet = $this->getWallet($walletId, $userId);
        $name = trim((string) ($dto['name'] ?? $wallet['name']));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Tên ví phải từ 1 đến 120 ký tự.');
        }
        if ($this->walletDao->nameExists($userId, $name, $walletId)) {
            throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                'name' => 'Hãy dùng tên khác; tên ví đang hoạt động hoặc đã lưu trữ không được trùng.',
            ]);
        }

        $this->walletDao->update($walletId, $userId, $name);
        return $this->getWallet($walletId, $userId);
    }

    /**
     * Record an explicit balance correction as a balanced financial transaction.
     *
     * @param array{direction?: mixed, amount?: mixed, note?: mixed} $dto
     * @return array{wallet: array<string, mixed>, transactionId: int}
     */
    public function adjustWallet(
        int $walletId,
        int $userId,
        array $dto,
        ?string $idempotencyKey = null
    ): array {
        $direction = strtoupper(trim((string) ($dto['direction'] ?? '')));
        if (!in_array($direction, ['INCREASE', 'DECREASE'], true)) {
            throw new ApiException('VALIDATION_ERROR', 'Chiều điều chỉnh số dư không hợp lệ.', 422, [
                'direction' => 'Chọn thêm tiền hoặc bớt tiền.',
            ]);
        }

        $note = preg_replace('/\s+/u', ' ', trim((string) ($dto['note'] ?? ''))) ?? '';
        if (mb_strlen($note, 'UTF-8') > 200) {
            throw new ApiException('VALIDATION_ERROR', 'Nội dung điều chỉnh quá dài.', 422, [
                'note' => 'Nội dung không vượt quá 200 ký tự.',
            ]);
        }

        $key = $this->normalizeIdempotencyKey($idempotencyKey);
        $pdo = $this->walletDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $wallet = $this->walletDao->findByIdAndUser($walletId, $userId, true);
            if ($wallet === null || (string) $wallet['status'] !== 'ACTIVE') {
                throw new ApiException('NOT_FOUND', 'Ví đang hoạt động không tồn tại hoặc không thuộc quyền sở hữu.', 404);
            }
            if ((string) $wallet['wallet_type'] === 'CREDIT') {
                throw new ApiException(
                    'CREDIT_ADJUSTMENT_NOT_ALLOWED',
                    'Thẻ tín dụng chỉ thay đổi qua giao dịch chi tiêu hoặc chuyển tiền thanh toán thẻ.',
                    422
                );
            }

            $currency = strtoupper((string) $wallet['currency']);
            $amount = $this->normalizeAmount($dto['amount'] ?? null, $currency, 'amount');
            if (bccomp($amount, '0', 8) !== 1) {
                throw new ApiException('VALIDATION_ERROR', 'Số tiền điều chỉnh phải lớn hơn 0.', 422, [
                    'amount' => 'Nhập số tiền lớn hơn 0.',
                ]);
            }

            $payload = compact('walletId', 'direction', 'amount', 'note');
            $cached = $this->replayIdempotentRequest($userId, $key, $payload);
            if ($cached !== null) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return $cached;
            }

            $currentBalance = (string) $wallet['cached_balance'];
            $newBalance = $direction === 'INCREASE'
                ? bcadd($currentBalance, $amount, 8)
                : bcsub($currentBalance, $amount, 8);
            if ((string) $wallet['wallet_type'] !== 'CREDIT' && bccomp($newBalance, '0', 8) === -1) {
                throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư ví không đủ để bớt số tiền này.', 422, [
                    'amount' => 'Số tiền bớt không được vượt quá số dư hiện tại.',
                ]);
            }

            $baseCurrency = $this->walletDao->getUserBaseCurrency($userId);
            if ($baseCurrency === null) {
                throw new ApiException('USER_NOT_FOUND', 'Không tìm thấy tài khoản đang hoạt động.', 404);
            }
            try {
                $rate = $currency === $baseCurrency
                    ? '1.0000000000'
                    : $this->rateService->getSnapshotRate($currency, $baseCurrency);
            } catch (\RuntimeException $error) {
                throw new ApiException('EXCHANGE_RATE_UNAVAILABLE', $error->getMessage(), 422, [
                    'amount' => 'Hãy cập nhật tỷ giá Vietcombank trước khi điều chỉnh ví ngoại tệ.',
                ]);
            }
            $amountBase = $this->rateService->convertToBase($amount, $rate);

            $transactionId = $this->ledgerDao->createTransaction([
                'userId' => $userId,
                'categoryId' => null,
                'type' => 'OTHER',
                'status' => 'POSTED',
                'description' => $note !== '' ? $note : ($direction === 'INCREASE'
                    ? 'Điều chỉnh tăng số dư ví'
                    : 'Điều chỉnh giảm số dư ví'),
                'currency' => $currency,
                'amount' => $amount,
                'fxRateSnapshot' => $rate,
                'occurredOn' => date('Y-m-d'),
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);

            $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);
            $adjustmentAccountId = $this->walletDao->getOrCreateBalanceAdjustmentAccountId($userId);
            $entry = [
                'transactionId' => $transactionId,
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $rate,
            ];
            $this->ledgerDao->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $walletAccountId : $adjustmentAccountId,
                'entrySide' => 'DEBIT',
            ]);
            $this->ledgerDao->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $adjustmentAccountId : $walletAccountId,
                'entrySide' => 'CREDIT',
            ]);

            if (!$this->walletDao->setBalance($walletId, $userId, $newBalance)) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }

            $response = [
                'wallet' => $this->getWallet($walletId, $userId),
                'transactionId' => $transactionId,
            ];
            if ($key !== null) {
                $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
                $this->ledgerDao->saveIdempotency(
                    $userId,
                    $key,
                    $hash,
                    $transactionId,
                    201,
                    json_encode($response, JSON_THROW_ON_ERROR)
                );
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $response;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public function archiveWallet(int $walletId, int $userId): bool
    {
        $wallet = $this->getWallet($walletId, $userId);
        if ($wallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Ví đã được lưu trữ.');
        }
        $pdo = $this->walletDao->getPdo();
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) $pdo->beginTransaction();
        try {
            // Lock the wallet and verify the balance within the same transaction.
            $locked = $this->walletDao->findByIdAndUser($walletId, $userId, true);
            if (!$locked || bccomp((string) $locked['cached_balance'], '0', 8) !== 0) {
                throw new InvalidArgumentException('Chỉ lưu trữ được ví có số dư bằng 0.');
            }
            if (bccomp((string) ($locked['earmarked_balance'] ?? '0'), '0', 8) !== 0) {
                throw new InvalidArgumentException('Ví vẫn còn số tiền đang được phân bổ cho mục tiêu.');
            }
            $active = $pdo->prepare("SELECT COUNT(*) FROM recurring_schedules WHERE user_id = ? AND wallet_id = ? AND status = 'ACTIVE'");
            $active->execute([$userId, $walletId]);
            if ((int) $active->fetchColumn() > 0) {
                throw new InvalidArgumentException('Ví còn giao dịch định kỳ đang hoạt động; hãy tạm dừng trước.');
            }
            $result = $this->walletDao->archive($walletId, $userId);
            if (!$result) {
                throw new ApiException('WALLET_ARCHIVE_CONFLICT', 'Trạng thái ví đã thay đổi. Vui lòng tải lại trang.', 409);
            }
            if ($ownTransaction) $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteWallet(int $walletId, int $userId): bool
    {
        $pdo = $this->walletDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $wallet = $this->walletDao->findByIdAndUser($walletId, $userId, true);
            if ($wallet === null) {
                throw new ApiException('NOT_FOUND', 'Ví không tồn tại hoặc không thuộc quyền sở hữu.', 404);
            }
            if ((string) $wallet['status'] !== 'ARCHIVED') {
                throw new ApiException(
                    'WALLET_MUST_BE_ARCHIVED',
                    'Phải lưu trữ ví trước khi xóa vĩnh viễn.',
                    422
                );
            }
            if (bccomp((string) $wallet['cached_balance'], '0', 8) !== 0
                || bccomp((string) ($wallet['earmarked_balance'] ?? '0'), '0', 8) !== 0) {
                throw new ApiException(
                    'WALLET_BALANCE_NOT_ZERO',
                    'Không thể xóa ví vẫn còn số dư hoặc tiền đang phân bổ.',
                    422
                );
            }

            $references = $this->walletDao->getReferenceCounts($walletId);
            if ($references !== []) {
                $details = implode(', ', array_map(
                    static fn(string $label, int $count): string => "{$count} {$label}",
                    array_keys($references),
                    array_values($references)
                ));
                throw new ApiException(
                    'WALLET_HISTORY_REQUIRED',
                    "Không thể xóa vĩnh viễn vì ví đang được dùng trong {$details}. Ví sẽ được giữ ở trạng thái lưu trữ để bảo toàn lịch sử.",
                    409
                );
            }
            $this->walletDao->deleteCreationBookkeeping($walletId, $userId);
            if (!$this->walletDao->delete($walletId, $userId)) {
                throw new ApiException('WALLET_DELETE_CONFLICT', 'Trạng thái ví đã thay đổi. Vui lòng tải lại trang.', 409);
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private function normalizeAmount(mixed $value, string $currency, string $field): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($currency === 'VND') {
            if (preg_match('/^\d{1,16}$/D', $raw)) {
                return ltrim($raw, '0') ?: '0';
            }
            $groupedByDot = preg_match('/^\d{1,3}(?:\.\d{3})+$/D', $raw) === 1;
            $groupedByComma = preg_match('/^\d{1,3}(?:,\d{3})+$/D', $raw) === 1;
            if (!$groupedByDot && !$groupedByComma) {
                throw new ApiException('VALIDATION_ERROR', 'Số tiền VND không hợp lệ.', 422, [
                    $field => 'Nhập số nguyên, ví dụ 1250000, 1.250.000 hoặc 1,250,000.',
                ]);
            }
            $normalized = str_replace($groupedByDot ? '.' : ',', '', $raw);
            if (strlen($normalized) > 16) {
                throw new ApiException('VALIDATION_ERROR', 'Số tiền vượt quá giới hạn.', 422, [
                    $field => 'Phần nguyên không được vượt quá 16 chữ số.',
                ]);
            }
            return ltrim($normalized, '0') ?: '0';
        }

        if (!in_array($currency, ['USD', 'EUR'], true)
            || preg_match('/^(\d{1,16})(?:[.,](\d{1,2}))?$/D', $raw, $parts) !== 1) {
            throw new ApiException('VALIDATION_ERROR', 'Số tiền ngoại tệ không hợp lệ.', 422, [
                $field => 'Nhập tối đa 2 số thập phân, ví dụ 123.45 hoặc 123,45; không dùng dấu phân cách hàng nghìn.',
            ]);
        }
        $whole = ltrim($parts[1], '0') ?: '0';
        return $whole . '.' . str_pad($parts[2] ?? '', 2, '0');
    }

    private function normalizeIdempotencyKey(?string $value): ?string
    {
        $key = trim((string) $value);
        if ($key === '') {
            return null;
        }
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $key)) {
            throw new ApiException('INVALID_IDEMPOTENCY_KEY', 'Idempotency-Key không hợp lệ.', 400);
        }
        return strtolower($key);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed>|null */
    private function replayIdempotentRequest(int $userId, ?string $key, array $payload): ?array
    {
        if ($key === null) {
            return null;
        }
        $cached = $this->ledgerDao->findIdempotency($userId, $key);
        if ($cached === null) {
            return null;
        }
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        if (!hash_equals((string) $cached['request_hash'], $hash)) {
            throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa yêu cầu đã được dùng với dữ liệu khác.', 409);
        }
        $response = json_decode((string) $cached['response_json'], true, 32, JSON_THROW_ON_ERROR);
        return is_array($response) ? $response : null;
    }
}
