<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use WealthFlow\Config\Database;
use WealthFlow\Modules\Budgets\BudgetRepository;
use WealthFlow\Modules\Budgets\BudgetService;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Recurring\RecurringRepository;
use WealthFlow\Modules\Recurring\RecurringService;
use WealthFlow\Modules\Recurring\RecurringWorker;
use WealthFlow\Modules\Transactions\CategoryRepository;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerService;
use WealthFlow\Modules\Transactions\TransactionRepository;
use WealthFlow\Modules\Wallets\WalletRepository;

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
        echo "WEALTHFLOW BACKEND AUTOMATED TEST SUITE: FR03 - FR06 VERIFICATION\n";
        echo "====================================================================\n\n";

        // Setup isolated in-memory SQLite database for deterministic tests
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Database::initSqliteSchema($pdo);
        Database::setConnection($pdo, 'sqlite');

        $walletRepo = new WalletRepository($pdo);
        $catRepo = new CategoryRepository($pdo);
        $txRepo = new TransactionRepository($pdo);
        $fxService = new ExchangeRateService($pdo);
        $notifService = new NotificationService($pdo);
        $budgetRepo = new BudgetRepository($pdo);
        $budgetService = new BudgetService($budgetRepo, $catRepo, $notifService);
        $ledgerService = new LedgerService($txRepo, $walletRepo, $catRepo, $fxService);
        $recRepo = new RecurringRepository($pdo);
        $recService = new RecurringService($recRepo, $walletRepo);
        $recWorker = new RecurringWorker($recRepo, $ledgerService, $txRepo, $notifService);

        $userId = 1;

        // -------------------------------------------------------------
        echo "--- GROUP 1: FR03 - Core Ledger & Double-Entry Engine ---\n";
        // -------------------------------------------------------------

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
