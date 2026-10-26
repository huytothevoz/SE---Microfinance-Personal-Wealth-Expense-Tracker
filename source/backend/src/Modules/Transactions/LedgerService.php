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
        $amount = $this->positiveAmount($dto['amount'] ?? null, 'Số tiền chi tiêu');
        $description = trim((string) ($dto['description'] ?? 'Chi tiêu'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if (!$wallet || (string) $wallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Ví thanh toán đang hoạt động không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = strtoupper((string) $wallet['currency']);
        $this->rejectCurrencyMismatch($dto, $currency);

        $category = $this->catDao->findActiveByIdAndUserAndType($categoryId, $userId, 'EXPENSE');
        if (!$category) {
            throw new InvalidArgumentException('Danh mục chi tiêu không tồn tại hoặc không thuộc quyền sở hữu.');
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
                bcmul($amount, '-1', 8),
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
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
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
        $amount = $this->positiveAmount($dto['amount'] ?? null, 'Số tiền thu nhập');
        $description = trim((string) ($dto['description'] ?? 'Thu nhập'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if (!$wallet || (string) $wallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Ví nhận tiền đang hoạt động không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        if ((string) $wallet['wallet_type'] === 'CREDIT') {
            throw new InvalidArgumentException('Thanh toán thẻ tín dụng phải dùng chuyển tiền, không được ghi là thu nhập.');
        }

        $currency = strtoupper((string) $wallet['currency']);
        $this->rejectCurrencyMismatch($dto, $currency);

        $category = $this->catDao->findActiveByIdAndUserAndType($categoryId, $userId, 'INCOME');
        if (!$category) {
            throw new InvalidArgumentException('Danh mục thu nhập không tồn tại hoặc không thuộc quyền sở hữu.');
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
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
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
        $amount = $this->positiveAmount($dto['amount'] ?? null, 'Số tiền chuyển');
        $description = trim((string) ($dto['description'] ?? 'Chuyển tiền nội bộ'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        if ($fromWalletId === $toWalletId) {
            throw new InvalidArgumentException('Ví nguồn và ví đích phải khác nhau.');
        }
        $fromWallet = $this->walletDao->findByIdAndUser($fromWalletId, $userId);
        $toWallet = $this->walletDao->findByIdAndUser($toWalletId, $userId);

        if (!$fromWallet || !$toWallet
            || (string) $fromWallet['status'] !== 'ACTIVE'
            || (string) $toWallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Ví nguồn hoặc ví đích đang hoạt động không hợp lệ hoặc không thuộc quyền sở hữu.');
        }
        if ((string) $fromWallet['wallet_type'] === 'CREDIT') {
            throw new InvalidArgumentException('Không thể dùng thẻ tín dụng làm ví nguồn cho chuyển tiền nội bộ.');
        }

        $fromCurrency = $fromWallet['currency'];
        $toCurrency = $toWallet['currency'];

        // Determine destination amount
        if ($fromCurrency === $toCurrency) {
            $destAmount = $amount;
            $fxRate = '1.00000000';
            $amountBase = $this->fxService->convertToBase($amount, $this->fxService->getSnapshotRate($fromCurrency, 'VND'));
            $destinationFxRate = $this->fxService->getSnapshotRate($toCurrency, 'VND');
        } else {
            // The server is the sole authority for destination amount and rate snapshot.
            $conversionRate = $this->fxService->getSnapshotRate($fromCurrency, $toCurrency);
            $destAmount = bcmul($amount, $conversionRate, $toCurrency === 'VND' ? 0 : 8);
            $fxRate = $this->fxService->getSnapshotRate($fromCurrency, 'VND');
            $amountBase = $this->fxService->convertToBase($amount, $fxRate);
            $destinationFxRate = bcdiv($amountBase, $destAmount, 10);
        }
        if ((string) $toWallet['wallet_type'] === 'CREDIT'
            && bccomp(bcadd((string) $toWallet['cached_balance'], $destAmount, 8), '0', 8) === 1) {
            throw new InvalidArgumentException('Số tiền thanh toán thẻ không được vượt quá dư nợ hiện tại.');
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
                'fxRateSnapshot' => $destinationFxRate,
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
                bcmul($amount, '-1', 8),
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
                'conversionRate' => $fromCurrency === $toCurrency ? '1.00000000' : $conversionRate,
                'amountBase' => $amountBase,
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
                'description' => '[ĐẢO] ' . $tx['description'] . ' - Lý do: ' . $reason,
                'currency' => $tx['currency'],
                'amount' => (string) $tx['amount'],
                'fxRateSnapshot' => (string) $tx['fx_rate_snapshot'],
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
                    'amountOriginal' => (string) $entry['amount_original'],
                    'currency' => $entry['currency'],
                    'amountBase' => (string) $entry['amount_base'],
                    'fxRateSnapshot' => (string) $entry['fx_rate_snapshot'],
                ]);

                // If account is linked to a wallet, adjust balance back
                if (!empty($entry['wallet_id'])) {
                    $walletId = (int) $entry['wallet_id'];
                    $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
                    if ($wallet) {
                        $entryAmt = (string) $entry['amount_original'];
                        $delta = $entry['entry_side'] === 'DEBIT' ? bcmul($entryAmt, '-1', 8) : $entryAmt;
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

    private function positiveAmount(mixed $value, string $label): string
    {
        $amount = trim((string) ($value ?? ''));
        if (!preg_match('/^\d+(?:\.\d{1,8})?$/D', $amount) || bccomp($amount, '0', 8) !== 1) {
            throw new InvalidArgumentException($label . ' phải là số thập phân hợp lệ lớn hơn 0.');
        }
        return bcadd($amount, '0', 8);
    }

    /** @param array<string, mixed> $dto */
    private function rejectCurrencyMismatch(array $dto, string $walletCurrency): void
    {
        if (isset($dto['currency']) && strtoupper(trim((string) $dto['currency'])) !== $walletCurrency) {
            throw new InvalidArgumentException('Đơn vị tiền của giao dịch phải trùng với đơn vị tiền của ví.');
        }
    }
}
