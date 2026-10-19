<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Budgets;

use PDO;
use WealthFlow\Core\Database;

final class BudgetDAO
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

    public function create(int $userId, int $categoryId, string $monthYear, float|string $limitAmount): int
    {
        $monthDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $limitStr = is_string($limitAmount) ? $limitAmount : number_format((float) $limitAmount, 8, '.', '');

        $checkArchived = $this->pdo->prepare(
            'SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND budget_month = ? AND status = "ARCHIVED"'
        );
        $checkArchived->execute([$userId, $categoryId, $monthDate]);
        $archivedId = $checkArchived->fetchColumn();
        if ($archivedId !== false) {
            $reactivate = $this->pdo->prepare(
                'UPDATE budgets SET limit_amount = ?, spent_amount = 0, alert_level = "NORMAL", is_yellow_sent = 0, is_red_sent = 0, status = "ACTIVE" WHERE id = ? AND user_id = ?'
            );
            $reactivate->execute([$limitStr, (int) $archivedId, $userId]);
            return (int) $archivedId;
        }

        $stmt = $this->pdo->prepare('INSERT INTO budgets (user_id, category_id, budget_month, limit_amount, status)
            VALUES (?, ?, ?, ?, "ACTIVE")');
        $stmt->execute([$userId, $categoryId, $monthDate, $limitStr]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLimit(int $id, int $userId, float|string $limitAmount): bool
    {
        $limitStr = is_string($limitAmount) ? $limitAmount : number_format((float) $limitAmount, 8, '.', '');
        $stmt = $this->pdo->prepare('UPDATE budgets SET limit_amount = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$limitStr, $id, $userId]);
    }

    public function syncBudgetState(
        int $id,
        int $userId,
        float|string $spentAmount,
        string $dbAlertLevel,
        bool $yellowSent,
        bool $redSent
    ): bool {
        $spentStr = is_string($spentAmount) ? $spentAmount : number_format((float) $spentAmount, 8, '.', '');
        $stmt = $this->pdo->prepare(
            'UPDATE budgets SET spent_amount = ?, alert_level = ?, is_yellow_sent = ?, is_red_sent = ? WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([
            $spentStr,
            $dbAlertLevel,
            $yellowSent ? 1 : 0,
            $redSent ? 1 : 0,
            $id,
            $userId,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT b.*, c.name as category_name
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.id = ? AND b.user_id = ?');
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUserCategoryAndMonth(int $userId, int $categoryId, string $monthYear): ?array
    {
        $monthDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $stmt = $this->pdo->prepare('SELECT b.*, c.name as category_name
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = ? AND b.category_id = ? AND b.budget_month = ? AND b.status = "ACTIVE"');
        $stmt->execute([$userId, $categoryId, $monthDate]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByMonth(int $userId, string $monthYear): array
    {
        $monthDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $stmt = $this->pdo->prepare('SELECT b.*, c.name as category_name
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = ? AND b.budget_month = ? AND b.status = "ACTIVE"
            ORDER BY b.id ASC');
        $stmt->execute([$userId, $monthDate]);
        return $stmt->fetchAll();
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE budgets SET status = "ARCHIVED" WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Calculate Net Spend for a category in a month in base currency (BR07).
     * Net Spend = SUM(POSTED Expense amount * fx_rate_snapshot), excluding TRANSFER, LOAN_PAYMENT, DRAFT, REVERSED.
     * Returns string decimal.
     */
    public function getCategoryNetExpenseInMonth(int $userId, int $categoryId, string $monthYear): string
    {
        $startDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $endDate = date('Y-m-t', strtotime($monthYear . '-01'));

        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount * COALESCE(fx_rate_snapshot, 1)), 0) FROM financial_transactions
            WHERE user_id = ? AND category_id = ? AND transaction_type = "EXPENSE"
            AND status = "POSTED" AND occurred_on BETWEEN ? AND ?');
        $stmt->execute([$userId, $categoryId, $startDate, $endDate]);
        $val = $stmt->fetchColumn();
        return (string) ($val !== false ? $val : '0');
    }
}
