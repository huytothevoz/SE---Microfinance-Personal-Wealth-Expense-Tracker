<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use PDO;
use WealthFlow\Core\Database;

final class ReportDAO
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
     * Fetch all wallets of a user (or a single wallet if $walletId is given) for reconciliation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findWalletsForReconciliation(int $userId, ?int $walletId = null, bool $includeArchived = true): array
    {
        $sql = 'SELECT id, user_id, name, wallet_type, currency, cached_balance, earmarked_balance, status, updated_at
                FROM wallets
                WHERE user_id = ? AND deleted_at IS NULL';
        $params = [$userId];

        if ($walletId !== null) {
            $sql .= ' AND id = ?';
            $params[] = $walletId;
        } elseif (!$includeArchived) {
            $sql .= " AND status = 'ACTIVE'";
        }

        $sql .= ' ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all posted/reversed ledger entries for a specific wallet.
     * Excludes DRAFT transactions per BR01 & BR14.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findWalletLedgerEntries(int $userId, int $walletId): array
    {
        $sql = "SELECT e.id,
                       e.transaction_id,
                       e.entry_side,
                       e.amount_original,
                       e.currency,
                       e.amount_base,
                       e.fx_rate_snapshot,
                       t.transaction_type,
                       t.status AS transaction_status,
                       t.description,
                       t.occurred_on,
                       a.code AS account_code,
                       a.account_type
                FROM ledger_entries e
                JOIN ledger_accounts a ON a.id = e.ledger_account_id
                JOIN financial_transactions t ON t.id = e.transaction_id
                WHERE a.user_id = ?
                  AND a.wallet_id = ?
                  AND t.user_id = ?
                  AND t.status IN ('POSTED', 'REVERSED')
                ORDER BY e.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $walletId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Aggregate trial balance across all ledger accounts of a user for POSTED and REVERSED transactions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrialBalanceRows(int $userId, ?string $asOfDate = null): array
    {
        $dateClause = '';
        $params = [$userId];
        if ($asOfDate !== null && trim($asOfDate) !== '') {
            $dateClause = ' AND t.occurred_on <= ?';
            $params[] = trim($asOfDate);
        }

        $sql = "SELECT a.id AS account_id,
                       a.code AS account_code,
                       a.account_type,
                       a.wallet_id,
                       w.name AS wallet_name,
                       COUNT(e.id) AS entry_count,
                       COALESCE(SUM(CASE WHEN e.entry_side = 'DEBIT' THEN e.amount_base ELSE 0 END), 0) AS total_debit_base,
                       COALESCE(SUM(CASE WHEN e.entry_side = 'CREDIT' THEN e.amount_base ELSE 0 END), 0) AS total_credit_base
                FROM ledger_accounts a
                LEFT JOIN wallets w ON w.id = a.wallet_id
                LEFT JOIN ledger_entries e ON e.ledger_account_id = a.id
                LEFT JOIN financial_transactions t ON t.id = e.transaction_id
                    AND t.user_id = a.user_id
                    AND t.status IN ('POSTED', 'REVERSED'){$dateClause}
                WHERE a.user_id = ?
                GROUP BY a.id, a.code, a.account_type, a.wallet_id, w.name
                HAVING COUNT(t.id) > 0 OR a.wallet_id IS NOT NULL
                ORDER BY a.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($asOfDate !== null && trim($asOfDate) !== '' ? [trim($asOfDate)] : [], [$userId]));
        return $stmt->fetchAll();
    }

    /**
     * Fetch transactions matching filter criteria for CSV export (FR12.03).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findTransactionsForExport(int $userId, array $filters = [], int $maxRows = 10000): array
    {
        $maxRows = max(1, min(10000, $maxRows));
        $type = $filters['type'] ?? $filters['transactionType'] ?? null;
        $status = $filters['status'] ?? null;
        $categoryId = $filters['categoryId'] ?? null;
        $walletId = $filters['walletId'] ?? null;
        $startDate = $filters['startDate'] ?? $filters['from'] ?? null;
        $endDate = $filters['endDate'] ?? $filters['to'] ?? null;
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));

        $sql = "SELECT t.id,
                       t.transaction_type,
                       t.status,
                       t.description,
                       t.currency,
                       t.amount,
                       t.fx_rate_snapshot,
                       t.occurred_on,
                       t.posted_at,
                       t.original_transaction_id,
                       t.created_at,
                       c.name AS category_name,
                       tw.wallet_id,
                       tw.wallet_name,
                       tw.source_wallet_name,
                       tw.destination_wallet_name,
                       tw.amount_base
                FROM financial_transactions t
                LEFT JOIN categories c ON c.id = t.category_id
                LEFT JOIN (
                    SELECT e.transaction_id,
                           MAX(w.id) AS wallet_id,
                           MAX(w.name) AS wallet_name,
                           MAX(CASE WHEN e.entry_side = 'CREDIT' THEN w.name END) AS source_wallet_name,
                           MAX(CASE WHEN e.entry_side = 'DEBIT' THEN w.name END) AS destination_wallet_name,
                           MAX(e.amount_base) AS amount_base
                    FROM ledger_entries e
                    JOIN ledger_accounts a ON a.id = e.ledger_account_id
                    LEFT JOIN wallets w ON w.id = a.wallet_id
                    GROUP BY e.transaction_id
                ) tw ON tw.transaction_id = t.id
                WHERE t.user_id = ?";
        $params = [$userId];

        if (!empty($type)) {
            $sql .= ' AND t.transaction_type = ?';
            $params[] = strtoupper((string) $type);
        }
        if (!empty($status)) {
            $sql .= ' AND t.status = ?';
            $params[] = strtoupper((string) $status);
        } else {
            $sql .= " AND t.status IN ('POSTED', 'REVERSED')";
        }
        if (!empty($categoryId)) {
            $sql .= ' AND t.category_id = ?';
            $params[] = (int) $categoryId;
        }
        if (!empty($walletId)) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM ledger_entries we
                JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
                WHERE we.transaction_id = t.id AND wa.wallet_id = ?
            )';
            $params[] = (int) $walletId;
        }
        if (!empty($startDate)) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = (string) $startDate;
        }
        if (!empty($endDate)) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = (string) $endDate;
        }
        if ($search !== '') {
            $sql .= " AND (LOWER(t.description) LIKE ? OR LOWER(COALESCE(c.name, '')) LIKE ? OR LOWER(COALESCE(tw.wallet_name, '')) LIKE ?)";
            $kw = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $sql .= " ORDER BY t.occurred_on DESC, t.id DESC LIMIT {$maxRows}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSystemConfigInt(string $key, int $default): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            if ($value !== false && is_numeric($value)) {
                return (int) $value;
            }
        } catch (\Throwable) {
            // Ignore if table not yet migrated in custom environment
        }
        return $default;
    }

    public function logSecurityEvent(
        ?int $userId,
        string $eventType,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO security_audit_logs (user_id, event_type, ip_address, user_agent, metadata_json)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $eventType,
            $ipAddress,
            $userAgent,
            json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function logExportAudit(
        int $userId,
        string $exportType,
        int $rowCount,
        string $fileHash,
        ?string $ipAddress = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO export_audit_logs (user_id, export_type, row_count, file_hash, ip_address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $exportType, $rowCount, $fileHash, $ipAddress]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listExportAuditLogs(int $userId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, export_type, row_count, file_hash, ip_address, created_at
             FROM export_audit_logs
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
