<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use Exception;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Wallets\WalletDAO;

final class LedgerService
{
    private LedgerDAO $txDao;
    private WalletDAO $walletDao;
    private CategoryDAO $catDao;
    private ExchangeRateService $fxService;

    public function __construct(
        ?LedgerDAO $txDao = null,
        ?WalletDAO $walletDao = null,
        ?CategoryDAO $catDao = null,
        ?ExchangeRateService $fxService = null
    ) {
        $this->txDao = $txDao ?? new LedgerDAO();
        $this->walletDao = $walletDao ?? new WalletDAO();
        $this->catDao = $catDao ?? new CategoryDAO();
        $this->fxService = $fxService ?? new ExchangeRateService();
    }

    public function getPdo(): \PDO
    {
        return $this->txDao->getPdo();
    }

    /**
     * Check idempotency before executing transaction (BR13).
     * @return array{cached: bool, response?: array<string, mixed>, code?: int}
     */
    public function checkIdempotency(int $userId, ?string $key, array $payload): array
    {
        if ($key === null || trim($key) === '') {
            return ['cached' => false];
        }

        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $record = $this->txDao->findIdempotency($userId, $key);

        if ($record !== null) {
            if ($record['request_hash'] !== $hash) {
                // BR13: Same key but different payload -> 409 Conflict
                throw new RuntimeException('IDEMPOTENCY_CONFLICT: Idempotency key đã được sử dụng với payload khác.', 409);
            }
            // Same key and same payload -> Return cached response
            $cachedData = json_decode((string) $record['response_json'], true);
            return [
                'cached' => true,
                'response' => is_array($cachedData) ? $cachedData : [],
                'code' => (int) ($record['result_code'] ?? 200),
            ];
        }

        return ['cached' => false];
    }

    /**
     * Save idempotency record upon successful commit (BR13).
     */
    public function recordIdempotency(int $userId, ?string $key, array $payload, ?int $txId, int $code, array $response): void
    {
        if ($key === null || trim($key) === '') {
            return;
        }
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->txDao->saveIdempotency($userId, $key, $hash, $txId, $code, json_encode($response, JSON_THROW_ON_ERROR));
    }

    /**
     * @param callable(): array<string, mixed> $operation
     * @return array{response: array<string, mixed>, code: int}
     */
    public function executeIdempotently(
        int $userId,
        ?string $key,
        array $payload,
        int $code,
        callable $operation
    ): array {
        $pdo = $this->txDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $cached = $this->checkIdempotency($userId, $key, $payload);
            if ($cached['cached']) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return [
                    'response' => $cached['response'] ?? [],
                    'code' => $cached['code'] ?? 200,
                ];
            }

