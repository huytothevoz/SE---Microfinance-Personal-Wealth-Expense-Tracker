<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use PDOException;
use Throwable;
use WealthFlow\Http\ApiException;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\TransactionRepository;

final class WalletService
{
    private const TYPES = ['CASH', 'BANK', 'E_WALLET', 'SAVINGS'];
    private const CURRENCIES = ['VND', 'USD', 'EUR'];
    private const ICONS = ['cash', 'bank', 'wallet', 'savings'];
    private const COLORS = [
        'blue', 'navy', 'ocean', 'cyan', 'teal', 'green', 'emerald', 'forest',
        'amber', 'orange', 'coral', 'rose', 'purple', 'indigo', 'slate', 'graphite',
    ];

    private PDO $pdo;
    private WalletRepository $wallets;
    private TransactionRepository $transactions;
    private ExchangeRateService $rates;

    public function __construct(
        ?WalletRepository $wallets = null,
        ?TransactionRepository $transactions = null,
        ?ExchangeRateService $rates = null
    ) {
        $this->wallets = $wallets ?? new WalletRepository();
        $this->pdo = $this->wallets->getPdo();
        $this->transactions = $transactions ?? new TransactionRepository($this->pdo);
        $this->rates = $rates ?? new ExchangeRateService($this->pdo);
    }

