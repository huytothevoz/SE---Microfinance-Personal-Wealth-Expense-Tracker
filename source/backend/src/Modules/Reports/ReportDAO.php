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
     * Compute cumulative debit minus credit of a wallet from posted ledger entries.
     * Returns string formatted with 8 decimals (e.g. '15000000.00000000').
     */
    public function getWalletLedgerSum(int $walletId, int $userId): string
    {
        $sql = "SELECT COALESCE(
                    SUM(CASE WHEN e.entry_side = 'DEBIT' THEN e.amount_original ELSE -e.amount_original END),
                    0
                ) AS ledger_sum
                FROM ledger_entries e
                JOIN ledger_accounts a ON a.id = e.ledger_account_id
                JOIN financial_transactions t ON t.id = e.transaction_id
                WHERE a.wallet_id = ?
                  AND a.user_id = ?
                  AND t.status = 'POSTED'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$walletId, $userId]);
        $val = $stmt->fetchColumn();

        if ($val === false || $val === null) {
            return '0.00000000';
        }

        return number_format((float) $val, 8, '.', '');
    }

    /**
     * Fetch all active wallets for a user along with their cached balance
     * and computed ledger sum for reconciliation dashboard.
     *
     * @return array<int, array{
     *     walletId: int,
     *     walletName: string,
     *     currency: string,
     *     walletType: string,
     *     cachedBalance: string,
     *     ledgerSum: string,
     *     delta: string,
     *     status: string
     * }>
     */
    public function getReconciliationSummary(int $userId): array
    {
        $sql = "SELECT w.id AS wallet_id,
                       w.name AS wallet_name,
                       w.currency,
                       w.wallet_type,
                       w.cached_balance,
                       COALESCE(l.ledger_sum, 0) AS raw_ledger_sum
                FROM wallets w
                LEFT JOIN (
                    SELECT a.wallet_id,
                           SUM(CASE WHEN e.entry_side = 'DEBIT' THEN e.amount_original ELSE -e.amount_original END) AS ledger_sum
                    FROM ledger_entries e
                    JOIN ledger_accounts a ON a.id = e.ledger_account_id
                    JOIN financial_transactions t ON t.id = e.transaction_id
                    WHERE a.user_id = ?
                      AND a.wallet_id IS NOT NULL
                      AND t.status = 'POSTED'
                    GROUP BY a.wallet_id
                ) l ON l.wallet_id = w.id
                WHERE w.user_id = ? AND w.status = 'ACTIVE'
                ORDER BY w.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $rows = $stmt->fetchAll();

        $results = [];
        foreach ($rows as $row) {
            $cached = number_format((float) $row['cached_balance'], 8, '.', '');
            $ledger = number_format((float) $row['raw_ledger_sum'], 8, '.', '');
            $delta = bcsub($cached, $ledger, 8);
            $matched = bccomp($delta, '0.00000000', 8) === 0;

            $results[] = [
                'walletId' => (int) $row['wallet_id'],
                'walletName' => (string) $row['wallet_name'],
                'currency' => (string) $row['currency'],
                'walletType' => (string) $row['wallet_type'],
                'cachedBalance' => $cached,
                'ledgerSum' => $ledger,
                'delta' => $delta,
                'status' => $matched ? 'MATCHED' : 'MISMATCH',
            ];
        }

        return $results;
    }

    /**
     * Trial Balance: aggregates all debit and credit movements in base currency (VND).
     * Verifies global ledger equilibrium (sum(Debit) == sum(Credit)).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrialBalanceRows(int $userId, ?string $asOfDate = null): array
    {
        $sql = "SELECT a.id,
                       a.code,
                       a.account_type,
                       a.wallet_id,
                       w.name AS wallet_name,
                       COALESCE(SUM(CASE WHEN e.entry_side = 'DEBIT' THEN e.amount_base ELSE 0 END), 0) AS total_debit,
                       COALESCE(SUM(CASE WHEN e.entry_side = 'CREDIT' THEN e.amount_base ELSE 0 END), 0) AS total_credit
                FROM ledger_accounts a
                LEFT JOIN wallets w ON w.id = a.wallet_id
                LEFT JOIN ledger_entries e ON e.ledger_account_id = a.id
                LEFT JOIN financial_transactions t ON t.id = e.transaction_id AND t.status = 'POSTED' ";

        $params = [];
        if ($asOfDate !== null && trim($asOfDate) !== '') {
            $sql .= " AND t.occurred_on <= ? ";
            $params[] = trim($asOfDate);
        }

        $sql .= "WHERE a.user_id = ?
                 GROUP BY a.id, a.code, a.account_type, a.wallet_id, w.name
                 ORDER BY a.account_type ASC, a.code ASC";
        $params[] = $userId;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Query detailed transactions for CSV export (up to 10,000 records).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTransactionsForExport(int $userId, array $filters = []): array
    {
        $sql = "SELECT t.id,
                       t.occurred_on,
                       t.transaction_type,
                       t.description,
                       c.name AS category_name,
                       COALESCE(tw.source_wallet_name, tw.wallet_name) AS wallet_name,
                       tw.destination_wallet_name,
                       t.amount,
                       t.currency,
                       t.fx_rate_snapshot,
                       (t.amount * t.fx_rate_snapshot) AS amount_base,
                       t.status,
                       t.created_at
                FROM financial_transactions t
                LEFT JOIN categories c ON c.id = t.category_id
                LEFT JOIN (
                    SELECT e.transaction_id,
                           MAX(w.name) AS wallet_name,
                           MAX(CASE WHEN e.entry_side = 'CREDIT' THEN w.name END) AS source_wallet_name,
                           MAX(CASE WHEN e.entry_side = 'DEBIT' THEN w.name END) AS destination_wallet_name
                    FROM ledger_entries e
                    JOIN ledger_accounts a ON a.id = e.ledger_account_id
                    JOIN wallets w ON w.id = a.wallet_id
                    GROUP BY e.transaction_id
                ) tw ON tw.transaction_id = t.id
                WHERE t.user_id = ?";

        $params = [$userId];

        if (!empty($filters['walletId'])) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM ledger_entries we
                JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
                WHERE we.transaction_id = t.id AND wa.wallet_id = ?
            )";
            $params[] = (int) $filters['walletId'];
        }

        if (!empty($filters['startDate'])) {
            $sql .= " AND t.occurred_on >= ?";
            $params[] = $filters['startDate'];
        }

        if (!empty($filters['endDate'])) {
            $sql .= " AND t.occurred_on <= ?";
            $params[] = $filters['endDate'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND t.transaction_type = ?";
            $params[] = strtoupper((string) $filters['type']);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = strtoupper((string) $filters['status']);
        }

        $sql .= " ORDER BY t.occurred_on DESC, t.id DESC LIMIT 10000";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count posted transactions for a user (excluding drafts).
     */
    public function countPostedTransactions(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM financial_transactions WHERE user_id = ? AND status = 'POSTED'"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
