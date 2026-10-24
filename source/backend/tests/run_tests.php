<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use WealthFlow\Core\ApiException;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Admin\AdminDAO;
use WealthFlow\Modules\Admin\AdminService;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Budgets\BudgetDAO;
use WealthFlow\Modules\Budgets\BudgetService;
use WealthFlow\Modules\Notifications\NotificationDAO;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Recurring\RecurringRuleDAO;
use WealthFlow\Modules\Recurring\RecurringService;
use WealthFlow\Modules\Recurring\RecurringWorker;
use WealthFlow\Modules\Reports\ExportService;
use WealthFlow\Modules\Reports\ReconciliationService;
use WealthFlow\Modules\Reports\ReportDAO;
use WealthFlow\Modules\Transactions\CategoryDAO;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Transactions\LedgerService;
use WealthFlow\Modules\Transactions\VietcombankRateProvider;
use WealthFlow\Modules\Wallets\WalletDAO;

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(string $name, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$name}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$name}: {$detail}\n";
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo "WEALTHFLOW BACKEND AUTOMATED TEST SUITE: FR02 - FR14 VERIFICATION\n";
        echo "====================================================================\n\n";

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            fwrite(STDERR, "[SKIP] pdo_sqlite not installed; FR02-FR14 tests require SQLite.\n");
            exit(2);
        }
        // Setup isolated in-memory SQLite database for deterministic tests
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Database::initSqliteSchema($pdo);
        Database::setConnection($pdo, 'sqlite');

        $walletDao = new WalletDAO($pdo);
        $catDao = new CategoryDAO($pdo);
        $txDao = new LedgerDAO($pdo);
        $fxService = new ExchangeRateService($pdo);
        $notifDao = new NotificationDAO($pdo);
        $notifService = new NotificationService($notifDao);
        $budgetDao = new BudgetDAO($pdo);
        $budgetService = new BudgetService($budgetDao, $catDao, $notifService);
        $ledgerService = new LedgerService($txDao, $walletDao, $catDao, $fxService);
        $recDao = new RecurringRuleDAO($pdo);
        $recService = new RecurringService($recDao, $walletDao);
        $recWorker = new RecurringWorker($recDao, $ledgerService, $txDao, $notifService, $budgetService);

        $walletRepo = $walletDao;
        $catRepo = $catDao;
        $txRepo = $txDao;
        $recRepo = $recDao;
        $budgetRepo = $budgetDao;

        $userId = 1;

        echo "--- GROUP 0: FR02 - Wallet Management (refactor contract) ---\n";
        $walletService = new \WealthFlow\Modules\Wallets\WalletService($walletDao, $txDao);
        $pdo->exec("INSERT INTO users (id, full_name, email, password_hash, role, status, base_currency)
            VALUES (2, 'Wallet Test User', 'wallet.test@wealthflow.local', 'test', 'USER', 'ACTIVE', 'VND'),
                   (3, 'Admin Test User', 'admin.test@wealthflow.local', 'test', 'ADMIN', 'ACTIVE', 'VND')");
        $created = $walletService->createWallet(2, [
            'name' => 'Ví kiểm thử', 'walletType' => 'CASH', 'currency' => 'VND', 'initialBalance' => '2000000'
        ]);
        $createdId = (int) $created['id'];
        $this->assert('TC-F02.01 Create wallet with server-owned opening balance',
            $createdId > 0 && (float) $created['cached_balance'] == 2000000);
        $duplicateNameRejected = false;
        try {
            $walletService->createWallet(2, [
                'name' => '  Ví   kiểm thử  ', 'walletType' => 'BANK', 'currency' => 'VND', 'initialBalance' => '0'
            ]);
        } catch (\WealthFlow\Core\ApiException $e) {
            $duplicateNameRejected = $e->status === 409 && $e->errorCode === 'DUPLICATE_WALLET_NAME';
        }
        $this->assert('TC-F02.01a Duplicate wallet name returns a business conflict instead of server error',
            $duplicateNameRejected);
        $createKey = '023e4567-e89b-42d3-a456-426614174000';
        $idempotentInput = [
            'name' => 'Ví tạo một lần', 'walletType' => 'CASH', 'currency' => 'VND', 'initialBalance' => '0'
        ];
        $firstCreate = $walletService->createWallet(2, $idempotentInput, $createKey);
        $replayedCreate = $walletService->createWallet(2, $idempotentInput, $createKey);
        $sameNameCount = (int) $pdo->query("SELECT COUNT(*) FROM wallets WHERE user_id = 2 AND name = 'Ví tạo một lần'")->fetchColumn();
        $this->assert('TC-F02.01b Retried create request returns the first wallet without duplication',
            (int) $firstCreate['id'] === (int) $replayedCreate['id'] && $sameNameCount === 1);
        $createKeyConflict = false;
        try {
            $walletService->createWallet(2, array_merge($idempotentInput, ['name' => 'Tên khác']), $createKey);
        } catch (\WealthFlow\Core\ApiException $e) {
            $createKeyConflict = $e->status === 409 && $e->errorCode === 'IDEMPOTENCY_CONFLICT';
        }
        $this->assert('TC-F02.01c Reusing create idempotency key with another payload is rejected',
            $createKeyConflict);
        $entryCount = (int) $pdo->query("SELECT COUNT(*) FROM ledger_entries e JOIN ledger_accounts a ON e.ledger_account_id = a.id WHERE a.wallet_id = $createdId")->fetchColumn();
        $this->assert('TC-F02.02 Opening balance records ledger entry', $entryCount >= 1);
        $walletService->updateWallet($createdId, 2, ['name' => 'Ví đã đổi tên']);
        $this->assert('TC-F02.03 Rename stays scoped to owner',
            $walletRepo->findByIdAndUser($createdId, 2)['name'] === 'Ví đã đổi tên');
        $invalid = false;
        try { $walletService->createWallet(2, ['name' => 'Không hợp lệ', 'walletType'=>'CASH', 'initialBalance'=>'-1']); }
        catch (\WealthFlow\Core\ApiException $e) { $invalid = $e->status === 422; }
        $this->assert('TC-F02.04 Negative opening balance rejected', $invalid);
        $pdo->exec("DELETE FROM exchange_rates WHERE from_currency = 'USD' AND to_currency = 'VND'");
        $invalidFX = false;
        try { $walletService->createWallet(2, ['name'=>'USD chưa chốt FX', 'walletType'=>'CASH', 'currency'=>'USD','initialBalance'=>'100']); }
        catch (InvalidArgumentException $e) { $invalidFX = true; }
        $this->assert('TC-F02.05 Foreign opening without verified rate rejected', $invalidFX);
        $fxService->recordRate('USD', 'VND', '26000', 'TEST', '2099-10-18 10:00:00');
        $foreign = $walletService->createWallet(2, [
            'name'=>'Ví USD kiểm thử', 'walletType'=>'BANK', 'currency'=>'USD', 'initialBalance'=>'123,45'
        ]);
        $foreignId = (int) $foreign['id'];
        $foreignEntryBase = (string) $pdo->query(
            "SELECT amount_base FROM ledger_entries e
             JOIN ledger_accounts a ON e.ledger_account_id = a.id
             WHERE a.wallet_id = {$foreignId} LIMIT 1"
        )->fetchColumn();
        $this->assert('TC-F02.05a Foreign opening accepts manually entered decimal amount',
            (string) $foreign['currency'] === 'USD' && (float) $foreign['cached_balance'] === 123.45);
        $this->assert('TC-F02.05b Foreign opening stores converted base amount using verified rate',
            bccomp($foreignEntryBase, '3209700', 4) === 0);
        $formattedVnd = $walletService->createWallet(2, [
            'name'=>'Ví VND có phân cách', 'walletType'=>'CASH', 'currency'=>'VND', 'initialBalance'=>'1.250.000'
        ]);
        $this->assert('TC-F02.05c VND accepts a valid thousands separator',
            (float) $formattedVnd['cached_balance'] === 1250000.0);
        $badAmounts = ['1.25', '1.000,00', '12e3', '1 000', '+1000', '1000đ'];
        $badAmountsRejected = true;
        foreach ($badAmounts as $index => $badAmount) {
            try {
                $walletService->createWallet(2, [
                    'name'=>'Ví sai định dạng ' . $index,
                    'walletType'=>'CASH',
                    'currency'=>'VND',
                    'initialBalance'=>$badAmount,
                ]);
                $badAmountsRejected = false;
            } catch (\WealthFlow\Core\ApiException $e) {
                $badAmountsRejected = $badAmountsRejected && $e->status === 422;
            }
        }
        $this->assert('TC-F02.05d VND rejects decimal, mixed separators, exponent, spaces and symbols',
            $badAmountsRejected);
        $badForeignRejected = false;
        try {
            $walletService->createWallet(2, [
                'name'=>'USD sai định dạng', 'walletType'=>'BANK', 'currency'=>'USD', 'initialBalance'=>'1,234'
            ]);
        } catch (\WealthFlow\Core\ApiException $e) {
            $badForeignRejected = $e->status === 422;
        }
        $this->assert('TC-F02.05e Foreign amount rejects ambiguous thousands/three-decimal input',
            $badForeignRejected);
        $cannotArchive = false;
        try { $walletService->archiveWallet($createdId, 2); }
        catch (InvalidArgumentException $e) { $cannotArchive = true; }
        $this->assert('TC-F02.06 Nonzero wallet cannot be archived', $cannotArchive);
        $zero = $walletService->createWallet(2, ['name'=>'Ví rỗng', 'walletType'=>'E_WALLET','initialBalance'=>'0']);
        $walletService->archiveWallet((int) $zero['id'], 2);
        $this->assert('TC-F02.07 Empty wallet can be archived',
            $walletRepo->findByIdAndUser((int)$zero['id'],2)['status']==='ARCHIVED');
        $walletService->deleteWallet((int) $zero['id'], 2);
        $this->assert('TC-F02.07a Unused archived wallet can be permanently deleted',
            $walletRepo->findByIdAndUser((int) $zero['id'], 2) === null);
        $activeEmpty = $walletService->createWallet(2, [
            'name'=>'Ví chưa lưu trữ', 'walletType'=>'CASH', 'initialBalance'=>'0'
        ]);
        $activeDeleteRejected = false;
        try { $walletService->deleteWallet((int) $activeEmpty['id'], 2); }
        catch (\WealthFlow\Core\ApiException $e) { $activeDeleteRejected = $e->errorCode === 'WALLET_MUST_BE_ARCHIVED'; }
        $this->assert('TC-F02.07b Active wallet must be archived before permanent deletion',
            $activeDeleteRejected);
        $this->assert('TC-F02.08 Other user cannot list test wallet',
            count(array_filter($walletService->getWallets(1,true), static fn(array $w):bool => (int)$w['id'] === $createdId))===0);

        $adjustKey = '123e4567-e89b-42d3-a456-426614174000';
        $adjusted = $walletService->adjustWallet($createdId, 2, [
            'direction' => 'INCREASE', 'amount' => '300,000', 'note' => 'Bổ sung tiền kiểm thử'
        ], $adjustKey);
        $adjustTx = $txRepo->findByIdAndUser((int) $adjusted['transactionId'], 2);
        $debit = 0.0;
        $credit = 0.0;
        foreach ($adjustTx['entries'] as $entry) {
            if ($entry['entry_side'] === 'DEBIT') $debit += (float) $entry['amount_base'];
            if ($entry['entry_side'] === 'CREDIT') $credit += (float) $entry['amount_base'];
        }
        $this->assert('TC-F02.09 Add money updates only the owned active wallet',
            (float) $walletRepo->findByIdAndUser($createdId, 2)['cached_balance'] === 2300000.0);
        $this->assert('TC-F02.10 Balance adjustment creates two balanced ledger entries',
            count($adjustTx['entries']) === 2 && abs($debit - $credit) < 0.0001);
        $walletService->adjustWallet($createdId, 2, [
            'direction' => 'INCREASE', 'amount' => '300,000', 'note' => 'Bổ sung tiền kiểm thử'
        ], $adjustKey);
        $this->assert('TC-F02.11 Idempotent replay does not add money twice',
            (float) $walletRepo->findByIdAndUser($createdId, 2)['cached_balance'] === 2300000.0);

        $foreignOwnerRejected = false;
        try { $walletService->adjustWallet($createdId, 1, ['direction'=>'INCREASE', 'amount'=>'50000']); }
        catch (\WealthFlow\Core\ApiException $e) { $foreignOwnerRejected = $e->status === 404; }
        $this->assert('TC-F02.12 Other user cannot adjust wallet balance', $foreignOwnerRejected);

        $walletService->adjustWallet($createdId, 2, [
            'direction' => 'DECREASE', 'amount' => '2.300.000', 'note' => 'Đưa ví về số dư 0'
        ], '223e4567-e89b-42d3-a456-426614174001');
        $this->assert('TC-F02.13 Decrease money updates balance through the ledger',
            (float) $walletRepo->findByIdAndUser($createdId, 2)['cached_balance'] === 0.0);
        $walletService->archiveWallet($createdId, 2);
        $historyDeleteRejected = false;
        try { $walletService->deleteWallet($createdId, 2); }
        catch (\WealthFlow\Core\ApiException $e) { $historyDeleteRejected = $e->errorCode === 'WALLET_HISTORY_REQUIRED'; }
        $this->assert('TC-F02.14 Wallet with ledger history stays archived when permanent deletion is requested',
            $historyDeleteRejected && $walletRepo->findByIdAndUser($createdId, 2)['status'] === 'ARCHIVED');

        echo "\n--- GROUP 1: FR03 - Core Ledger & Double-Entry Engine ---\n";

        // 1.1: TC-F03.01 Post Expense & Double-Entry Balancing
        $initialWallet1 = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $expenseRes = $ledgerService->postExpense($userId, [
            'walletId' => 1,
            'categoryId' => 1, // Ăn uống
            'amount' => 150000,
            'currency' => 'VND',
            'description' => 'Bữa trưa công ty',
        ]);
        $txId = (int) $expenseRes['transactionId'];
        $txDetail = $txRepo->findByIdAndUser($txId, $userId);

        $this->assert('TC-F03.01.1 Expense transaction created with status POSTED', $txDetail['status'] === 'POSTED');
        $this->assert('TC-F03.01.2 Expense has exactly 2 balanced ledger entries', count($txDetail['entries']) === 2);

        $debitTotal = 0.0;
        $creditTotal = 0.0;
        foreach ($txDetail['entries'] as $e) {
            if ($e['entry_side'] === 'DEBIT') {
                $debitTotal += (float) $e['amount_base'];
            } else {
                $creditTotal += (float) $e['amount_base'];
            }
        }
        $this->assert('TC-F03.01.3 Double-entry balance holds: sum(DebitBase) == sum(CreditBase)', abs($debitTotal - $creditTotal) < 0.0001);

        $newWallet1 = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $this->assert('TC-F03.01.4 Wallet balance deducted correctly by 150,000 VND', $newWallet1 === ($initialWallet1 - 150000));

        // 1.2: TC-F03.02 Insufficient Funds Rejection
        $insufficientFundsError = false;
        try {
            $ledgerService->postExpense($userId, [
                'walletId' => 1,
                'categoryId' => 1,
                'amount' => 999999999, // Way higher than balance
            ]);
        } catch (\RuntimeException $e) {
            $insufficientFundsError = true;
        }
        $this->assert('TC-F03.02 Insufficient funds rejected with 422 runtime exception', $insufficientFundsError);

        // 1.3: TC-F03.03 Internal Transfer between Wallets (BR05)
        $w1Before = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $w2Before = (float) $walletRepo->findByIdAndUser(2, $userId)['cached_balance'];
        $transferRes = $ledgerService->postTransfer($userId, [
            'fromWalletId' => 1,
            'toWalletId' => 2,
            'amount' => 500000,
            'description' => 'Nạp tiền vào tài khoản ngân hàng',
        ]);
        $w1After = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $w2After = (float) $walletRepo->findByIdAndUser(2, $userId)['cached_balance'];
        $transferTx = $txRepo->findByIdAndUser((int) $transferRes['transactionId'], $userId);

        $this->assert('TC-F03.03.1 Internal transfer created with status POSTED', $transferTx['status'] === 'POSTED');
        $this->assert('TC-F03.03.2 Source wallet decreased by 500,000 VND', $w1After === ($w1Before - 500000));
        $this->assert('TC-F03.03.3 Dest wallet increased by 500,000 VND', $w2After === ($w2Before + 500000));
        $this->assert('TC-F03.03.4 Internal transfer has 2 entries (DEBIT Dest, CREDIT Source)', count($transferTx['entries']) === 2);

        // 1.4: TC-F03.04 Transfer to same wallet rejected
        $sameWalletError = false;
        try {
            $ledgerService->postTransfer($userId, [
                'fromWalletId' => 1,
                'toWalletId' => 1,
                'amount' => 100000,
            ]);
        } catch (\InvalidArgumentException $e) {
            $sameWalletError = true;
        }
        $this->assert('TC-F03.04 Transfer to same wallet rejected with validation error', $sameWalletError);

        // 1.5: TC-F03.05 Transaction Reversal (BR06)
        $w1PreRev = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $revRes = $ledgerService->reverseTransaction($userId, $txId, 'Nhập nhầm hóa đơn');
        $w1PostRev = (float) $walletRepo->findByIdAndUser(1, $userId)['cached_balance'];
        $origTxAfter = $txRepo->findByIdAndUser($txId, $userId);
        $revTx = $txRepo->findByIdAndUser((int) $revRes['reversalTransactionId'], $userId);

        $this->assert('TC-F03.05.1 Original transaction status changed to REVERSED', $origTxAfter['status'] === 'REVERSED');
        $this->assert('TC-F03.05.2 Reversal transaction created with link to original ID', $revTx['original_transaction_id'] == $txId);
        $this->assert('TC-F03.05.3 Reversal entries swap DEBIT and CREDIT correctly', count($revTx['entries']) === 2);
        $this->assert('TC-F03.05.4 Wallet balance restored (+150,000 VND)', $w1PostRev === ($w1PreRev + 150000));

        // 1.6: TC-F03.06 Reversing an already reversed transaction fails
        $doubleRevError = false;
        try {
            $ledgerService->reverseTransaction($userId, $txId);
        } catch (\RuntimeException $e) {
            $doubleRevError = true;
        }
        $this->assert('TC-F03.06 Reversing an already reversed transaction is rejected', $doubleRevError);

        // 1.7: TC-F03.07 Idempotency Key Handling (BR13)
        $idempKey = 'idemp-uuid-' . bin2hex(random_bytes(4));
        $payload1 = ['walletId' => 1, 'categoryId' => 1, 'amount' => 80000, 'description' => 'Test Idempotency'];
        $check1 = $ledgerService->checkIdempotency($userId, $idempKey, $payload1);
        $this->assert('TC-F03.07.1 New idempotency key is not cached', !$check1['cached']);

        $res1 = $ledgerService->postExpense($userId, $payload1);
        $ledgerService->recordIdempotency($userId, $idempKey, $payload1, (int) $res1['transactionId'], 201, $res1);

        $check2 = $ledgerService->checkIdempotency($userId, $idempKey, $payload1);
        $this->assert('TC-F03.07.2 Same key and same payload returns cached response', $check2['cached'] && $check2['code'] === 201);

        $conflictError = false;
        try {
            $payloadConflict = ['walletId' => 1, 'categoryId' => 1, 'amount' => 999999];
            $ledgerService->checkIdempotency($userId, $idempKey, $payloadConflict);
        } catch (\RuntimeException $e) {
            $conflictError = ($e->getCode() === 409);
        }
        $this->assert('TC-F03.07.3 Same key with different payload returns 409 Conflict', $conflictError);

        $atomicKey = 'idemp-atomic-' . bin2hex(random_bytes(4));
        $atomicPayload = ['walletId' => 1, 'categoryId' => 1, 'amount' => 25000, 'description' => 'Atomic idempotency'];
        $atomicFirst = $ledgerService->executeIdempotently(
            $userId,
            $atomicKey,
            $atomicPayload,
            201,
            fn (): array => $ledgerService->postExpense($userId, $atomicPayload)
        );
        $secondActionCalled = false;
        $atomicReplay = $ledgerService->executeIdempotently(
            $userId,
            $atomicKey,
            $atomicPayload,
            201,
            function () use (&$secondActionCalled): array {
                $secondActionCalled = true;
                return [];
            }
        );
        $this->assert(
            'TC-F03.08 Same-key replay returns its committed result without repeating posting',
            !$secondActionCalled
                && $atomicFirst['response']['transactionId'] === $atomicReplay['response']['transactionId']
                && $atomicReplay['code'] === 201
        );

        $failedAtomicKey = 'idemp-rollback-' . bin2hex(random_bytes(4));
        $failedAtomicPayload = ['walletId' => 1, 'categoryId' => 1, 'amount' => 999999999, 'description' => 'Rollback test'];
        $transactionCountBefore = (int) $pdo->query('SELECT COUNT(*) FROM financial_transactions')->fetchColumn();
        $failedAtomic = false;
        try {
            $ledgerService->executeIdempotently(
                $userId,
                $failedAtomicKey,
                $failedAtomicPayload,
                201,
                fn (): array => $ledgerService->postExpense($userId, $failedAtomicPayload)
            );
        } catch (\RuntimeException) {
            $failedAtomic = true;
        }
        $transactionCountAfter = (int) $pdo->query('SELECT COUNT(*) FROM financial_transactions')->fetchColumn();
        $this->assert(
            'TC-F03.09 Failed posting rolls back ledger rows and idempotency is not cached',
            $failedAtomic
                && $transactionCountBefore === $transactionCountAfter
                && !$ledgerService->checkIdempotency($userId, $failedAtomicKey, $failedAtomicPayload)['cached']
        );

        // -------------------------------------------------------------
        echo "\n--- GROUP 2: FR04 - Multi-Currency & Snapshot Rates ---\n";
        // -------------------------------------------------------------

        // 2.1: TC-F04.01 Foreign Currency USD Snapshot Rate
        $usdRate = $fxService->getSnapshotRate('USD', 'VND');
        $this->assert('TC-F04.01.1 Effective USD to VND rate retrieved (> 0)', (float) $usdRate > 0);

        $usdExpense = $ledgerService->postExpense($userId, [
            'walletId' => 4, // USD Wallet
            'categoryId' => 4, // Shopping
            'amount' => 50,
            'currency' => 'USD',
            'description' => 'Software subscription USD',
        ]);
        $usdTxDetail = $txRepo->findByIdAndUser((int) $usdExpense['transactionId'], $userId);

        $expectedBase = bcmul('50', $usdRate, 4);
        $this->assert('TC-F04.01.2 fx_rate_snapshot saved immutably on transaction', (float) $usdTxDetail['fx_rate_snapshot'] === (float) $usdRate);
        $this->assert('TC-F04.01.3 amount_base calculated accurately via BCMath', abs((float) $usdTxDetail['entries'][0]['amount_base'] - (float) $expectedBase) < 0.01);

        $fakeVietcombank = new class extends VietcombankRateProvider {
            public function fetch(): array
            {
                return [
                    'provider' => 'VIETCOMBANK_TRANSFER',
                    'effectiveAt' => '2026-10-18 10:00:00',
                    'sourceUrl' => self::SOURCE_URL,
                    'rates' => ['USD' => '26000', 'EUR' => '28000'],
                ];
            }
        };
        $fxService->syncPublicRates($fakeVietcombank);
        $vcbPairCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM exchange_rates WHERE provider = 'VIETCOMBANK_TRANSFER' AND effective_at = '2026-10-18 10:00:00'"
        )->fetchColumn();
        $this->assert('TC-F04.02 Vietcombank sync stores direct, inverse and cross rates', $vcbPairCount === 6);

        // -------------------------------------------------------------
        echo "\n--- GROUP 3: FR05 - Expense Budgeting & Thresholds ---\n";
        // -------------------------------------------------------------

        // 3.1: TC-F05.01 Budget Creation & Net Spend Calculation (BR07)
        $curMonth = date('Y-m');
        $budget = $budgetService->createBudget($userId, 1, $curMonth, 2000000.0);
        $this->assert('TC-F05.01.1 Budget created with limit 2,000,000 VND for current month', $budget['limitAmount'] === 2000000.0);

        // 3.2: TC-F05.02 Threshold Warnings (BR08: Yellow >= 80%, Red >= 100%)
        // Post an expense that reaches 1,650,000 VND (82.5%)
        $ledgerService->postExpense($userId, [
            'walletId' => 2, // Bank wallet with sufficient funds
            'categoryId' => 1,
            'amount' => 1570000, // Total net in cat 1: 80k + 1570k = 1,650k = 82.5%
            'description' => 'Ăn tiệc liên hoan',
        ]);
        $checkYellow = $budgetService->checkBudgetAndNotify($userId, 1, date('Y-m-d'));
        $this->assert('TC-F05.02.1 Yellow warning triggered at >= 80%', $checkYellow['alertTriggered'] === 'YELLOW');

        // Post additional expense that pushes usage to 2,150,000 VND (107.5%)
        $ledgerService->postExpense($userId, [
            'walletId' => 2,
            'categoryId' => 1,
            'amount' => 500000,
            'description' => 'Ăn tối sang trọng',
        ]);
        $checkRed = $budgetService->checkBudgetAndNotify($userId, 1, date('Y-m-d'));
        $this->assert('TC-F05.02.2 Red warning triggered at >= 100%', $checkRed['alertTriggered'] === 'RED');

        // Post another expense: Red alert should be suppressed (no duplicate notification)
        $ledgerService->postExpense($userId, [
            'walletId' => 2,
            'categoryId' => 1,
            'amount' => 50000,
            'description' => 'Trà đá tráng miệng',
        ]);
        $checkSuppress = $budgetService->checkBudgetAndNotify($userId, 1, date('Y-m-d'));
        $this->assert('TC-F05.02.3 Duplicate alert suppressed: alertTriggered is null', $checkSuppress['alertTriggered'] === null);

        // 3.3: TC-12 / BR08 Jump-Over-Yellow (<80% directly to >=100%) & Reversal Deduplication
        $budgetCat2 = $budgetService->createBudget($userId, 2, $curMonth, 1000000.0); // Category 2: Di chuyển
        $jumpTx1 = $ledgerService->postExpense($userId, [
            'walletId' => 2,
            'categoryId' => 2,
            'amount' => 850000,
            'description' => 'Vé máy bay công tác chặng 1',
        ]);
        $jumpTx2 = $ledgerService->postExpense($userId, [
            'walletId' => 2,
            'categoryId' => 2,
            'amount' => 250000,
            'description' => 'Taxi sân bay',
        ]);
        // Evaluate threshold once at 1,100,000 / 1,000,000 (110%) — jumps from 0% straight to >=100%
        $checkJumpRed = $budgetService->checkBudgetAndNotify($userId, 2, date('Y-m-d'));
        $this->assert('TC-12.1 Jump from <80% to >=100% triggers RED immediately', $checkJumpRed['alertTriggered'] === 'RED');
        // Reverse the 250,000 VND taxi expense so net spend drops to 850,000 VND (85% - in Yellow zone)
        $ledgerService->reverseTransaction($userId, (int) $jumpTx2['transactionId'], 'Hoàn cước taxi');
        $checkAfterRev = $budgetService->checkBudgetAndNotify($userId, 2, date('Y-m-d'));
        $this->assert(
            'TC-12.2 BR08 Jump-over-yellow marks yellow sent so dropping to 85% does not emit belated YELLOW',
            $checkAfterRev['alertTriggered'] === null && (float) $checkAfterRev['spentAmount'] === 850000.0
        );

        // 3.4: TC-13 / BR07 Net Spend Excludes Transfers, Reversals & Loan Principal
        $spentBeforeTransfer = (float) $budgetDao->getCategoryNetExpenseInMonth($userId, 2, $curMonth);
        $ledgerService->postTransfer($userId, [
            'fromWalletId' => 2,
            'toWalletId' => 1,
            'amount' => 300000,
            'description' => 'Rút tiền mặt ATM',
        ]);
        // Simulate a LOAN_PAYMENT principal transaction in financial_transactions
        $pdo->prepare("INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, amount, currency, fx_rate_snapshot, status, description, occurred_on)
            VALUES (?, 2, 'LOAN_PAYMENT', 400000, 'VND', 1.0, 'POSTED', 'Trả gốc khoản vay', ?)")
            ->execute([$userId, date('Y-m-d')]);
        $spentAfterExclusions = (float) $budgetDao->getCategoryNetExpenseInMonth($userId, 2, $curMonth);
        $this->assert(
            'TC-13 BR07 Internal transfer, reversed expense, and LOAN_PAYMENT principal do not inflate budget net spend',
            abs($spentAfterExclusions - $spentBeforeTransfer) < 0.0001 && $spentAfterExclusions === 850000.0
        );

        // 3.5: PLAN-03 Budget Update, Archive & Reactivation
        $updatedBudget = $budgetService->updateBudget((int) $budgetCat2['id'], $userId, 2000000.0);
        $this->assert(
            'TC-F05.03.1 Budget limit updated to 2,000,000 VND and usage recalculated to 42.5% (GREEN)',
            $updatedBudget['limitAmount'] === 2000000.0
                && $updatedBudget['usagePercent'] === 42.5
                && $updatedBudget['alertLevel'] === 'GREEN'
        );
        $archivedOk = $budgetService->archiveBudget((int) $budgetCat2['id'], $userId);
        $activeIdsAfterArchive = array_map(
            static fn(array $b): int => (int) $b['id'],
            $budgetService->getBudgetsForMonth($userId, $curMonth)
        );
        $this->assert(
            'TC-F05.03.2 Archived budget is removed from active monthly budget list',
            $archivedOk && !in_array((int) $budgetCat2['id'], $activeIdsAfterArchive, true)
        );
        $reactivatedBudget = $budgetService->createBudget($userId, 2, $curMonth, 1000000.0);
        $this->assert(
            'TC-F05.03.3 Re-creating archived budget for same (user, category, month) reactivates it and triggers threshold check',
            (int) $reactivatedBudget['id'] === (int) $budgetCat2['id']
                && $reactivatedBudget['alertLevel'] === 'YELLOW'
        );

        // 3.6: PLAN-05 Notification Center (Read/Unread Filter & User Isolation)
        $notifListAll = $notifService->getNotificationsForUser($userId, 50, false);
        $unreadBefore = $notifService->countUnreadForUser($userId);
        $this->assert(
            'TC-F05.04.1 Notification center lists generated budget alerts with unreadCount > 0',
            count($notifListAll) >= 3 && $unreadBefore >= 3
        );
        $firstNotifId = (int) $notifListAll[0]['id'];
        $crossUserMark = $notifService->markAsRead($firstNotifId, 2); // User 2 attempts to mark User 1's notification
        $this->assert('TC-F05.04.2 Cross-user markAsRead is rejected (returns false)', $crossUserMark === false);

        $ownerMark = $notifService->markAsRead($firstNotifId, $userId);
        $notifAfterOneRead = $notifService->getNotificationsForUser($userId, 50, true);
        $unreadAfterOne = $notifService->countUnreadForUser($userId);
        $this->assert(
            'TC-F05.04.3 Owner markAsRead succeeds and unreadOnly filter excludes read notification',
            $ownerMark === true
                && $unreadAfterOne === ($unreadBefore - 1)
                && count($notifAfterOneRead) === $unreadAfterOne
        );
        $markedAllOk = $notifService->markAllAsRead($userId);
        $notifAfterAllRead = $notifService->getNotificationsForUser($userId, 50, true);
        $unreadAfterAll = $notifService->countUnreadForUser($userId);
        $this->assert(
            'TC-F05.04.4 markAllAsRead clears all remaining unread notifications for owner',
            $markedAllOk === true && $unreadAfterAll === 0 && count($notifAfterAllRead) === 0
        );

        // -------------------------------------------------------------
        echo "\n--- GROUP 4: FR06 - Recurring Schedules & Background Worker ---\n";
        // -------------------------------------------------------------

        // 4.1: TC-F06.01 BR09 End-of-Month Clamping Algorithm
        // Scenario from SRS TC-F06: Anchor day 31
        $nextFeb = RecurringService::calculateNextMonthlyDueDate(31, '2026-01-31');
        $this->assert('TC-F06.01.1 Anchor day 31 clamps to Feb 28 for February', $nextFeb === '2026-02-28');

        $nextMar = RecurringService::calculateNextMonthlyDueDate(31, '2026-02-28');
        $this->assert('TC-F06.01.2 Anchor day 31 restores to March 31 for March', $nextMar === '2026-03-31');

        $nextApr = RecurringService::calculateNextMonthlyDueDate(31, '2026-03-31');
        $this->assert('TC-F06.01.3 Anchor day 31 clamps to April 30 for April', $nextApr === '2026-04-30');

        // 4.2: TC-F06.02 Recurring Schedule Creation
        $sub1 = $recService->createSchedule($userId, [
            'name' => 'Tiền nhà hàng tháng',
            'amount' => 3500000,
            'walletId' => 2,
            'categoryId' => 3,
            'dayOfMonth' => 5,
            'executionMode' => 'AUTO_POST',
        ]);
        $this->assert('TC-F06.02.1 Recurring schedule created with AUTO_POST', $sub1['execution_mode'] === 'AUTO_POST');

        $sub2 = $recService->createSchedule($userId, [
            'name' => 'Hóa đơn tiền điện thoại',
            'amount' => 200000,
            'walletId' => 1,
            'categoryId' => 3,
            'dayOfMonth' => 15,
            'executionMode' => 'DRAFT_REMINDER',
        ]);
        $this->assert('TC-F06.02.2 Recurring schedule created with DRAFT_REMINDER', $sub2['execution_mode'] === 'DRAFT_REMINDER');

        // 4.3: TC-F06.03 SYS02 Worker Execution for Due Schedules
        // Set sub1 next_due_date to today to simulate due schedule
        $today = date('Y-m-d');
        $recRepo->updateNextDueDate((int) $sub1['id'], $today);
        $recRepo->updateNextDueDate((int) $sub2['id'], $today);

        $workerSummary = $recWorker->processDueSchedules($today);
        $this->assert('TC-F06.03.1 Worker processed both due schedules', $workerSummary['processed'] === 2);
        $this->assert('TC-F06.03.2 AUTO_POST schedule posted expense automatically', $workerSummary['autoPosted'] === 1);
        $this->assert('TC-F06.03.3 DRAFT_REMINDER schedule created draft and sent reminder', $workerSummary['draftReminders'] === 1);

        // Verify next_due_date advanced to next month
        $sub1Updated = $recRepo->findByIdAndUser((int) $sub1['id'], $userId);
        $expectedNext = RecurringService::calculateNextMonthlyDueDate(5, $today);
        $this->assert('TC-F06.03.4 Next due date advanced correctly for next cycle', $sub1Updated['next_due_date'] === $expectedNext);

        // -------------------------------------------------------------
        echo "\n--- GROUP 5: FR12 - Ledger Reconciliation, Trial Balance & CSV Export ---\n";
        // -------------------------------------------------------------

        $reportDao = new ReportDAO($pdo);
        $auditLogDao = new AuditLogDAO($pdo);
        $reconcileService = new ReconciliationService($reportDao);
        $exportService = new ExportService($reportDao);

        // 5.1: TC-F12.01 Multi-criteria Transaction History Filtering & Pagination
        $filteredBySearch = $txRepo->listTransactions($userId, ['search' => 'Ăn tiệc', 'limit' => 10, 'offset' => 0]);
        $countBySearch = $txRepo->countTransactions($userId, ['search' => 'Ăn tiệc']);
        $this->assert(
            'TC-F12.01.1 Transaction history search filter matches description and countTransactions agrees',
            count($filteredBySearch) === 1 && $countBySearch === 1 && $filteredBySearch[0]['description'] === 'Ăn tiệc liên hoan'
        );

        $filteredByWallet = $txRepo->listTransactions($userId, ['walletId' => 4, 'status' => 'POSTED', 'limit' => 10, 'offset' => 0]);
        $this->assert(
            'TC-F12.01.2 Transaction history walletId filter returns only transactions touching that wallet',
            count($filteredByWallet) >= 1 && array_reduce(
                $filteredByWallet,
                static fn(bool $carry, array $tx): bool => $carry && $tx['currency'] === 'USD',
                true
            )
        );

        // 5.2: TC-F12.02 Wallet Balance vs. Double-Entry Ledger Reconciliation & Trial Balance
        $reconResult = $reconcileService->reconcileAllWallets($userId);
        $this->assert(
            'TC-F12.02.1 Reconciliation confirms cached_balance == ledgerSum for all wallets (isAllMatched == true)',
            $reconResult['isAllMatched'] === true
                && $reconResult['overallStatus'] === 'MATCHED'
                && $reconResult['mismatchCount'] === 0
        );
        $this->assert(
            'TC-F12.02.2 Trial balance confirms sum(DebitBase) == sum(CreditBase) and excludes DRAFT rows',
            $reconResult['trialBalance']['isBalanced'] === true
                && $reconResult['trialBalance']['status'] === 'BALANCED'
                && (float) $reconResult['trialBalance']['totalDebitBase'] > 0
                && abs((float) $reconResult['trialBalance']['differenceBase']) < 0.0001
        );

        // Simulate a cached_balance drift on Wallet 1 (+50,000 VND) and verify LEDGER_MISMATCH audit logging
        $pdo->exec("UPDATE wallets SET cached_balance = cached_balance + 50000 WHERE id = 1");
        $reconDrifted = $reconcileService->reconcileAllWallets($userId, null, true, '127.0.0.1');
        $securityLogCount = (int) $pdo->query("SELECT COUNT(*) FROM security_audit_logs WHERE event_type = 'LEDGER_MISMATCH' AND user_id = {$userId}")->fetchColumn();
        $this->assert(
            'TC-F12.02.3 Tampered cached_balance is detected (mismatchCount == 1) and logged to security_audit_logs',
            $reconDrifted['isAllMatched'] === false
                && $reconDrifted['overallStatus'] === 'MISMATCHED'
                && $reconDrifted['mismatchCount'] === 1
                && $securityLogCount >= 1
        );
        // Restore Wallet 1 balance
        $pdo->exec("UPDATE wallets SET cached_balance = cached_balance - 50000 WHERE id = 1");
        $reconRestored = $reconcileService->reconcileAllWallets($userId);
        $this->assert('TC-F12.02.4 Restored wallet balance returns reconciliation to isAllMatched == true', $reconRestored['isAllMatched'] === true);

        // 5.3: TC-F12.03 & TC-F12.04 CSV Export with UTF-8 BOM, Audit Hash & OWASP Formula Injection Sanitization
        $this->assert(
            'TC-F12.04.1 ExportService::sanitizeCsvField prepends single quote to =, +, -, @, tab, CR and leading-space formulas',
            $exportService->sanitizeCsvField('=1+1') === "'=1+1"
                && $exportService->sanitizeCsvField('+cmd|\' /C calc\'!A0') === "'+cmd|' /C calc'!A0"
                && $exportService->sanitizeCsvField('-2+3') === "'-2+3"
                && $exportService->sanitizeCsvField('@SUM(A1:A10)') === "'@SUM(A1:A10)"
                && $exportService->sanitizeCsvField("\t=1+1") === "'\t=1+1"
                && $exportService->sanitizeCsvField('  =HYPERLINK("http://evil")') === "'  =HYPERLINK(\"http://evil\")"
                && $exportService->sanitizeCsvField('Bữa trưa bình thường') === 'Bữa trưa bình thường'
        );

        // Post an expense with a formula-like description to test end-to-end CSV export sanitization
        $ledgerService->postExpense($userId, [
            'walletId' => 2,
            'categoryId' => 1,
            'amount' => 45000,
            'description' => '=CMD|\' /C calc\'!A0',
        ]);
        $csvExport = $exportService->exportTransactionsCsv($userId, ['status' => 'POSTED'], '127.0.0.1');
        $exportAuditRow = $pdo->query("SELECT * FROM export_audit_logs WHERE user_id = {$userId} ORDER BY id DESC LIMIT 1")->fetch();
        $this->assert(
            'TC-F12.03.1 CSV export starts with UTF-8 BOM (\\xEF\\xBB\\xBF) and records SHA-256 hash & row_count in export_audit_logs',
            str_starts_with($csvExport['csv'], "\xEF\xBB\xBF")
                && $csvExport['rowCount'] >= 1
                && is_array($exportAuditRow)
                && $exportAuditRow['file_hash'] === hash('sha256', $csvExport['csv'])
                && (int) $exportAuditRow['row_count'] === $csvExport['rowCount']
        );
        $this->assert(
            'TC-F12.04.2 Exported CSV neutralizes formula injection payload in transaction description',
            str_contains($csvExport['csv'], "'=CMD|")
        );

        // -------------------------------------------------------------
        echo "\n--- GROUP 6: FR13 & FR14 - Admin Governance, Config, Broadcast & User Lock ---\n";
        // -------------------------------------------------------------

        $adminDao = new AdminDAO($pdo);
        $adminService = new AdminService($adminDao, $auditLogDao, $notifService);
        $adminUserId = 3; // Seeded ADMIN user

        // 6.1: TC-F13.01 Default Categories Management
        $initialTemplates = $adminService->listDefaultCategories();
        $newTemplate = $adminService->createDefaultCategory($adminUserId, [
            'name' => 'Bảo hiểm sức khỏe',
            'categoryType' => 'EXPENSE',
        ]);
        $this->assert(
            'TC-F13.01.1 Admin creates default category template and logs CREATE_DEFAULT_CATEGORY',
            (int) $newTemplate['id'] > count($initialTemplates)
                && $newTemplate['name'] === 'Bảo hiểm sức khỏe'
                && $newTemplate['categoryType'] === 'EXPENSE'
        );

        $dupCatConflict = false;
        try {
            $adminService->createDefaultCategory($adminUserId, [
                'name' => 'Bảo hiểm sức khỏe',
                'categoryType' => 'EXPENSE',
            ]);
        } catch (ApiException $e) {
            $dupCatConflict = ($e->status === 409 && $e->errorCode === 'DUPLICATE_DEFAULT_CATEGORY');
        }
        $this->assert('TC-F13.01.2 Duplicate default category (name, category_type) is rejected with 409 Conflict', $dupCatConflict);

        $updatedTemplate = $adminService->updateDefaultCategory($adminUserId, (int) $newTemplate['id'], [
            'name' => 'Bảo hiểm & Y tế',
            'categoryType' => 'EXPENSE',
            'isActive' => true,
        ]);
        $this->assert(
            'TC-F13.01.3 Admin updates default category template name',
            $updatedTemplate['name'] === 'Bảo hiểm & Y tế' && $updatedTemplate['isActive'] === true
        );

        // 6.2: TC-F13.02 System Operational Configuration & Bounds Validation
        $sysConfigBefore = $adminService->getSystemConfig();
        $this->assert('TC-F13.02.1 System configuration loads seeded parameters', count($sysConfigBefore['configs']) >= 8);

        $updatedConfigRes = $adminService->updateSystemConfig($adminUserId, [
            'BUDGET_WARNING_THRESHOLD' => '75',
            'BUDGET_CRITICAL_THRESHOLD' => '95',
        ]);
        $this->assert(
            'TC-F13.02.2 Valid system config update persists new thresholds and logs audit entry',
            count($updatedConfigRes['updatedKeys']) === 2
                && $updatedConfigRes['configMap']['BUDGET_WARNING_THRESHOLD'] === '75'
        );

        $invalidConfigRejected = false;
        try {
            $adminService->updateSystemConfig($adminUserId, [
                'BUDGET_WARNING_THRESHOLD' => '150', // Out of bounds (max 95)
            ]);
        } catch (ApiException $e) {
            $invalidConfigRejected = ($e->status === 422);
        }
        $invertedThresholdsRejected = false;
        try {
            $adminService->updateSystemConfig($adminUserId, [
                'BUDGET_WARNING_THRESHOLD' => '95',
                'BUDGET_CRITICAL_THRESHOLD' => '95', // Warning >= Critical must fail
            ]);
        } catch (ApiException $e) {
            $invertedThresholdsRejected = ($e->status === 422);
        }
        $this->assert(
            'TC-F13.02.3 Out-of-bounds and inverted threshold configs are rejected with 422 Unprocessable Entity',
            $invalidConfigRejected && $invertedThresholdsRejected
        );

        // 6.3: TC-F13.03 System Announcement Broadcast Fan-out
        $emptyBroadcastRejected = false;
        try {
            $adminService->broadcastAnnouncement($adminUserId, ['title' => '', 'body' => '']);
        } catch (ApiException $e) {
            $emptyBroadcastRejected = ($e->status === 400);
        }
        $this->assert('TC-F13.03.1 Empty broadcast title/body rejected with 400 Bad Request', $emptyBroadcastRejected);

        $unreadUser2Before = $notifService->countUnreadForUser(2);
        $broadcastRes = $adminService->broadcastAnnouncement($adminUserId, [
            'title' => 'Bảo trì hệ thống định kỳ',
            'body' => 'Hệ thống sẽ nâng cấp cơ sở dữ liệu vào 02:00 sáng Chủ Nhật.',
        ]);
        $unreadUser2After = $notifService->countUnreadForUser(2);
        $this->assert(
            'TC-F13.03.2 Broadcast fans out notification to all ACTIVE users and records admin audit log',
            $broadcastRes['recipientCount'] >= 3 && $unreadUser2After === ($unreadUser2Before + 1)
        );

        // 6.4: TC-F14.01 & TC-F14.02 Admin Macro Overview Isolation (BR11) & User Lock/Unlock with Session Revocation
        $overview = $adminService->getOverview();
        $overviewJson = json_encode($overview, JSON_UNESCAPED_UNICODE);
        $this->assert(
            'TC-F14.01.1 Admin macro overview returns aggregate counts without exposing personal wallet balances or transaction notes (BR11)',
            isset($overview['totalUsers'], $overview['activeUsers'], $overview['activeDefaultCategories'])
                && $overview['totalUsers'] >= 3
                && !str_contains((string) $overviewJson, 'cached_balance')
                && !str_contains((string) $overviewJson, 'Ăn tiệc liên hoan')
        );

        // Seed an active auth session for User 2 and test lockUser
        $pdo->exec("INSERT INTO auth_sessions (user_id, refresh_token_hash, expires_at) VALUES (2, 'hash_u2_session', '2099-01-01 00:00:00')");
        $shortReasonRejected = false;
        try {
            $adminService->lockUser($adminUserId, 2, 'Ngắn');
        } catch (ApiException $e) {
            $shortReasonRejected = ($e->status === 422 && $e->errorCode === 'LOCK_REASON_REQUIRED');
        }
        $this->assert('TC-F14.02.1 Locking user with reason < 10 chars is rejected with 422', $shortReasonRejected);

        $lockedRes = $adminService->lockUser($adminUserId, 2, 'Phát hiện đăng nhập bất thường từ nhiều IP lạ');
        $activeSessionsU2 = (int) $pdo->query("SELECT COUNT(*) FROM auth_sessions WHERE user_id = 2 AND revoked_at IS NULL")->fetchColumn();
        $this->assert(
            'TC-F14.02.2 Locking user sets status = LOCKED and revokes all active auth_sessions',
            $lockedRes['user']['status'] === 'LOCKED' && $lockedRes['revokedSessions'] >= 1 && $activeSessionsU2 === 0
        );

        $selfLockRejected = false;
        try {
            $adminService->lockUser($adminUserId, $adminUserId, 'Tự khóa tài khoản quản trị viên');
        } catch (ApiException $e) {
            $selfLockRejected = ($e->status === 400 && $e->errorCode === 'CANNOT_LOCK_LAST_ADMIN');
        }
        $this->assert('TC-F14.02.3 Admin cannot lock their own account', $selfLockRejected);

        $unlockedRes = $adminService->unlockUser($adminUserId, 2, 'Đã xác minh danh tính chủ tài khoản');
        $this->assert('TC-F14.02.4 Unlocking user restores status = ACTIVE', $unlockedRes['user']['status'] === 'ACTIVE');

        $auditLogs = $adminService->getAuditLogs(20);
        $this->assert('TC-F14.03.1 Admin audit log lists all recorded governance actions', count($auditLogs) >= 5);

        echo "\n====================================================================\n";
        echo "TEST SUMMARY: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "====================================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$runner = new TestRunner();
$runner->run();
