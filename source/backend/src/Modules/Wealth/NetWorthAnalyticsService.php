<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use RuntimeException;
use WealthFlow\Modules\Goals\FinancialGoalDAO;
use WealthFlow\Modules\Loans\LoanDAO;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Wallets\WalletDAO;

final class NetWorthAnalyticsService
{
    private NetWorthSnapshotDAO $snapshotDao;
    private WalletDAO $walletDao;
    private GoldHoldingDAO $goldDao;
    private LoanDAO $loanDao;
    private FinancialGoalDAO $goalDao;
    private ExchangeRateService $rateService;

    public function __construct(
        ?NetWorthSnapshotDAO $snapshotDao = null,
        ?WalletDAO $walletDao = null,
        ?GoldHoldingDAO $goldDao = null,
        ?LoanDAO $loanDao = null,
        ?FinancialGoalDAO $goalDao = null,
        ?ExchangeRateService $rateService = null
    ) {
        $this->snapshotDao = $snapshotDao ?? new NetWorthSnapshotDAO();
        $pdo = $this->snapshotDao->getPdo();
        $this->walletDao = $walletDao ?? new WalletDAO($pdo);
        $this->goldDao = $goldDao ?? new GoldHoldingDAO($pdo);
        $this->loanDao = $loanDao ?? new LoanDAO($pdo);
        $this->goalDao = $goalDao ?? new FinancialGoalDAO($pdo);
        $this->rateService = $rateService ?? new ExchangeRateService($pdo);
    }

