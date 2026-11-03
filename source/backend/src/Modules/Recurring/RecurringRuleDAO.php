<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Recurring;

use PDO;
use WealthFlow\Core\Database;

final class RecurringRuleDAO
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

    /**
     * @param array{
     *     userId: int,
     *     walletId: int,
     *     categoryId: ?int,
     *     name: string,
     *     amount: float|string|int,
     *     currency: string,
     *     dayOfMonth: int,
     *     nextDueDate: string,
     *     executionMode: string
     * } $data
     */
    public function create(array $data): int
    {
        $amountStr = is_string($data['amount']) ? $data['amount'] : number_format((float) $data['amount'], 8, '.', '');

        $stmt = $this->pdo->prepare('INSERT INTO recurring_schedules
            (user_id, wallet_id, category_id, name, amount, currency, day_of_month, next_due_date, execution_mode, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "ACTIVE")');
        $stmt->execute([
            $data['userId'],
            $data['walletId'],
            $data['categoryId'],
            $data['name'],
            $amountStr,
            strtoupper($data['currency']),
            $data['dayOfMonth'],
            $data['nextDueDate'],
            $data['executionMode'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, w.name as wallet_name, c.name as category_name
            FROM recurring_schedules s
            JOIN wallets w ON s.wallet_id = w.id
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.id = ? AND s.user_id = ?');
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, w.name as wallet_name, c.name as category_name
            FROM recurring_schedules s
            JOIN wallets w ON s.wallet_id = w.id
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.user_id = ?
            ORDER BY s.next_due_date ASC, s.id ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE recurring_schedules SET status = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([strtoupper($status), $id, $userId]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM recurring_schedules WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public function updateNextDueDate(int $id, string $nextDueDate): void
    {
        $stmt = $this->pdo->prepare('UPDATE recurring_schedules SET next_due_date = ? WHERE id = ?');
        $stmt->execute([$nextDueDate, $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function findDueSchedules(string $asOfDate): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, w.cached_balance as wallet_balance, w.name as wallet_name
            FROM recurring_schedules s
            JOIN wallets w ON s.wallet_id = w.id
            WHERE s.status = "ACTIVE" AND w.status = "ACTIVE" AND s.next_due_date <= ?
            ORDER BY s.id ASC');
        $stmt->execute([$asOfDate]);
        return $stmt->fetchAll();
    }

    public function hasExecutionForDue(int $scheduleId, string $dueDate): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM recurring_executions
            WHERE recurring_schedule_id = ? AND due_date = ?');
        $stmt->execute([$scheduleId, $dueDate]);
        return $stmt->fetchColumn() !== false;
    }

    public function recordExecution(int $scheduleId, string $dueDate, ?int $txId, string $status, ?string $errorMessage = null): int
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare('INSERT INTO recurring_executions
                (recurring_schedule_id, due_date, transaction_id, status)
                VALUES (?, ?, ?, ?)');
            $stmt->execute([$scheduleId, $dueDate, $txId, strtoupper($status)]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO recurring_executions
                (recurring_schedule_id, due_date, transaction_id, status, executed_at, error_message)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?)');
            $stmt->execute([$scheduleId, $dueDate, $txId, strtoupper($status), $errorMessage]);
        }
        return (int) $this->pdo->lastInsertId();
    }
}
