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
     * Log a security audit event (e.g., LEDGER_MISMATCH, LOGIN_FAILED, CSRF_REJECTED)
     */
    public function logSecurityEvent(?int $userId, string $event): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO security_audit_logs (user_id, event, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$userId, strtoupper(trim($event))]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Log an export audit trail (e.g., TRANSACTIONS_CSV, TRIAL_BALANCE_CSV)
     */
    public function logExportAudit(
        int $userId,
        string $exportType,
        int $rowCount,
        ?string $fileHash = null,
        ?string $ipAddress = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO export_audit_logs (user_id, export_type, row_count, file_hash, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            $userId,
            strtoupper(trim($exportType)),
            max(0, $rowCount),
            $fileHash,
            $ipAddress,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getExportLogsByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM export_audit_logs WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSecurityLogsByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM security_audit_logs WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Log an administrative action (e.g., CREATE_DEFAULT_CATEGORY, UPDATE_DEFAULT_CATEGORY)
     */
    public function logAdminAction(
        int $adminUserId,
        string $action,
        ?int $targetUserId = null,
        ?string $reason = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_audit_logs (admin_user_id, action, target_user_id, reason, created_at)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            $adminUserId,
            strtoupper(trim($action)),
            $targetUserId,
            $reason,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdminLogs(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT l.*, u.email as admin_email 
             FROM admin_audit_logs l
             JOIN users u ON l.admin_user_id = u.id
             ORDER BY l.id DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBroadcastLogs(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT l.*, u.email as admin_email 
             FROM admin_audit_logs l
             JOIN users u ON l.admin_user_id = u.id
             WHERE l.action = 'BROADCAST_ANNOUNCEMENT'
             ORDER BY l.id DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Alias for logAdminAction (FR13.02, FR13.03, FR14.03, FR14.04).
     */
    public function logAction(
        int $adminUserId,
        string $action,
        ?int $targetUserId = null,
        ?string $reason = null
    ): int {
        return $this->logAdminAction($adminUserId, $action, $targetUserId, $reason);
    }

    /**
     * List recent administrative audit logs joined with admin and target user identities (FR14.01, FR14.03).
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

