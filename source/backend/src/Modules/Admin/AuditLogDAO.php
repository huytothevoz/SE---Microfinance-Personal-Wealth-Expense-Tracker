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
}
