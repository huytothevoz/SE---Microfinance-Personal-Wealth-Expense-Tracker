<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Goals;

use PDO;
use WealthFlow\Core\Database;

final class FinancialGoalDAO
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
    public function listByUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT g.*, w.name AS wallet_name, w.currency AS wallet_currency
                FROM financial_goals g
                LEFT JOIN wallets w ON w.id = g.wallet_id
                WHERE g.user_id = ?';
        $params = [$userId];
        if ($status !== null) {
            $sql .= ' AND g.status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY CASE g.status WHEN 'ACTIVE' THEN 0 WHEN 'COMPLETED' THEN 1 ELSE 2 END,
                  g.deadline ASC, g.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT g.*, w.name AS wallet_name, w.currency AS wallet_currency
                FROM financial_goals g
                LEFT JOIN wallets w ON w.id = g.wallet_id
                WHERE g.id = ? AND g.user_id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(
        int $userId,
        ?int $walletId,
        string $name,
        string $targetAmount,
        string $deadline,
        string $priority
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO financial_goals (user_id, wallet_id, name, target_amount, current_amount, deadline, priority, status)
             VALUES (?, ?, ?, ?, 0, ?, ?, "ACTIVE")'
        );
        $stmt->execute([$userId, $walletId, $name, $targetAmount, $deadline, $priority]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateInfo(
        int $id,
        int $userId,
        string $name,
        string $targetAmount,
        string $deadline,
        string $priority,
        string $status
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE financial_goals
             SET name = ?, target_amount = ?, deadline = ?, priority = ?, status = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $targetAmount, $deadline, $priority, $status, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function updateAmountAndStatus(int $id, int $userId, string $amount, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE financial_goals SET current_amount = ?, status = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$amount, $status, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function setStatus(int $id, int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE financial_goals SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$status, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function addAllocation(
        int $goalId,
        int $walletId,
        string $type,
        string $amount,
        string $allocatedOn,
        ?string $note
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO goal_allocations (goal_id, wallet_id, allocation_type, amount, allocated_on, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$goalId, $walletId, $type, $amount, $allocatedOn, $note]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAllocations(int $goalId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.allocation_type, a.amount, a.allocated_on, a.note,
                    a.wallet_id, w.name AS wallet_name
             FROM goal_allocations a
             JOIN financial_goals g ON g.id = a.goal_id
             LEFT JOIN wallets w ON w.id = a.wallet_id
             WHERE a.goal_id = ? AND g.user_id = ?
             ORDER BY a.allocated_on DESC, a.id DESC'
        );
        $stmt->execute([$goalId, $userId]);
        return $stmt->fetchAll();
    }

    public function allocatedAmountForWallet(int $goalId, int $walletId, int $userId): string
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN a.allocation_type = 'DEPOSIT' THEN a.amount ELSE -a.amount END), 0)
             FROM goal_allocations a
             JOIN financial_goals g ON g.id = a.goal_id
             WHERE a.goal_id = ? AND a.wallet_id = ? AND g.user_id = ?"
        );
        $stmt->execute([$goalId, $walletId, $userId]);
        return (string) $stmt->fetchColumn();
    }

    /** @return array<int, array{wallet_id:int, amount:string}> */
    public function allocatedWalletBalances(int $goalId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.wallet_id,
                    SUM(CASE WHEN a.allocation_type = 'DEPOSIT' THEN a.amount ELSE -a.amount END) AS amount
             FROM goal_allocations a
             JOIN financial_goals g ON g.id = a.goal_id
             WHERE a.goal_id = ? AND g.user_id = ? AND a.wallet_id IS NOT NULL
             GROUP BY a.wallet_id
             HAVING amount > 0"
        );
        $stmt->execute([$goalId, $userId]);
        return $stmt->fetchAll();
    }

    public function increaseWalletEarmark(int $walletId, int $userId, string $amount): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets
             SET earmarked_balance = earmarked_balance + CAST(? AS DECIMAL(24,8))
             WHERE id = ? AND user_id = ? AND status = 'ACTIVE'
               AND cached_balance - earmarked_balance >= CAST(? AS DECIMAL(24,8))"
        );
        $stmt->execute([$amount, $walletId, $userId, $amount]);
        return $stmt->rowCount() === 1;
    }

    public function decreaseWalletEarmark(int $walletId, int $userId, string $amount): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets
             SET earmarked_balance = earmarked_balance - CAST(? AS DECIMAL(24,8))
             WHERE id = ? AND user_id = ? AND earmarked_balance >= CAST(? AS DECIMAL(24,8))'
        );
        $stmt->execute([$amount, $walletId, $userId, $amount]);
        return $stmt->rowCount() === 1;
    }
}