    /** @return array<string, mixed> */
    public function summary(int $userId, ?string $month = null): array
    {
        $baseCurrency = $this->walletDao->getUserBaseCurrency($userId);
        if ($baseCurrency === null) {
            throw new RuntimeException('Không tìm thấy tài khoản đang hoạt động.');
        }
        $walletAssets = '0.00000000';
        $creditLiabilities = '0.00000000';
        $walletBreakdown = ['cash' => '0.00000000', 'bank' => '0.00000000', 'eWallet' => '0.00000000', 'savings' => '0.00000000'];
        foreach ($this->walletDao->findAllByUser($userId) as $wallet) {
            $balanceBase = $this->toBase((string) $wallet['cached_balance'], (string) $wallet['currency'], $baseCurrency);
            if ((string) $wallet['wallet_type'] === 'CREDIT') {
                if (bccomp($balanceBase, '0', 8) < 0) {
                    $creditLiabilities = bcadd($creditLiabilities, bcmul($balanceBase, '-1', 8), 8);
                }
                continue;
            }
            $walletAssets = bcadd($walletAssets, $balanceBase, 8);
            $key = match ((string) $wallet['wallet_type']) {
                'CASH' => 'cash',
                'BANK' => 'bank',
                'E_WALLET' => 'eWallet',
                'SAVINGS' => 'savings',
                default => null,
            };
            if ($key !== null) {
                $walletBreakdown[$key] = bcadd($walletBreakdown[$key], $balanceBase, 8);
            }
        }

        $goldTotal = '0.00000000';
        foreach ($this->goldDao->listByUser($userId) as $asset) {
            $goldTotal = bcadd(
                $goldTotal,
                bcmul((string) $asset['quantity_chi'], (string) $asset['valuation_per_chi'], 8),
                8
            );
        }

        $receivableLoans = '0.00000000';
        $payableLoans = '0.00000000';
        foreach ($this->loanDao->listByUser($userId) as $loan) {
            if ((string) $loan['status'] === 'PAID_OFF') {
                continue;
            }
            $remaining = $this->toBase((string) $loan['principal_remaining'], (string) $loan['currency'], $baseCurrency);
            if ((string) $loan['loan_direction'] === 'LENT') {
                $receivableLoans = bcadd($receivableLoans, $remaining, 8);
            } else {
                $payableLoans = bcadd($payableLoans, $remaining, 8);
            }
        }

        $totalAssets = bcadd(bcadd($walletAssets, $goldTotal, 8), $receivableLoans, 8);
        $totalLiabilities = bcadd($creditLiabilities, $payableLoans, 8);
        $netWorth = bcsub($totalAssets, $totalLiabilities, 8);
        $cashFlow = $this->snapshotDao->cashFlowForMonth($userId, $this->month($month));
        $netCashFlow = bcsub($cashFlow['income'], $cashFlow['expense'], 8);
        $savingRate = bccomp($cashFlow['income'], '0', 8) > 0
            ? bcmul(bcdiv($netCashFlow, $cashFlow['income'], 8), '100', 2)
            : null;

        $goals = $this->goalDao->listByUser($userId);
        $goalTarget = '0.00000000';
        $goalCurrent = '0.00000000';
        $activeGoals = [];
        foreach ($goals as $goal) {
            if ((string) $goal['status'] === 'CANCELLED') {
                continue;
            }
            $goalTarget = bcadd($goalTarget, (string) $goal['target_amount'], 8);
            $goalCurrent = bcadd($goalCurrent, (string) $goal['current_amount'], 8);
            if ((string) $goal['status'] === 'ACTIVE') {
                $progress = bccomp((string) $goal['target_amount'], '0', 8) > 0
                    ? bcmul(bcdiv((string) $goal['current_amount'], (string) $goal['target_amount'], 8), '100', 2)
                    : '0.00';
                if (bccomp($progress, '100.00', 2) > 0) {
                    $progress = '100.00';
                }
                $activeGoals[] = [
                    'id' => (int) $goal['id'],
                    'name' => (string) $goal['name'],
                    'targetAmount' => (string) $goal['target_amount'],
                    'currentAmount' => (string) $goal['current_amount'],
                    'progressPercent' => $progress,
                    'deadline' => (string) $goal['deadline'],
                ];
            }
        }

        return [
            'currency' => $baseCurrency,
            'netWorth' => $netWorth,
            'assets' => [
                'wallets' => $walletAssets,
                'gold' => $goldTotal,
                'receivableLoans' => $receivableLoans,
                'totalAssets' => $totalAssets,
                'breakdown' => $walletBreakdown + ['gold' => $goldTotal, 'receivableLoans' => $receivableLoans],
            ],
            'liabilities' => [
                'creditWallets' => $creditLiabilities,
                'payableLoans' => $payableLoans,
                'totalLiabilities' => $totalLiabilities,
            ],
            'cashFlow' => [
                'month' => $this->month($month),
                'income' => $cashFlow['income'],
                'expense' => $cashFlow['expense'],
                'net' => $netCashFlow,
                'savingRate' => $savingRate,
            ],
            'goals' => [
                'targetAmount' => $goalTarget,
                'currentAmount' => $goalCurrent,
                'activeCount' => count($activeGoals),
                'items' => array_slice($activeGoals, 0, 5),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $userId, int $days): array
    {
        if (!in_array($days, [30, 90, 365], true)) {
            throw new \InvalidArgumentException('Khoảng lịch sử chỉ nhận 30, 90 hoặc 365 ngày.');
        }
        return array_map(static function (array $row): array {
            $breakdown = json_decode((string) ($row['breakdown_json'] ?? ''), true);
            return [
                'date' => (string) $row['snapshot_date'],
                'totalAssets' => (string) $row['total_assets'],
                'totalLiabilities' => (string) $row['total_liabilities'],
                'netWorth' => (string) $row['net_worth'],
                'breakdown' => is_array($breakdown) ? $breakdown : null,
            ];
        }, $this->snapshotDao->history($userId, $days));
    }

    /** @return array<string, mixed> */
    public function expenseDashboard(int $userId, ?string $month = null): array
    {
        if ($this->walletDao->getUserBaseCurrency($userId) === null) {
            throw new RuntimeException('Không tìm thấy tài khoản đang hoạt động.');
        }
        $selectedMonth = $this->month($month);
        $cashFlow = $this->snapshotDao->cashFlowForMonth($userId, $selectedMonth);
        return [
            'currency' => 'VND',
            'month' => $selectedMonth,
            'totalExpense' => $cashFlow['expense'],
            'byCategory' => $this->snapshotDao->expenseByCategory($userId, $selectedMonth),
            'trend' => $this->snapshotDao->cashFlowTrend($userId, $selectedMonth, 6),
        ];
    }

    private function toBase(string $amount, string $currency, string $baseCurrency): string
    {
        $currency = strtoupper($currency);
        if ($currency === $baseCurrency || bccomp($amount, '0', 8) === 0) {
            return bcadd($amount, '0', 8);
        }
        $rate = $this->rateService->getSnapshotRate($currency, $baseCurrency);
        return bcmul($amount, $rate, 8);
    }

    private function month(?string $month): string
    {
        $value = $month === null || trim($month) === '' ? date('Y-m') : trim($month);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $value)) {
            throw new \InvalidArgumentException('Tháng thống kê phải có định dạng YYYY-MM.');
        }
        return $value;
    }
}
