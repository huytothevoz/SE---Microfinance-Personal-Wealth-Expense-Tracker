<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use PDOException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerDAO;

final class WalletService
{
    private const TYPES = ['CASH', 'BANK', 'E_WALLET', 'SAVINGS', 'CREDIT'];
    private const CURRENCIES = ['VND', 'USD', 'EUR'];

    private PDO $pdo;
    private WalletDAO $wallets;
    private LedgerDAO $ledger;
    private ExchangeRateService $rates;

    public function __construct(
        ?WalletDAO $wallets = null,
        ?LedgerDAO $ledger = null,
        ?ExchangeRateService $rates = null
    ) {
        $this->wallets = $wallets ?? new WalletDAO();
        $this->pdo = $this->wallets->getPdo();
        $this->ledger = $ledger ?? new LedgerDAO($this->pdo);
        $this->rates = $rates ?? new ExchangeRateService($this->pdo);
    }

    /** @return array<string, mixed> */
    public function listWallets(int $userId, string $status = 'ALL'): array
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, ['ACTIVE', 'ARCHIVED', 'ALL'], true)) {
            throw new ApiException('VALIDATION_ERROR', 'Trạng thái danh sách ví không hợp lệ.', 422, [
                'status' => 'Chỉ chấp nhận active, archived hoặc all.',
            ]);
        }

        $baseCurrency = $this->requireBaseCurrency($userId);
        $rows = $this->wallets->listByUser($userId, 'ALL');
        $items = [];
        $activeCount = 0;
        $archivedCount = 0;
        $unconverted = false;
        $total = '0.00000000';

        foreach ($rows as $row) {
            $wallet = $this->presentWallet($row, $baseCurrency);
            if ($status === 'ALL' || $row['status'] === $status) {
                $items[] = $wallet;
            }
            if ($row['status'] === 'ACTIVE') {
                $activeCount++;
                if ($wallet['baseBalance'] === null && bccomp((string) $row['cached_balance'], '0', 8) !== 0) {
                    $unconverted = true;
                } elseif ($wallet['baseBalance'] !== null) {
                    $total = bcadd($total, (string) $wallet['baseBalance'], 8);
                }
            } elseif ($row['status'] === 'ARCHIVED') {
                $archivedCount++;
            }
        }

        return [
            'wallets' => $items,
            'summary' => [
                'baseCurrency' => $baseCurrency,
                'totalBalanceBase' => $total,
                'activeCount' => $activeCount,
                'archivedCount' => $archivedCount,
                'hasUnconvertedBalances' => $unconverted,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getWallet(int $userId, int $walletId): array
    {
        $wallet = $this->wallets->findByIdAndUser($walletId, $userId);
        if ($wallet === null) {
            throw new ApiException('NOT_FOUND', 'Ví không tồn tại.', 404);
        }
        return ['wallet' => $this->presentWallet($wallet, $this->requireBaseCurrency($userId))];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createWallet(int $userId, array $input, ?string $idempotencyKey): array
    {
        $name = $this->normalizeName($input['name'] ?? null);
        $type = strtoupper(trim((string) ($input['type'] ?? $input['walletType'] ?? '')));
        $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
        if (!in_array($type, self::TYPES, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Loại ví không hợp lệ.', 422, [
                'type' => 'Chọn tiền mặt, ngân hàng, ví điện tử, tiết kiệm hoặc thẻ tín dụng.',
            ]);
        }
        $balance = $this->normalizeAmount(
            $input['openingBalance'] ?? $input['initialBalance'] ?? null,
            $currency,
            'openingBalance'
        );
        $key = $this->validateIdempotencyKey($idempotencyKey);
        $baseCurrency = $this->requireBaseCurrency($userId);
        $payload = compact('name', 'type', 'currency', 'balance');
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $this->pdo->beginTransaction();
        try {
            $cached = $this->replayIdempotency($userId, $key, $requestHash);
            if ($cached !== null) {
                $this->pdo->commit();
                return $cached;
            }
            if ($this->wallets->nameExists($userId, $name)) {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Tên ví không được trùng với ví đang hoạt động hoặc đã lưu trữ.',
                ]);
            }

            $rate = $this->resolveRate($currency, $baseCurrency, $balance, true);
            $walletId = $this->wallets->create($userId, $name, $type, $currency, $balance);
            $transactionId = null;
            if (bccomp($balance, '0', 8) === 1) {
                $amountBase = $this->rates->convertToBase($balance, (string) $rate['rate']);
                $transactionId = $this->ledger->createTransaction([
                    'userId' => $userId,
                    'categoryId' => null,
                    'type' => 'OTHER',
                    'status' => 'POSTED',
                    'description' => 'Số dư đầu kỳ: ' . $name,
                    'currency' => $currency,
                    'amount' => $balance,
                    'fxRateSnapshot' => $rate['rate'],
                    'occurredOn' => date('Y-m-d'),
                    'postedAt' => date('Y-m-d H:i:s'),
                    'originalTransactionId' => null,
                ]);
                $walletAccountId = $this->wallets->getOrCreateWalletLedgerAccountId($userId, $walletId);
                $equityAccountId = $this->wallets->getOrCreateOpeningEquityAccountId($userId);
                $entry = [
                    'transactionId' => $transactionId,
                    'amountOriginal' => $balance,
                    'currency' => $currency,
                    'amountBase' => $amountBase,
                    'fxRateSnapshot' => $rate['rate'],
                ];
                $this->ledger->createLedgerEntry($entry + [
                    'ledgerAccountId' => $walletAccountId,
                    'entrySide' => 'DEBIT',
                ]);
                $this->ledger->createLedgerEntry($entry + [
                    'ledgerAccountId' => $equityAccountId,
                    'entrySide' => 'CREDIT',
                ]);
            }

            $row = $this->wallets->findByIdAndUser($walletId, $userId);
            if ($row === null) {
                throw new ApiException('WALLET_CREATE_FAILED', 'Không thể đọc lại ví vừa tạo.', 500);
            }
            $response = [
                'wallet' => $this->presentWallet($row, $baseCurrency),
                'openingTransactionId' => $transactionId,
            ];
            $this->ledger->saveIdempotency(
                $userId,
                $key,
                $requestHash,
                $transactionId,
                201,
                json_encode($response, JSON_THROW_ON_ERROR)
            );
            $this->pdo->commit();
            return $response;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($error instanceof PDOException && $error->getCode() === '23000') {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Tên ví không được trùng.',
                ]);
            }
            throw $error;
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function updateWallet(int $userId, int $walletId, array $input): array
    {
        $wallet = $this->wallets->findByIdAndUser($walletId, $userId);
        if ($wallet === null || $wallet['status'] !== 'ACTIVE') {
            throw new ApiException('NOT_FOUND', 'Ví đang hoạt động không tồn tại.', 404);
        }
        $name = $this->normalizeName($input['name'] ?? null);
        if ($this->wallets->nameExists($userId, $name, $walletId)) {
            throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                'name' => 'Tên ví không được trùng.',
            ]);
        }
        try {
            if (!$this->wallets->updateName($walletId, $userId, $name)) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật ví.', 409);
            }
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Tên ví không được trùng.',
                ]);
            }
            throw $error;
        }
        $updated = $this->wallets->findByIdAndUser($walletId, $userId);
        return ['wallet' => $this->presentWallet($updated, $this->requireBaseCurrency($userId))];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function adjustWallet(int $userId, int $walletId, array $input, ?string $idempotencyKey): array
    {
        $direction = strtoupper(trim((string) ($input['direction'] ?? '')));
        if (!in_array($direction, ['INCREASE', 'DECREASE'], true)) {
            throw new ApiException('VALIDATION_ERROR', 'Chiều điều chỉnh không hợp lệ.', 422, [
                'direction' => 'Chọn tăng hoặc giảm số dư.',
            ]);
        }
        $note = preg_replace('/\s+/u', ' ', trim((string) ($input['note'] ?? ''))) ?? '';
        if (mb_strlen($note, 'UTF-8') > 200) {
            throw new ApiException('VALIDATION_ERROR', 'Nội dung điều chỉnh quá dài.', 422, [
                'note' => 'Nội dung không vượt quá 200 ký tự.',
            ]);
        }
        $key = $this->validateIdempotencyKey($idempotencyKey);
        $baseCurrency = $this->requireBaseCurrency($userId);

        $this->pdo->beginTransaction();
        try {
            $wallet = $this->wallets->findActiveForUpdate($walletId, $userId);
            if ($wallet === null) {
                throw new ApiException('NOT_FOUND', 'Ví đang hoạt động không tồn tại.', 404);
            }
            $currency = strtoupper((string) $wallet['currency']);
            $amount = $this->normalizeAmount($input['amount'] ?? null, $currency, 'amount');
            if (bccomp($amount, '0', 8) !== 1) {
                throw new ApiException('VALIDATION_ERROR', 'Số tiền điều chỉnh phải lớn hơn 0.', 422, [
                    'amount' => 'Nhập số tiền lớn hơn 0.',
                ]);
            }
            $payload = compact('walletId', 'direction', 'amount', 'note');
            $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $cached = $this->replayIdempotency($userId, $key, $requestHash);
            if ($cached !== null) {
                $this->pdo->commit();
                return $cached;
            }

            $currentBalance = (string) $wallet['cached_balance'];
            $newBalance = $direction === 'INCREASE'
                ? bcadd($currentBalance, $amount, 8)
                : bcsub($currentBalance, $amount, 8);
            if ((string) $wallet['wallet_type'] !== 'CREDIT' && bccomp($newBalance, '0', 8) === -1) {
                throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư ví không đủ để giảm số tiền này.', 422, [
                    'amount' => 'Số tiền giảm không được vượt quá số dư hiện tại.',
                ]);
            }

            $rate = $this->resolveRate($currency, $baseCurrency, $amount, true);
            $amountBase = $this->rates->convertToBase($amount, (string) $rate['rate']);
            $transactionId = $this->ledger->createTransaction([
                'userId' => $userId,
                'categoryId' => null,
                'type' => 'OTHER',
                'status' => 'POSTED',
                'description' => $note !== '' ? $note : ($direction === 'INCREASE'
                    ? 'Điều chỉnh tăng số dư ví'
                    : 'Điều chỉnh giảm số dư ví'),
                'currency' => $currency,
                'amount' => $amount,
                'fxRateSnapshot' => $rate['rate'],
                'occurredOn' => date('Y-m-d'),
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);
            $walletAccountId = $this->wallets->getOrCreateWalletLedgerAccountId($userId, $walletId);
            $adjustmentAccountId = $this->wallets->getOrCreateBalanceAdjustmentAccountId($userId);
            $entry = [
                'transactionId' => $transactionId,
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $rate['rate'],
            ];
            $this->ledger->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $walletAccountId : $adjustmentAccountId,
                'entrySide' => 'DEBIT',
            ]);
            $this->ledger->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $adjustmentAccountId : $walletAccountId,
                'entrySide' => 'CREDIT',
            ]);
            if (!$this->wallets->setBalance($walletId, $userId, $newBalance)) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }

            $updated = $this->wallets->findByIdAndUser($walletId, $userId);
            $response = [
                'wallet' => $this->presentWallet($updated, $baseCurrency),
                'transactionId' => $transactionId,
            ];
            $this->ledger->saveIdempotency(
                $userId,
                $key,
                $requestHash,
                $transactionId,
                201,
                json_encode($response, JSON_THROW_ON_ERROR)
            );
            $this->pdo->commit();
            return $response;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function archiveWallet(int $userId, int $walletId): void
    {
        $this->pdo->beginTransaction();
        try {
            $wallet = $this->wallets->findActiveForUpdate($walletId, $userId);
            if ($wallet === null) {
                throw new ApiException('NOT_FOUND', 'Ví đang hoạt động không tồn tại.', 404);
            }
            if (bccomp((string) $wallet['cached_balance'], '0', 8) !== 0) {
                throw new ApiException('WALLET_BALANCE_NOT_ZERO', 'Chỉ có thể lưu trữ ví có số dư bằng 0.', 422);
            }
            if (bccomp((string) ($wallet['earmarked_balance'] ?? '0'), '0', 8) !== 0) {
                throw new ApiException('WALLET_EARMARK_NOT_ZERO', 'Ví vẫn còn số tiền đang được phân bổ cho mục tiêu.', 422);
            }
            if ($this->wallets->hasActiveRecurringSchedule($walletId, $userId)) {
                throw new ApiException(
                    'WALLET_HAS_ACTIVE_RECURRING',
                    'Ví đang được sử dụng bởi giao dịch định kỳ hoạt động.',
                    422
                );
            }
            if (!$this->wallets->archive($walletId, $userId)) {
                throw new ApiException('WALLET_ARCHIVE_FAILED', 'Không thể lưu trữ ví.', 409);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function presentWallet(array $row, string $baseCurrency): array
    {
        $currency = strtoupper((string) $row['currency']);
        $balance = (string) $row['cached_balance'];
        $rate = $this->resolveRate($currency, $baseCurrency, $balance, false);
        $baseBalance = $rate === null
            ? (bccomp($balance, '0', 8) === 0 ? '0.00000000' : null)
            : $this->rates->convertToBase($balance, (string) $rate['rate']);

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'type' => (string) $row['wallet_type'],
            'currency' => $currency,
            'balance' => $balance,
            'earmarkedBalance' => (string) ($row['earmarked_balance'] ?? '0'),
            'status' => (string) $row['status'],
            'iconKey' => $this->defaultIcon((string) $row['wallet_type']),
            'baseCurrency' => $baseCurrency,
            'baseBalance' => $baseBalance,
            'conversionRate' => $rate['rate'] ?? null,
            'rateEffectiveAt' => $rate['effectiveAt'] ?? null,
            'rateProvider' => $rate['provider'] ?? null,
            'createdAt' => (string) $row['created_at'],
        ];
    }

    /** @return array{rate: string, effectiveAt: string, provider: ?string}|null */
    private function resolveRate(
        string $currency,
        string $baseCurrency,
        string $amount,
        bool $required
    ): ?array {
        if ($currency === $baseCurrency) {
            return ['rate' => '1.0000000000', 'effectiveAt' => '', 'provider' => 'IDENTITY'];
        }
        $rate = $this->rates->findLatestRate($currency, $baseCurrency);
        if ($rate === null && $required && bccomp($amount, '0', 8) === 1) {
            throw new ApiException(
                'EXCHANGE_RATE_UNAVAILABLE',
                "Chưa có tỷ giá hợp lệ từ {$currency} sang {$baseCurrency}.",
                422,
                ['currency' => 'Cần cập nhật tỷ giá Vietcombank trước khi ghi nhận số dư ngoại tệ.']
            );
        }
        return $rate;
    }

    private function normalizeName(mixed $value): string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
            throw new ApiException('VALIDATION_ERROR', 'Tên ví không hợp lệ.', 422, [
                'name' => 'Tên ví bắt buộc và không vượt quá 120 ký tự.',
            ]);
        }
        return $name;
    }

    private function normalizeAmount(mixed $value, string $currency, string $field): string
    {
        if (!in_array($currency, self::CURRENCIES, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Tiền tệ không hợp lệ.', 422, [
                'currency' => 'Chỉ hỗ trợ VND, USD hoặc EUR.',
            ]);
        }
        $raw = trim((string) ($value ?? '0'));
        $pattern = $currency === 'VND' ? '/^\d{1,16}$/D' : '/^\d{1,16}(?:\.\d{1,2})?$/D';
        if (!preg_match($pattern, $raw)) {
            throw new ApiException('VALIDATION_ERROR', 'Số tiền không hợp lệ.', 422, [
                $field => $currency === 'VND'
                    ? 'Số tiền VND phải là số nguyên không âm.'
                    : 'Số tiền phải không âm và có tối đa 2 chữ số thập phân.',
            ]);
        }
        $parts = explode('.', $raw, 2);
        $whole = ltrim($parts[0], '0') ?: '0';
        if ($currency === 'VND') {
            return $whole;
        }
        return $whole . '.' . str_pad($parts[1] ?? '', 2, '0');
    }

    private function validateIdempotencyKey(?string $value): string
    {
        $key = trim((string) $value);
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $key)) {
            throw new ApiException(
                'IDEMPOTENCY_KEY_REQUIRED',
                'Yêu cầu cần Idempotency-Key hợp lệ.',
                400
            );
        }
        return strtolower($key);
    }

    /** @return array<string, mixed>|null */
    private function replayIdempotency(int $userId, string $key, string $requestHash): ?array
    {
        $cached = $this->ledger->findIdempotency($userId, $key);
        if ($cached === null) {
            return null;
        }
        if (!hash_equals((string) $cached['request_hash'], $requestHash)) {
            throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa yêu cầu đã được sử dụng với dữ liệu khác.', 409);
        }
        return json_decode((string) $cached['response_json'], true, 32, JSON_THROW_ON_ERROR);
    }

    private function requireBaseCurrency(int $userId): string
    {
        $currency = $this->wallets->getUserBaseCurrency($userId);
        if ($currency === null || !in_array($currency, self::CURRENCIES, true)) {
            throw new ApiException('USER_NOT_FOUND', 'Không tìm thấy tài khoản người dùng đang hoạt động.', 404);
        }
        return $currency;
    }

    private function defaultIcon(string $type): string
    {
        return match ($type) {
            'BANK' => 'bank',
            'E_WALLET' => 'wallet',
            'SAVINGS' => 'savings',
            'CREDIT' => 'card',
            default => 'cash',
        };
    }
}
