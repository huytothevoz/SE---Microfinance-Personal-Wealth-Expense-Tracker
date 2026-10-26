<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use WealthFlow\Core\ApiException;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Goals\FinancialGoalDAO;
use WealthFlow\Modules\Goals\GoalService;
use WealthFlow\Modules\Loans\LoanDAO;
use WealthFlow\Modules\Loans\LoanService;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\CategoryDAO;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlow\Modules\Wealth\GoldAssetService;
use WealthFlow\Modules\Wealth\GoldHoldingDAO;
use WealthFlow\Modules\Wealth\NetWorthAnalyticsService;
use WealthFlow\Modules\Wealth\NetWorthSnapshotDAO;

final class WealthGoalsTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(string $name, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$name}\n";
            return;
        }
        $this->failed++;
        echo "  [FAIL] {$name}" . ($detail !== '' ? ": {$detail}" : '') . "\n";
    }

    public function run(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            fwrite(STDERR, "[SKIP] pdo_sqlite is required.\n");
            exit(2);
        }
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        Database::initSqliteSchema($pdo);
        $pdo->exec("INSERT INTO users (id, full_name, email, password_hash, role, status, base_currency) VALUES
            (2, 'Wealth Owner', 'wealth.owner@example.test', 'test', 'USER', 'ACTIVE', 'VND'),
            (3, 'Other Owner', 'other.owner@example.test', 'test', 'USER', 'ACTIVE', 'VND')");
        $pdo->exec("INSERT INTO wallets (id, user_id, name, active_name_key, wallet_type, currency, cached_balance, earmarked_balance, status) VALUES
            (20, 2, 'Ngân hàng', 'ngân hàng', 'BANK', 'VND', 20000000, 0, 'ACTIVE'),
            (30, 3, 'Ví người khác', 'ví người khác', 'CASH', 'VND', 5000000, 0, 'ACTIVE')");

        $walletDao = new WalletDAO($pdo);
        $ledgerDao = new LedgerDAO($pdo);
        $goldDao = new GoldHoldingDAO($pdo);
        $loanDao = new LoanDAO($pdo);
        $goalDao = new FinancialGoalDAO($pdo);
        $categoryDao = new CategoryDAO($pdo);
        $goldService = new GoldAssetService($goldDao, $walletDao, $ledgerDao);
        $goalService = new GoalService($goalDao, $walletDao, $ledgerDao);
        $loanService = new LoanService($loanDao, $walletDao, $ledgerDao);
        $analytics = new NetWorthAnalyticsService(
            new NetWorthSnapshotDAO($pdo),
            $walletDao,
            $goldDao,
            $loanDao,
            $goalDao,
            new ExchangeRateService($pdo)
        );

        echo "--- Category notes and response privacy ---\n";
        $categoryId = $categoryDao->create(2, 'Ghi chú kiểm thử', 'EXPENSE', 'Chi phí thiết yếu');
        $listedCategories = $categoryDao->findAllByUser(2, 'EXPENSE');
        $category = array_values(array_filter($listedCategories, static fn(array $row): bool => (int) $row['id'] === $categoryId))[0] ?? [];
        $this->assert('TC-CATEGORY-01 Category note persists and category list omits owner id',
            ($category['note'] ?? null) === 'Chi phí thiết yếu' && !array_key_exists('user_id', $category));
        $categoryDao->update($categoryId, 2, 'Ghi chú kiểm thử', 'Chi phí cố định');
        $updatedCategory = $categoryDao->findByIdAndUser($categoryId, 2);
        $this->assert('TC-CATEGORY-02 Category note can be updated',
            ($updatedCategory['note'] ?? null) === 'Chi phí cố định');

        echo "--- FR08 / FR10: Gold and Net Worth ---\n";
        $before = $analytics->summary(2);
        $purchase = $goldService->buy(2, [
            'goldType' => 'SJC_BAR',
            'quantityChi' => '1',
            'pricePerChi' => '8500000',
            'walletId' => 20,
            'transactionDate' => date('Y-m-d'),
        ], '11111111-1111-4111-8111-111111111111');
        $after = $analytics->summary(2);
        $wallet = $walletDao->findByIdAndUser(20, 2);
        $this->assert('TC-WEALTH-01 Gold purchase decreases source wallet', bccomp((string) $wallet['cached_balance'], '11500000', 8) === 0);
        $this->assert('TC-WEALTH-02 Gold purchase creates held asset', bccomp((string) $purchase['asset']['currentValue'], '8500000', 8) === 0);
        $this->assert('TC-WEALTH-03 Asset conversion does not change Net Worth', bccomp((string) $before['netWorth'], (string) $after['netWorth'], 8) === 0);
        $incomeEntries = (int) $pdo->query("SELECT COUNT(*) FROM ledger_entries e JOIN ledger_accounts a ON a.id = e.ledger_account_id WHERE a.account_type = 'INCOME'")->fetchColumn();
        $this->assert('TC-WEALTH-04 Gold purchase is not income', $incomeEntries === 0);
        $entryBalance = $pdo->query('SELECT SUM(CASE WHEN entry_side = "DEBIT" THEN amount_base ELSE -amount_base END) FROM ledger_entries WHERE transaction_id = ' . (int) $purchase['transactionId'])->fetchColumn();
        $this->assert('TC-WEALTH-05 Gold purchase ledger is balanced', bccomp((string) $entryBalance, '0', 8) === 0);
        $replay = $goldService->buy(2, [
            'goldType' => 'SJC_BAR', 'quantityChi' => '1', 'pricePerChi' => '8500000',
            'walletId' => 20, 'transactionDate' => date('Y-m-d'),
        ], '11111111-1111-4111-8111-111111111111');
        $this->assert('TC-WEALTH-06 Retried purchase is not duplicated', (int) $replay['transactionId'] === (int) $purchase['transactionId'] && bccomp((string) $walletDao->findByIdAndUser(20, 2)['cached_balance'], '11500000', 8) === 0);
        try {
            $goldService->getAsset((int) $purchase['asset']['id'], 3);
            $isolated = false;
        } catch (ApiException $error) {
            $isolated = $error->status === 404;
        }
        $this->assert('TC-WEALTH-07 Other user cannot read gold asset', $isolated);
        $balanceBeforeRevalue = (string) $walletDao->findByIdAndUser(20, 2)['cached_balance'];
        $revalued = $goldService->revalue((int) $purchase['asset']['id'], 2, [
            'pricePerChi' => '8500000',
            'valuationDate' => date('Y-m-d'),
        ], '33333333-3333-4333-8333-333333333333');
        $this->assert('TC-WEALTH-08 Revaluation does not change wallet balance',
            bccomp((string) $walletDao->findByIdAndUser(20, 2)['cached_balance'], $balanceBeforeRevalue, 8) === 0);
        $this->assert('TC-WEALTH-09 Revaluation records the confirmed market value',
            bccomp((string) $revalued['asset']['valuationPerChi'], '8500000', 8) === 0);

        echo "--- FR07: Financial Goals ---\n";
        $goal = $goalService->createGoal(2, [
            'name' => 'Quỹ khẩn cấp',
            'targetAmount' => '1000000',
            'deadline' => date('Y-m-d', strtotime('+6 months')),
            'priority' => 'HIGH',
        ]);
        $goalResult = $goalService->allocate((int) $goal['id'], 2, 'DEPOSIT', [
            'walletId' => 20,
            'amount' => '1200000',
            'allocatedOn' => date('Y-m-d'),
            'note' => 'Khoản đầu tiên',
        ], '22222222-2222-4222-8222-222222222222');
        $walletAfterGoal = $walletDao->findByIdAndUser(20, 2);
        $this->assert('TC-GOAL-01 Goal allocation does not change wallet balance', bccomp((string) $walletAfterGoal['cached_balance'], '11500000', 8) === 0);
        $this->assert('TC-GOAL-02 Goal allocation earmarks available money', bccomp((string) $walletAfterGoal['earmarked_balance'], '1200000', 8) === 0);
        $this->assert('TC-GOAL-03 Completed progress is capped at 100%', (string) $goalResult['progressPercent'] === '100.0' && bccomp((string) $goalResult['remainingAmount'], '0', 8) === 0);
        $goalReplay = $goalService->allocate((int) $goal['id'], 2, 'DEPOSIT', [
            'walletId' => 20, 'amount' => '1200000', 'allocatedOn' => date('Y-m-d'), 'note' => 'Khoản đầu tiên',
        ], '22222222-2222-4222-8222-222222222222');
        $allocationCount = (int) $pdo->query('SELECT COUNT(*) FROM goal_allocations WHERE goal_id = ' . (int) $goal['id'])->fetchColumn();
        $this->assert('TC-GOAL-04 Retried allocation is not duplicated', $allocationCount === 1 && $goalReplay['currentAmount'] === $goalResult['currentAmount']);
        try {
            $goalService->getGoal((int) $goal['id'], 3);
            $goalIsolated = false;
        } catch (ApiException $error) {
            $goalIsolated = $error->status === 404;
        }
        $this->assert('TC-GOAL-05 Other user cannot read goal', $goalIsolated);

        echo "--- FR08: Loan and Debt Management ---\n";
        $beforeLoan = $analytics->summary(3);
        $createdLoan = $loanService->createLoan(3, [
            'title' => 'Vay vốn kiểm thử',
            'partnerName' => 'Quỹ tín dụng',
            'direction' => 'BORROWED',
            'principalAmount' => '2000000',
            'interestRateYear' => '12',
            'termMonths' => 12,
            'startDate' => date('Y-m-d'),
            'walletId' => 30,
        ], '55555555-5555-4555-8555-555555555555');
        $loanId = (int) $createdLoan['loan']['id'];
        $afterLoan = $analytics->summary(3);
        $this->assert('TC-LOAN-01 Borrowing increases the selected wallet',
            bccomp((string) $walletDao->findByIdAndUser(30, 3)['cached_balance'], '7000000', 8) === 0);
        $this->assert('TC-LOAN-02 Borrowing increases assets and liabilities equally',
            bccomp((string) $beforeLoan['netWorth'], (string) $afterLoan['netWorth'], 8) === 0);
        $this->assert('TC-LOAN-03 Repayment schedule is generated for the term',
            count($createdLoan['loan']['schedules']) === 12);
        $replayedLoan = $loanService->createLoan(3, [
            'title' => 'Vay vốn kiểm thử', 'partnerName' => 'Quỹ tín dụng', 'direction' => 'BORROWED',
            'principalAmount' => '2000000', 'interestRateYear' => '12', 'termMonths' => 12,
            'startDate' => date('Y-m-d'), 'walletId' => 30,
        ], '55555555-5555-4555-8555-555555555555');
        $this->assert('TC-LOAN-04 Retried disbursement is not duplicated',
            (int) $replayedLoan['loan']['id'] === $loanId
            && bccomp((string) $walletDao->findByIdAndUser(30, 3)['cached_balance'], '7000000', 8) === 0);
        $payment = $loanService->recordPayment($loanId, 3, [
            'principalAmount' => '500000',
            'interestFeeAmount' => '50000',
            'paidOn' => date('Y-m-d'),
            'walletId' => 30,
        ], '66666666-6666-4666-8666-666666666666');
        $this->assert('TC-LOAN-05 Principal and interest reduce the wallet exactly once',
            bccomp((string) $walletDao->findByIdAndUser(30, 3)['cached_balance'], '6450000', 8) === 0);
        $this->assert('TC-LOAN-06 Principal payment reduces remaining debt',
            bccomp((string) $payment['loan']['remainingPrincipal'], '1500000', 8) === 0);
        $interestExpense = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE user_id = 3 AND transaction_type = 'EXPENSE'")->fetchColumn();
        $this->assert('TC-LOAN-07 Borrowing interest is an expense but principal is not',
            bccomp((string) $interestExpense, '50000', 8) === 0);
        $unbalanced = (int) $pdo->query("SELECT COUNT(*) FROM (
            SELECT t.id FROM financial_transactions t JOIN ledger_entries e ON e.transaction_id = t.id
            WHERE t.user_id = 3 GROUP BY t.id
            HAVING ABS(SUM(CASE WHEN e.entry_side = 'DEBIT' THEN e.amount_base ELSE -e.amount_base END)) > 0.000001
        ) invalid")->fetchColumn();
        $this->assert('TC-LOAN-08 Every loan transaction keeps double-entry balanced', $unbalanced === 0);
        try {
            $loanService->getLoan($loanId, 2);
            $loanIsolated = false;
        } catch (ApiException $error) {
            $loanIsolated = $error->status === 404;
        }
        $this->assert('TC-LOAN-09 Other user cannot read the loan', $loanIsolated);

        echo "--- FR10: Liabilities and Dashboard source consistency ---\n";
        $pdo->exec("UPDATE wallets SET cached_balance = cached_balance + 10000000 WHERE id = 20");
        $pdo->exec("INSERT INTO micro_loans
            (user_id, wallet_id, title, partner_name, loan_direction, currency, principal_original,
             principal_remaining, interest_rate_year, term_months, start_date, next_due_date, status)
            VALUES (2, 20, 'Khoản vay học tập', 'Ngân hàng', 'BORROWED', 'VND', 10000000,
                    10000000, 6.5, 12, date('now'), date('now', '+1 month'), 'ACTIVE')");
        $withLoan = $analytics->summary(2);
        $this->assert('TC-DASH-01 Borrowed principal comes from Loans module', bccomp((string) $withLoan['liabilities']['payableLoans'], '10000000', 8) === 0);
        $this->assert('TC-DASH-02 Loan receipt increases assets and liabilities equally', bccomp((string) $withLoan['netWorth'], (string) $after['netWorth'], 8) === 0);
        $this->assert('TC-DASH-03 Goal money is not added to total assets', bccomp((string) $withLoan['assets']['totalAssets'], '30000000', 8) === 0);

        $beforeSaleBalance = (string) $walletDao->findByIdAndUser(20, 2)['cached_balance'];
        $sale = $goldService->liquidate((int) $purchase['asset']['id'], 2, [
            'walletId' => 20,
            'pricePerChi' => '9000000',
            'transactionDate' => date('Y-m-d'),
        ], '44444444-4444-4444-8444-444444444444');
        $this->assert('TC-WEALTH-10 Liquidation credits sale proceeds to the selected wallet',
            bccomp((string) $walletDao->findByIdAndUser(20, 2)['cached_balance'], bcadd($beforeSaleBalance, '9000000', 8), 8) === 0);
        $this->assert('TC-WEALTH-11 Liquidation removes the holding from active assets',
            (string) $sale['asset']['status'] === 'LIQUIDATED' && bccomp((string) $sale['asset']['currentValue'], '0', 8) === 0);
        $saleEntryBalance = $pdo->query('SELECT SUM(CASE WHEN entry_side = "DEBIT" THEN amount_base ELSE -amount_base END) FROM ledger_entries WHERE transaction_id = ' . (int) $sale['transactionId'])->fetchColumn();
        $this->assert('TC-WEALTH-12 Liquidation ledger including gain is balanced', bccomp((string) $saleEntryBalance, '0', 8) === 0);
        $saleReplay = $goldService->liquidate((int) $purchase['asset']['id'], 2, [
            'walletId' => 20, 'pricePerChi' => '9000000', 'transactionDate' => date('Y-m-d'),
        ], '44444444-4444-4444-8444-444444444444');
        $this->assert('TC-WEALTH-13 Retried liquidation is not duplicated',
            (int) $saleReplay['transactionId'] === (int) $sale['transactionId']
            && bccomp((string) $walletDao->findByIdAndUser(20, 2)['cached_balance'], bcadd($beforeSaleBalance, '9000000', 8), 8) === 0);

        echo "\nWEALTH/GOALS TEST SUMMARY: {$this->passed} PASSED, {$this->failed} FAILED\n";
        if ($this->failed > 0) {
            exit(1);
        }
    }
}

(new WealthGoalsTest())->run();
