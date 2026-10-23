<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Loans;

use PDO;
use WealthFlow\Core\Database;

final class LoanDAO
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
    public function listByUser(int $userId, ?string $direction = null, ?string $status = null): array
    {
        $sql = 'SELECT l.*, w.name AS wallet_name
                FROM micro_loans l
                LEFT JOIN wallets w ON w.id = l.wallet_id
                WHERE l.user_id = ?';
        $params = [$userId];
        if ($direction !== null) {
            $sql .= ' AND l.loan_direction = ?';
            $params[] = $direction;
        }
        if ($status !== null) {
            $sql .= ' AND l.status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY CASE l.status WHEN 'ACTIVE' THEN 0 WHEN 'DEFAULTED' THEN 1 ELSE 2 END,
                  COALESCE(l.next_due_date, '9999-12-31'), l.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, w.name AS wallet_name
             FROM micro_loans l
             LEFT JOIN wallets w ON w.id = l.wallet_id
             WHERE l.id = ? AND l.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listPayments(int $loanId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.* FROM loan_payments p
             JOIN micro_loans l ON l.id = p.loan_id
             WHERE p.loan_id = ? AND l.user_id = ?
             ORDER BY p.paid_on DESC, p.id DESC'
        );
        $stmt->execute([$loanId, $userId]);
        return $stmt->fetchAll();
    }
}
