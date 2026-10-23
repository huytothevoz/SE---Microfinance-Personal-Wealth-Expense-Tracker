<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use WealthFlow\Core\ApiException;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Goals\FinancialGoalDAO;
use WealthFlow\Modules\Goals\GoalService;
use WealthFlow\Modules\Loans\LoanDAO;
use WealthFlow\Modules\Transactions\ExchangeRateService;
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
        $goldService = new GoldAssetService($goldDao, $walletDao, $ledgerDao);
        $goalService = new GoalService($goalDao, $walletDao, $ledgerDao);
        $analytics = new NetWorthAnalyticsService(
            new NetWorthSnapshotDAO($pdo),
            $walletDao,
            $goldDao,
            $loanDao,
            $goalDao,
            new ExchangeRateService($pdo)
        );

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