            $response = $operation();
            $txId = $response['transactionId'] ?? $response['reversalTransactionId'] ?? null;
            $this->recordIdempotency(
                $userId,
                $key,
                $payload,
                $txId === null ? null : (int) $txId,
                $code,
                $response
            );
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return ['response' => $response, 'code' => $code];
        } catch (PDOException $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($key !== null && trim($key) !== '') {
                $cached = $this->checkIdempotency($userId, $key, $payload);
                if ($cached['cached']) {
                    return [
                        'response' => $cached['response'] ?? [],
                        'code' => $cached['code'] ?? 200,
                    ];
                }
            }
            throw $error;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Post an Expense transaction (FR03.01, BR01, BR05).
     * Entry 1 (DEBIT): Expense Category
     * Entry 2 (CREDIT): Asset Wallet
     *
     * @param array{
     *     walletId: int,
     *     categoryId: int,
     *     amount: float|string,
     *     description?: string,
     *     occurredOn?: string,
     *     currency?: string
     * } $dto
     * @return array<string, mixed>
     */
    public function postExpense(int $userId, array $dto): array
    {
        $walletId = (int) ($dto['walletId'] ?? 0);
        $categoryId = (int) ($dto['categoryId'] ?? 0);
        $amount = $this->validateAmount($dto['amount'] ?? null, 'Số tiền chi tiêu');
        $description = $this->validateDescription($dto['description'] ?? null, 'Chi tiêu');
        $occurredOn = $this->validateDate((string) ($dto['occurredOn'] ?? date('Y-m-d')));

        $wallet = $this->walletDao->findActiveForUpdate($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví thanh toán không hoạt động, không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = $this->validateWalletCurrency($dto['currency'] ?? null, (string) $wallet['currency']);

        $category = $this->catDao->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục chi tiêu không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        if (strtoupper((string) $category['category_type']) !== 'EXPENSE') {
            throw new InvalidArgumentException('Giao dịch chi tiêu phải dùng danh mục EXPENSE.');
        }

        // FR04: Snapshot exchange rate to base currency (VND)
        $fxRate = $this->fxService->getSnapshotRate($currency, 'VND');
        $amountBase = $this->fxService->convertToBase($amount, $fxRate);

        $pdo = $this->txDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // 1. Insert Transaction
            $txId = $this->txDao->createTransaction([
                'userId' => $userId,
                'categoryId' => $categoryId,
                'type' => 'EXPENSE',
                'status' => 'POSTED',
                'description' => $description,
                'currency' => $currency,
                'amount' => $amount,
                'fxRateSnapshot' => $fxRate,
                'occurredOn' => $occurredOn,
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);

            // 2. Ledger Accounts
            $expenseAccountId = $this->catDao->getOrCreateCategoryLedgerAccountId($userId, $categoryId, 'EXPENSE');
            $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);

            // 3. Double-entry: DEBIT Expense, CREDIT Asset
            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $expenseAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $walletAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            // 4. Update cached balance
            $newBalance = $this->walletDao->adjustBalance(
                $walletId,
                -$amount,
                $wallet['wallet_type'] === 'CREDIT'
            );

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'transactionId' => $txId,
                'type' => 'EXPENSE',
                'amount' => $amount,
                'currency' => $currency,
                'amountBase' => (float) $amountBase,
                'fxRateSnapshot' => (float) $fxRate,
                'walletId' => $walletId,
                'newWalletBalance' => $newBalance,
                'categoryId' => $categoryId,
                'occurredOn' => $occurredOn,
                'status' => 'POSTED',
            ];
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Post an Income transaction (FR03.01, BR01, BR05).
     * Entry 1 (DEBIT): Asset Wallet
     * Entry 2 (CREDIT): Income Category
     *
     * @param array{
     *     walletId: int,
     *     categoryId: int,
     *     amount: float|string,
     *     description?: string,
     *     occurredOn?: string,
     *     currency?: string
     * } $dto
     * @return array<string, mixed>
     */
    public function postIncome(int $userId, array $dto): array
    {
        $walletId = (int) ($dto['walletId'] ?? 0);
        $categoryId = (int) ($dto['categoryId'] ?? 0);
        $amount = $this->validateAmount($dto['amount'] ?? null, 'Số tiền thu nhập');
        $description = $this->validateDescription($dto['description'] ?? null, 'Thu nhập');
        $occurredOn = $this->validateDate((string) ($dto['occurredOn'] ?? date('Y-m-d')));

        $wallet = $this->walletDao->findActiveForUpdate($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví nhận tiền không hoạt động, không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = $this->validateWalletCurrency($dto['currency'] ?? null, (string) $wallet['currency']);

        $category = $this->catDao->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục thu nhập không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        if (strtoupper((string) $category['category_type']) !== 'INCOME') {
            throw new InvalidArgumentException('Giao dịch thu nhập phải dùng danh mục INCOME.');
        }

        $fxRate = $this->fxService->getSnapshotRate($currency, 'VND');
        $amountBase = $this->fxService->convertToBase($amount, $fxRate);

        $pdo = $this->txDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $txId = $this->txDao->createTransaction([
                'userId' => $userId,
                'categoryId' => $categoryId,
                'type' => 'INCOME',
                'status' => 'POSTED',
                'description' => $description,
                'currency' => $currency,
                'amount' => $amount,
                'fxRateSnapshot' => $fxRate,
                'occurredOn' => $occurredOn,
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);

            $incomeAccountId = $this->catDao->getOrCreateCategoryLedgerAccountId($userId, $categoryId, 'INCOME');
            $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);

            // Double entry: DEBIT Asset Wallet, CREDIT Income
            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $walletAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $incomeAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $newBalance = $this->walletDao->adjustBalance($walletId, $amount);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'transactionId' => $txId,
                'type' => 'INCOME',
                'amount' => $amount,
                'currency' => $currency,
                'amountBase' => (float) $amountBase,
                'fxRateSnapshot' => (float) $fxRate,
                'walletId' => $walletId,
                'newWalletBalance' => $newBalance,
                'categoryId' => $categoryId,
                'occurredOn' => $occurredOn,
                'status' => 'POSTED',
            ];
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Post an Internal Transfer between 2 wallets of the same user (FR03.02, BR01, BR05).
     * Entry 1 (DEBIT): Destination Wallet
     * Entry 2 (CREDIT): Source Wallet
     * Strictly NOT counted in Income or Expense.
     *
     * @param array{
     *     fromWalletId: int,
     *     toWalletId: int,
     *     amount: float|string,
     *     destAmount?: float|string,
     *     description?: string,
     *     occurredOn?: string
     * } $dto
     * @return array<string, mixed>
     */
    public function postTransfer(int $userId, array $dto): array
    {
        $fromWalletId = (int) ($dto['fromWalletId'] ?? 0);
        $toWalletId = (int) ($dto['toWalletId'] ?? 0);
        $amount = $this->validateAmount($dto['amount'] ?? null, 'Số tiền chuyển');
        $description = $this->validateDescription($dto['description'] ?? null, 'Chuyển tiền nội bộ');
        $occurredOn = $this->validateDate((string) ($dto['occurredOn'] ?? date('Y-m-d')));

        if ($fromWalletId === $toWalletId) {
            throw new InvalidArgumentException('Ví nguồn và ví đích phải khác nhau.');
        }
        $fromWallet = $this->walletDao->findActiveForUpdate($fromWalletId, $userId);
        $toWallet = $this->walletDao->findActiveForUpdate($toWalletId, $userId);

        if (!$fromWallet || !$toWallet) {
            throw new InvalidArgumentException('Ví nguồn hoặc ví đích không hợp lệ hoặc không thuộc quyền sở hữu.');
        }

        $fromCurrency = $fromWallet['currency'];
        $toCurrency = $toWallet['currency'];

        // Determine destination amount
        if ($fromCurrency === $toCurrency) {
            $destAmount = $amount;
            $fxRate = '1.00000000';
            $amountBase = $this->fxService->convertToBase($amount, $this->fxService->getSnapshotRate($fromCurrency, 'VND'));
        } else {
            // Cross-currency transfer
            $destAmount = isset($dto['destAmount']) && trim((string) $dto['destAmount']) !== ''
                ? $this->validateAmount($dto['destAmount'], 'Số tiền ví đích')
                : (float) bcmul((string) $amount, $this->fxService->getSnapshotRate($fromCurrency, $toCurrency), 4);
            $fxRate = $this->fxService->getSnapshotRate($fromCurrency, 'VND');
            $amountBase = $this->fxService->convertToBase($amount, $fxRate);
        }

        $pdo = $this->txDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $txId = $this->txDao->createTransaction([
                'userId' => $userId,
                'categoryId' => null,
                'type' => 'TRANSFER',
                'status' => 'POSTED',
                'description' => $description,
                'currency' => $fromCurrency,
                'amount' => $amount,
                'fxRateSnapshot' => $fxRate,
                'occurredOn' => $occurredOn,
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);

            $fromAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $fromWalletId);
            $toAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $toWalletId);

            // Double entry: DEBIT Destination Wallet, CREDIT Source Wallet
            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $toAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $destAmount,
                'currency' => $toCurrency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txDao->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $fromAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $fromCurrency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $newFromBalance = $this->walletDao->adjustBalance(
                $fromWalletId,
                -$amount,
                $fromWallet['wallet_type'] === 'CREDIT'
            );
            $newToBalance = $this->walletDao->adjustBalance($toWalletId, $destAmount);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'transactionId' => $txId,
                'type' => 'TRANSFER',
                'fromWalletId' => $fromWalletId,
                'toWalletId' => $toWalletId,
                'amount' => $amount,
                'currency' => $fromCurrency,
                'destAmount' => $destAmount,
                'destCurrency' => $toCurrency,
                'newFromBalance' => $newFromBalance,
                'newToBalance' => $newToBalance,
                'status' => 'POSTED',
            ];
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reverse a POSTED transaction (FR03.03, BR06).
     * Atomic creation of reverse entries (DEBIT <-> CREDIT swapped) with original fx snapshot.
     * Updates wallet balances accordingly and marks original transaction REVERSED.
     *
     * @return array<string, mixed>
     */
    public function reverseTransaction(int $userId, int $txId, string $reason = 'Điều chỉnh sai sót'): array
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason, 'UTF-8') > 120) {
            throw new InvalidArgumentException('Lý do đảo giao dịch cần từ 1 đến 120 ký tự.');
        }
        $tx = $this->txDao->findByIdAndUser($txId, $userId);
        if (!$tx) {
            throw new InvalidArgumentException('Giao dịch không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        if ($tx['status'] !== 'POSTED') {
            throw new RuntimeException("Chỉ có thể đảo giao dịch ở trạng thái POSTED (hiện tại: {$tx['status']}).", 422);
        }

        $entries = $tx['entries'] ?? [];
        if (empty($entries)) {
            throw new RuntimeException('Giao dịch không có bút toán đối ứng hợp lệ để đảo.', 500);
        }

        $pdo = $this->txDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // 1. Create reversal transaction record
            $revTxId = $this->txDao->createTransaction([
                'userId' => $userId,
                'categoryId' => $tx['category_id'],
                'type' => $tx['transaction_type'],
                'status' => 'REVERSED',
                'description' => mb_substr('[ĐẢO] ' . $tx['description'] . ' - Lý do: ' . $reason, 0, 255, 'UTF-8'),
                'currency' => $tx['currency'],
                'amount' => (float) $tx['amount'],
                'fxRateSnapshot' => (float) $tx['fx_rate_snapshot'],
                'occurredOn' => date('Y-m-d'),
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => $txId,
            ]);

            // 2. Reverse each ledger entry (swap DEBIT <-> CREDIT)
            foreach ($entries as $entry) {
                $reversedSide = $entry['entry_side'] === 'DEBIT' ? 'CREDIT' : 'DEBIT';
                $this->txDao->createLedgerEntry([
                    'transactionId' => $revTxId,
                    'ledgerAccountId' => (int) $entry['ledger_account_id'],
                    'entrySide' => $reversedSide,
                    'amountOriginal' => (float) $entry['amount_original'],
                    'currency' => $entry['currency'],
                    'amountBase' => (float) $entry['amount_base'],
                    'fxRateSnapshot' => (float) $entry['fx_rate_snapshot'],
                ]);

                // If account is linked to a wallet, adjust balance back
                if (!empty($entry['wallet_id'])) {
                    $walletId = (int) $entry['wallet_id'];
                    $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
                    if ($wallet) {
                        $entryAmt = (float) $entry['amount_original'];
                        $delta = $entry['entry_side'] === 'DEBIT' ? -$entryAmt : $entryAmt;
                        $this->walletDao->adjustBalance($walletId, $delta, true);
                    }
                }
            }

            // 3. Mark original transaction as REVERSED
            if (!$this->txDao->markReversed($txId, $userId)) {
                throw new RuntimeException('Giao dịch đã được đảo hoặc không còn ở trạng thái POSTED.', 422);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'reversalTransactionId' => $revTxId,
                'originalTransactionId' => $txId,
                'status' => 'REVERSED',
                'reason' => $reason,
            ];
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function validateAmount(mixed $value, string $label): float
    {
        $text = trim((string) ($value ?? ''));
        if (!preg_match('/^(?:0|[1-9]\d{0,15})(?:\.\d{1,8})?$/D', $text)) {
            throw new InvalidArgumentException("{$label} không hợp lệ; tối đa 16 chữ số nguyên và 8 chữ số thập phân.");
        }
        $amount = (float) $text;
        if (!is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException("{$label} phải lớn hơn 0.");
        }
        return $amount;
    }

    private function validateDescription(mixed $value, string $default): string
    {
        $description = trim((string) ($value ?? $default));
        if ($description === '' || mb_strlen($description, 'UTF-8') > 255) {
            throw new InvalidArgumentException('Nội dung giao dịch cần từ 1 đến 255 ký tự.');
        }
        return $description;
    }

    private function validateDate(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Ngày giao dịch không đúng định dạng YYYY-MM-DD.');
        }
        return $value;
    }

    private function validateWalletCurrency(mixed $requested, string $walletCurrency): string
    {
        $walletCurrency = strtoupper($walletCurrency);
        $currency = $requested === null || trim((string) $requested) === ''
            ? $walletCurrency
            : strtoupper(trim((string) $requested));
        if ($currency !== $walletCurrency) {
            throw new InvalidArgumentException('Tiền tệ giao dịch phải trùng với tiền tệ của ví.');
        }
        return $currency;
    }
}
