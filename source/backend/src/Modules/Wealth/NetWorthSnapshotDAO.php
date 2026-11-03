<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use PDO;
use WealthFlow\Core\Database;

final class NetWorthSnapshotDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $userId, int $days): array
    {
        $cutoff = date('Y-m-d', strtotime('-' . max(0, $days - 1) . ' days'));
        $stmt = $this->pdo->prepare(
            'SELECT snapshot_date, total_assets, total_liabilities, net_worth, breakdown_json
             FROM net_worth_snapshots
             WHERE user_id = ? AND snapshot_date >= ?
             ORDER BY snapshot_date ASC'
        );
        $stmt->execute([$userId, $cutoff]);
        return $stmt->fetchAll();
    }

    /** @return array{income:string, expense:string} */
    public function cashFlowForMonth(int $userId, string $month): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN transaction_type = 'INCOME' THEN amount * fx_rate_snapshot ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN transaction_type = 'EXPENSE' THEN amount * fx_rate_snapshot ELSE 0 END), 0) AS expense
             FROM financial_transactions
             WHERE user_id = ? AND status = 'POSTED' AND occurred_on >= ? AND occurred_on < ?"
        );
        $start = $month . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));
        $stmt->execute([$userId, $start, $end]);
        $row = $stmt->fetch() ?: ['income' => '0', 'expense' => '0'];
        return ['income' => (string) $row['income'], 'expense' => (string) $row['expense']];
    }

    /** @return array<int, array{categoryId:int, category:string, amount:string}> */
    public function expenseByCategory(int $userId, string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));
        $stmt = $this->pdo->prepare(
            "SELECT c.id AS category_id, c.name AS category_name,
                    COALESCE(SUM(t.amount * t.fx_rate_snapshot), 0) AS amount
             FROM financial_transactions t
             JOIN categories c ON c.id = t.category_id AND c.user_id = t.user_id
             WHERE t.user_id = ? AND t.status = 'POSTED' AND t.transaction_type = 'EXPENSE'
               AND t.occurred_on >= ? AND t.occurred_on < ?
             GROUP BY c.id, c.name
             ORDER BY amount DESC, c.id ASC"
        );
        $stmt->execute([$userId, $start, $end]);
        return array_map(static fn(array $row): array => [
            'categoryId' => (int) $row['category_id'],
            'category' => (string) $row['category_name'],
            'amount' => (string) $row['amount'],
        ], $stmt->fetchAll());
    }

    /** @return array<int, array{month:string, income:string, expense:string}> */
    public function cashFlowTrend(int $userId, string $lastMonth, int $months = 6): array
    {
        $result = [];
        $anchor = new \DateTimeImmutable($lastMonth . '-01');
        for ($index = $months - 1; $index >= 0; $index--) {
            $month = $anchor->modify("-{$index} months")->format('Y-m');
            $cashFlow = $this->cashFlowForMonth($userId, $month);
            $result[] = ['month' => $month] + $cashFlow;
        }
        return $result;
    }
}