    /** @return array<string, mixed> */
    public function listWallets(int $userId, string $status): array
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
        $total = $this->zeroFor($baseCurrency);

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
                    $total = bcadd($total, $wallet['baseBalance'], $this->currencyScale($baseCurrency));
                }
            } else {
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

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createWallet(int $userId, array $input, ?string $idempotencyKey): array
    {
        $name = $this->normalizeName($input['name'] ?? null);
        $nameKey = mb_strtolower($name, 'UTF-8');
        $type = strtoupper(trim((string) ($input['type'] ?? '')));
        $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
        $balance = $this->normalizeAmount($input['openingBalance'] ?? null, $currency);
        $icon = $this->choice((string) ($input['iconKey'] ?? ''), self::ICONS, $this->defaultIcon($type), 'iconKey');
        $color = $this->normalizeColor((string) ($input['colorKey'] ?? ''), 'blue');
        $key = $this->validateIdempotencyKey($idempotencyKey);

        if (!in_array($type, self::TYPES, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Loại ví không hợp lệ.', 422, [
                'type' => 'Chọn tiền mặt, ngân hàng, ví điện tử hoặc tiết kiệm.',
            ]);
        }

        $baseCurrency = $this->requireBaseCurrency($userId);
        $payload = compact('name', 'type', 'currency', 'balance', 'icon', 'color');
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $this->pdo->beginTransaction();
        try {
            $cached = $this->transactions->findIdempotency($userId, $key);
            if ($cached !== null) {
                if (!hash_equals((string) $cached['request_hash'], $requestHash)) {
                    throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa yêu cầu đã được sử dụng với dữ liệu khác.', 409);
                }
                $response = json_decode((string) $cached['response_json'], true, 32, JSON_THROW_ON_ERROR);
                $this->pdo->commit();
                return $response;
            }

            if ($this->wallets->activeNameExists($userId, $nameKey)) {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Tên ví không được trùng với ví đang hoạt động.',
                ]);
            }

            $rate = $this->resolveRate($currency, $baseCurrency, $balance);
            $walletId = $this->wallets->create([
                'userId' => $userId,
                'name' => $name,
                'activeNameKey' => $nameKey,
                'type' => $type,
                'currency' => $currency,
                'balance' => $balance,
                'iconKey' => $icon,
                'colorKey' => $color,
            ]);

            $transactionId = null;
            if (bccomp($balance, '0', 8) === 1) {
                $amountBase = $this->convert($balance, (string) $rate['rate'], $baseCurrency);
                $transactionId = $this->transactions->createTransaction([
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
                $this->transactions->createLedgerEntry($entry + [
                    'ledgerAccountId' => $walletAccountId,
                    'entrySide' => 'DEBIT',
                ]);
                $this->transactions->createLedgerEntry($entry + [
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
            $this->transactions->saveIdempotency(
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
                    'name' => 'Tên ví không được trùng với ví đang hoạt động.',
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
            throw new ApiException('NOT_FOUND', 'Ví không tồn tại.', 404);
        }

        $name = $this->normalizeName($input['name'] ?? null);
        $nameKey = mb_strtolower($name, 'UTF-8');
        $icon = $this->choice((string) ($input['iconKey'] ?? ''), self::ICONS, (string) $wallet['icon_key'], 'iconKey');
        $color = $this->normalizeColor((string) ($input['colorKey'] ?? ''), (string) $wallet['color_key']);
        if ($this->wallets->activeNameExists($userId, $nameKey, $walletId)) {
            throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                'name' => 'Tên ví không được trùng với ví đang hoạt động.',
            ]);
        }

        try {
            $this->wallets->updateMetadata($walletId, $userId, $name, $nameKey, $icon, $color);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new ApiException('DUPLICATE_WALLET_NAME', 'Tên ví đã tồn tại.', 409, [
                    'name' => 'Tên ví không được trùng với ví đang hoạt động.',
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
                throw new ApiException('NOT_FOUND', 'Ví không tồn tại.', 404);
            }
            $currency = strtoupper((string) $wallet['currency']);
            $amount = $this->normalizeAmount($input['amount'] ?? null, $currency);
            if (bccomp($amount, '0', 8) !== 1) {
                throw new ApiException('VALIDATION_ERROR', 'Số tiền điều chỉnh phải lớn hơn 0.', 422, [
                    'amount' => 'Nhập số tiền lớn hơn 0.',
                ]);
            }
            $payload = compact('walletId', 'direction', 'amount', 'note');
            $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $cached = $this->transactions->findIdempotency($userId, $key);
            if ($cached !== null) {
                if (!hash_equals((string) $cached['request_hash'], $requestHash)) {
                    throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa yêu cầu đã được sử dụng với dữ liệu khác.', 409);
                }
                $response = json_decode((string) $cached['response_json'], true, 32, JSON_THROW_ON_ERROR);
                $this->pdo->commit();
                return $response;
            }

            $scale = $this->currencyScale($currency);
            $currentBalance = (string) $wallet['cached_balance'];
            $newBalance = $direction === 'INCREASE'
                ? bcadd($currentBalance, $amount, $scale)
                : bcsub($currentBalance, $amount, $scale);
            if (bccomp($newBalance, '0', $scale) === -1) {
                throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư ví không đủ để giảm số tiền này.', 422, [
                    'amount' => 'Số tiền giảm không được vượt quá số dư hiện tại.',
                ]);
            }

            $rate = $this->resolveRate($currency, $baseCurrency, $amount);
            $amountBase = $this->convert($amount, (string) $rate['rate'], $baseCurrency);
            $transactionId = $this->transactions->createTransaction([
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
            $this->transactions->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $walletAccountId : $adjustmentAccountId,
                'entrySide' => 'DEBIT',
            ]);
            $this->transactions->createLedgerEntry($entry + [
                'ledgerAccountId' => $direction === 'INCREASE' ? $adjustmentAccountId : $walletAccountId,
                'entrySide' => 'CREDIT',
            ]);
            $this->wallets->setBalance($walletId, $userId, $newBalance);

            $updated = $this->wallets->findByIdAndUser($walletId, $userId);
            $response = [
                'wallet' => $this->presentWallet($updated, $baseCurrency),
                'transactionId' => $transactionId,
            ];
            $this->transactions->saveIdempotency(
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
                throw new ApiException('NOT_FOUND', 'Ví không tồn tại.', 404);
            }
            if (bccomp((string) $wallet['cached_balance'], '0', 8) !== 0) {
                throw new ApiException('WALLET_BALANCE_NOT_ZERO', 'Chỉ có thể lưu trữ ví có số dư bằng 0.', 422);
            }
            if ($this->wallets->hasActiveRecurringSchedule($walletId, $userId)) {
                throw new ApiException(
                    'WALLET_HAS_ACTIVE_RECURRING',
                    'Ví đang được sử dụng bởi giao dịch định kỳ hoạt động.',
                    422
                );
            }
            $this->wallets->archive($walletId, $userId);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function deleteWalletPermanently(int $userId, int $walletId): void
    {
        $this->pdo->beginTransaction();
        try {
            $wallet = $this->wallets->findArchivedForUpdate($walletId, $userId);
            if ($wallet === null) {
                throw new ApiException(
                    'WALLET_MUST_BE_ARCHIVED',
                    'Ví phải được lưu trữ trước khi xóa vĩnh viễn.',
                    409
                );
            }
            if (bccomp((string) $wallet['cached_balance'], '0', 8) !== 0) {
                throw new ApiException('WALLET_BALANCE_NOT_ZERO', 'Ví phải có số dư bằng 0.', 422);
            }
            if ($this->wallets->hasActiveRecurringSchedule($walletId, $userId)) {
                throw new ApiException(
                    'WALLET_HAS_ACTIVE_RECURRING',
                    'Không thể xóa ví đang liên kết với lịch định kỳ hoạt động.',
                    409
                );
            }
            if (!$this->wallets->markDeleted($walletId, $userId)) {
                throw new ApiException('WALLET_DELETE_FAILED', 'Không thể xóa ví.', 409);
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
        $rate = $this->resolveRate($currency, $baseCurrency, (string) $row['cached_balance'], false);
        $baseBalance = $rate === null
            ? null
            : $this->convert((string) $row['cached_balance'], (string) $rate['rate'], $baseCurrency);

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'type' => (string) $row['wallet_type'],
            'currency' => $currency,
            'balance' => (string) $row['cached_balance'],
            'status' => (string) $row['status'],
            'iconKey' => $row['icon_key'] ?: $this->defaultIcon((string) $row['wallet_type']),
            'colorKey' => $row['color_key'] ?: 'blue',
            'baseCurrency' => $baseCurrency,
            'baseBalance' => $baseBalance,
            'conversionRate' => $rate['rate'] ?? null,
            'rateEffectiveAt' => $rate['effectiveAt'] ?? null,
            'rateProvider' => $rate['provider'] ?? null,
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) $row['updated_at'],
            'archivedAt' => $row['archived_at'],
            'deletedAt' => $row['deleted_at'],
        ];
    }

    /** @return array{rate: string, effectiveAt: ?string}|null */
    private function resolveRate(
        string $currency,
        string $baseCurrency,
        string $amount,
        bool $required = true
    ): ?array {
        if ($currency === $baseCurrency) {
            return ['rate' => '1', 'effectiveAt' => null];
        }
        $rate = $this->rates->findLatestRate($currency, $baseCurrency);
        if ($rate === null && $required && bccomp($amount, '0', 8) === 1) {
            throw new ApiException(
                'EXCHANGE_RATE_UNAVAILABLE',
                "Chưa có tỷ giá hợp lệ từ {$currency} sang {$baseCurrency}.",
                422,
                ['currency' => 'Cần cập nhật tỷ giá trước khi ghi nhận số dư ngoại tệ.']
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

    private function normalizeAmount(mixed $value, string $currency): string
    {
        if (!in_array($currency, self::CURRENCIES, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Tiền tệ không hợp lệ.', 422, [
                'currency' => 'Chỉ hỗ trợ VND, USD hoặc EUR.',
            ]);
        }
        $raw = trim((string) ($value ?? '0'));
        $scale = $this->currencyScale($currency);
        $pattern = $scale === 0 ? '/^\d{1,16}$/' : '/^\d{1,16}(?:\.\d{1,2})?$/' ;
        if (!preg_match($pattern, $raw)) {
            throw new ApiException('VALIDATION_ERROR', 'Số dư đầu kỳ không hợp lệ.', 422, [
                'openingBalance' => $scale === 0
                    ? 'Số dư VND phải là số nguyên không âm.'
                    : 'Số dư phải không âm và có tối đa 2 chữ số thập phân.',
            ]);
        }
        $parts = explode('.', $raw, 2);
        $whole = ltrim($parts[0], '0') ?: '0';
        if ($scale === 0) {
            return $whole;
        }
        $fraction = str_pad($parts[1] ?? '', $scale, '0');
        return $whole . '.' . $fraction;
    }

    private function validateIdempotencyKey(?string $value): string
    {
        $key = trim((string) $value);
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $key)) {
            throw new ApiException(
                'IDEMPOTENCY_KEY_REQUIRED',
                'Yêu cầu tạo ví cần Idempotency-Key hợp lệ.',
                400
            );
        }
        return strtolower($key);
    }

    /** @param array<int, string> $allowed */
    private function choice(string $value, array $allowed, string $default, string $field): string
    {
        $choice = strtolower(trim($value));
        if ($choice === '') {
            return $default;
        }
        if (!in_array($choice, $allowed, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Tùy chọn hiển thị không hợp lệ.', 422, [
                $field => 'Giá trị không nằm trong danh sách hỗ trợ.',
            ]);
        }
        return $choice;
    }

    private function normalizeColor(string $value, string $default): string
    {
        $color = strtolower(trim($value));
        if ($color === '') {
            return $default;
        }
        if (in_array($color, self::COLORS, true) || preg_match('/^#[0-9a-f]{6}$/D', $color)) {
            return $color;
        }
        throw new ApiException('VALIDATION_ERROR', 'Màu hiển thị không hợp lệ.', 422, [
            'colorKey' => 'Chọn một màu hợp lệ.',
        ]);
    }

    private function defaultIcon(string $type): string
    {
        return match ($type) {
            'BANK' => 'bank',
            'E_WALLET' => 'wallet',
            'SAVINGS' => 'savings',
            default => 'cash',
        };
    }

    private function requireBaseCurrency(int $userId): string
    {
        $currency = $this->wallets->getUserBaseCurrency($userId);
        if ($currency === null || !in_array($currency, self::CURRENCIES, true)) {
            throw new ApiException('USER_NOT_FOUND', 'Không xác định được tiền tệ cơ sở của người dùng.', 404);
        }
        return $currency;
    }

    private function currencyScale(string $currency): int
    {
        return $currency === 'VND' ? 0 : 2;
    }

    private function zeroFor(string $currency): string
    {
        return $this->currencyScale($currency) === 0 ? '0' : '0.00';
    }

    private function convert(string $amount, string $rate, string $baseCurrency): string
    {
        $scale = $this->currencyScale($baseCurrency);
        $working = bcmul($amount, $rate, $scale + 1);
        $increment = $scale === 0 ? '0.5' : '0.' . str_repeat('0', $scale) . '5';
        return bcadd($working, $increment, $scale);
    }
}
