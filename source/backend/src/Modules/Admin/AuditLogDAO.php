<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use PDO;
use WealthFlow\Core\Database;

final class AuditLogDAO
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
     * Record an administrative action in admin_audit_logs (FR13.02, FR13.03, FR14.03, FR14.04).
     */
    public function logAction(
        int $adminUserId,
        string $action,
        ?int $targetUserId = null,
        ?string $reason = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_audit_logs (admin_user_id, target_user_id, action, reason)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $adminUserId,
            $targetUserId,
            strtoupper(trim($action)),
            $reason !== null ? trim($reason) : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * List recent administrative audit logs joined with admin and target user identities.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listLogs(int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT l.id,
                       l.admin_user_id,
                       u_admin.full_name AS admin_name,
                       u_admin.email AS admin_email,
                       l.target_user_id,
                       u_target.full_name AS target_name,
                       u_target.email AS target_email,
                       l.action,
                       l.reason,
                       l.created_at
                FROM admin_audit_logs l
                LEFT JOIN users u_admin ON u_admin.id = l.admin_user_id
                LEFT JOIN users u_target ON u_target.id = l.target_user_id
                ORDER BY l.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
