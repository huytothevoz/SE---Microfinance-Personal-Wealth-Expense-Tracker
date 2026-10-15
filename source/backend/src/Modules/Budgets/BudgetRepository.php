<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Budgets;

use PDO;
use WealthFlow\Config\Database;

final class BudgetRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function create(int $userId, int $categoryId, string $monthYear, float $limitAmount): int
    {
        $monthDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $stmt = $this->pdo->prepare('INSERT INTO budgets (user_id, category_id, budget_month, limit_amount, status)
            VALUES (?, ?, ?, ?, "ACTIVE")');
        $stmt->execute([$userId, $categoryId, $monthDate, $limitAmount]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLimit(int $id, int $userId, float $limitAmount): bool
    {
        $stmt = $this->pdo->prepare('UPDATE budgets SET limit_amount = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$limitAmount, $id, $userId]);
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
     * Calculate Net Spend for a category in a month (BR07).
     * Net Spend = SUM(POSTED Expense amountBase) - SUM(REVERSED Expense amountBase)
     */
    public function getCategoryNetExpenseInMonth(int $userId, int $categoryId, string $monthYear): float
    {
        $startDate = date('Y-m-01', strtotime($monthYear . '-01'));
        $endDate = date('Y-m-t', strtotime($monthYear . '-01'));

        // Active posted expenses only (BR07 & BR14: reversed transactions have status 'REVERSED' so they are not included)
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
            WHERE user_id = ? AND category_id = ? AND transaction_type = "EXPENSE"
            AND status = "POSTED" AND occurred_on BETWEEN ? AND ?');
        $stmt->execute([$userId, $categoryId, $startDate, $endDate]);
        return (float) $stmt->fetchColumn();
    }
}
