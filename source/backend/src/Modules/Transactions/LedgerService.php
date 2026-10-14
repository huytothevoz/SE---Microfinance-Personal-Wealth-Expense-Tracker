<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use WealthFlow\Config\Database;
use WealthFlow\Modules\Wallets\WalletRepository;

final class LedgerService
{
    private TransactionRepository $txRepo;
    private WalletRepository $walletRepo;
    private CategoryRepository $catRepo;
    private ExchangeRateService $fxService;

    public function __construct(
        ?TransactionRepository $txRepo = null,
        ?WalletRepository $walletRepo = null,
        ?CategoryRepository $catRepo = null,
        ?ExchangeRateService $fxService = null
    ) {
        $this->txRepo = $txRepo ?? new TransactionRepository();
        $this->walletRepo = $walletRepo ?? new WalletRepository();
        $this->catRepo = $catRepo ?? new CategoryRepository();
        $this->fxService = $fxService ?? new ExchangeRateService();
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
        $record = $this->txRepo->findIdempotency($userId, $key);

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
        $this->txRepo->saveIdempotency($userId, $key, $hash, $txId, $code, json_encode($response, JSON_THROW_ON_ERROR));
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
        $amount = (float) ($dto['amount'] ?? 0);
        $description = trim((string) ($dto['description'] ?? 'Chi tiêu'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        if ($amount <= 0) {
            throw new InvalidArgumentException('Số tiền chi tiêu phải lớn hơn 0.');
        }

        $wallet = $this->walletRepo->findByIdAndUser($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví thanh toán không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = strtoupper($dto['currency'] ?? $wallet['currency']);
        $currentBalance = (float) $wallet['cached_balance'];

        // Balance check (unless credit wallet)
        if ($wallet['wallet_type'] !== 'CREDIT' && $currentBalance < $amount) {
            throw new RuntimeException('Số dư ví không đủ chi trả khoản chi này.', 422);
        }

        $category = $this->catRepo->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục chi tiêu không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        // FR04: Snapshot exchange rate to base currency (VND)
        $fxRate = $this->fxService->getSnapshotRate($currency, 'VND');
        $amountBase = $this->fxService->convertToBase($amount, $fxRate);

        $pdo = $this->txRepo->getPdo();
        $pdo->beginTransaction();

        try {
            // 1. Insert Transaction
            $txId = $this->txRepo->createTransaction([
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
            $expenseAccountId = $this->catRepo->getOrCreateCategoryLedgerAccountId($userId, $categoryId, 'EXPENSE');
            $walletAccountId = $this->walletRepo->getOrCreateWalletLedgerAccountId($userId, $walletId);

            // 3. Double-entry: DEBIT Expense, CREDIT Asset
            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $expenseAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $walletAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            // 4. Update cached balance
            $newBalance = $currentBalance - $amount;
            $this->walletRepo->updateBalance($walletId, $newBalance);

            $pdo->commit();

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
            $pdo->rollBack();
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
        $amount = (float) ($dto['amount'] ?? 0);
        $description = trim((string) ($dto['description'] ?? 'Thu nhập'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        if ($amount <= 0) {
            throw new InvalidArgumentException('Số tiền thu nhập phải lớn hơn 0.');
        }

        $wallet = $this->walletRepo->findByIdAndUser($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví nhận tiền không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = strtoupper($dto['currency'] ?? $wallet['currency']);
        $currentBalance = (float) $wallet['cached_balance'];

        $category = $this->catRepo->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục thu nhập không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $fxRate = $this->fxService->getSnapshotRate($currency, 'VND');
        $amountBase = $this->fxService->convertToBase($amount, $fxRate);

        $pdo = $this->txRepo->getPdo();
        $pdo->beginTransaction();

        try {
            $txId = $this->txRepo->createTransaction([
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

            $incomeAccountId = $this->catRepo->getOrCreateCategoryLedgerAccountId($userId, $categoryId, 'INCOME');
            $walletAccountId = $this->walletRepo->getOrCreateWalletLedgerAccountId($userId, $walletId);

            // Double entry: DEBIT Asset Wallet, CREDIT Income
            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $walletAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $incomeAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $currency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $newBalance = $currentBalance + $amount;
            $this->walletRepo->updateBalance($walletId, $newBalance);

            $pdo->commit();

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
            $pdo->rollBack();
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
        $amount = (float) ($dto['amount'] ?? 0);
        $description = trim((string) ($dto['description'] ?? 'Chuyển tiền nội bộ'));
        $occurredOn = $dto['occurredOn'] ?? date('Y-m-d');

        if ($fromWalletId === $toWalletId) {
            throw new InvalidArgumentException('Ví nguồn và ví đích phải khác nhau.');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Số tiền chuyển phải lớn hơn 0.');
        }

        $fromWallet = $this->walletRepo->findByIdAndUser($fromWalletId, $userId);
        $toWallet = $this->walletRepo->findByIdAndUser($toWalletId, $userId);

        if (!$fromWallet || !$toWallet) {
            throw new InvalidArgumentException('Ví nguồn hoặc ví đích không hợp lệ hoặc không thuộc quyền sở hữu.');
        }

        $fromBalance = (float) $fromWallet['cached_balance'];
        if ($fromWallet['wallet_type'] !== 'CREDIT' && $fromBalance < $amount) {
            throw new RuntimeException('Số dư ví nguồn không đủ để thực hiện chuyển tiền.', 422);
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
            $destAmount = isset($dto['destAmount']) && (float) $dto['destAmount'] > 0 
                ? (float) $dto['destAmount'] 
                : (float) bcmul((string) $amount, $this->fxService->getSnapshotRate($fromCurrency, $toCurrency), 4);
            $fxRate = $this->fxService->getSnapshotRate($fromCurrency, 'VND');
            $amountBase = $this->fxService->convertToBase($amount, $fxRate);
        }

        $pdo = $this->txRepo->getPdo();
        $pdo->beginTransaction();

        try {
            $txId = $this->txRepo->createTransaction([
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

            $fromAccountId = $this->walletRepo->getOrCreateWalletLedgerAccountId($userId, $fromWalletId);
            $toAccountId = $this->walletRepo->getOrCreateWalletLedgerAccountId($userId, $toWalletId);

            // Double entry: DEBIT Destination Wallet, CREDIT Source Wallet
            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $toAccountId,
                'entrySide' => 'DEBIT',
                'amountOriginal' => $destAmount,
                'currency' => $toCurrency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $this->txRepo->createLedgerEntry([
                'transactionId' => $txId,
                'ledgerAccountId' => $fromAccountId,
                'entrySide' => 'CREDIT',
                'amountOriginal' => $amount,
                'currency' => $fromCurrency,
                'amountBase' => $amountBase,
                'fxRateSnapshot' => $fxRate,
            ]);

            $newFromBalance = $fromBalance - $amount;
            $newToBalance = (float) $toWallet['cached_balance'] + $destAmount;

            $this->walletRepo->updateBalance($fromWalletId, $newFromBalance);
            $this->walletRepo->updateBalance($toWalletId, $newToBalance);

            $pdo->commit();

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
            $pdo->rollBack();
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
        $tx = $this->txRepo->findByIdAndUser($txId, $userId);
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

        $pdo = $this->txRepo->getPdo();
        $pdo->beginTransaction();

        try {
            // 1. Create reversal transaction record
            $revTxId = $this->txRepo->createTransaction([
                'userId' => $userId,
                'categoryId' => $tx['category_id'],
                'type' => $tx['transaction_type'],
                'status' => 'REVERSED',
                'description' => '[ĐẢO] ' . $tx['description'] . ' - Lý do: ' . $reason,
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
                $this->txRepo->createLedgerEntry([
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
                    $wallet = $this->walletRepo->findByIdAndUser($walletId, $userId);
                    if ($wallet) {
                        $curr = (float) $wallet['cached_balance'];
                        $entryAmt = (float) $entry['amount_original'];
                        // Original DEBIT added to wallet => reversal CREDIT subtracts
                        // Original CREDIT subtracted from wallet => reversal DEBIT adds
                        $newBal = $entry['entry_side'] === 'DEBIT' ? ($curr - $entryAmt) : ($curr + $entryAmt);
                        $this->walletRepo->updateBalance($walletId, $newBal);
                    }
                }
            }

            // 3. Mark original transaction as REVERSED
            $this->txRepo->updateStatus($txId, 'REVERSED');

            $pdo->commit();

            return [
                'reversalTransactionId' => $revTxId,
                'originalTransactionId' => $txId,
                'status' => 'REVERSED',
                'reason' => $reason,
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
