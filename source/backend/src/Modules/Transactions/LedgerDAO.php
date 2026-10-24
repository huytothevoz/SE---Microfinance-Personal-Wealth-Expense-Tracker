<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use PDO;
use WealthFlow\Core\Database;

final class LedgerDAO
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
     *     categoryId: ?int,
     *     type: string,
     *     status: string,
     *     description: string,
     *     currency: string,
     *     amount: float|string|int,
     *     fxRateSnapshot: float|string|int,
     *     occurredOn: string,
     *     postedAt: ?string,
     *     originalTransactionId: ?int
     * } $data
     */
    public function createTransaction(array $data): int
    {
        $amountStr = is_string($data['amount']) ? $data['amount'] : number_format((float) $data['amount'], 8, '.', '');
        $fxRateStr = is_string($data['fxRateSnapshot']) ? $data['fxRateSnapshot'] : number_format((float) $data['fxRateSnapshot'], 8, '.', '');

        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on, posted_at, original_transaction_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['userId'],
            $data['categoryId'],
            strtoupper($data['type']),
            strtoupper($data['status']),
            $data['description'],
            strtoupper($data['currency']),
            $amountStr,
            $fxRateStr,
            $data['occurredOn'],
            $data['postedAt'],
            $data['originalTransactionId'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{
     *     transactionId: int,
     *     ledgerAccountId: int,
     *     entrySide: string,
     *     amountOriginal: float|string|int,
     *     currency: string,
     *     amountBase: float|string|int,
     *     fxRateSnapshot: float|string|int
     * } $data
     */
    public function createLedgerEntry(array $data): int
    {
        $amountOrigStr = is_string($data['amountOriginal']) ? $data['amountOriginal'] : number_format((float) $data['amountOriginal'], 8, '.', '');
        $amountBaseStr = is_string($data['amountBase']) ? $data['amountBase'] : number_format((float) $data['amountBase'], 8, '.', '');
        $fxRateStr = is_string($data['fxRateSnapshot']) ? $data['fxRateSnapshot'] : number_format((float) $data['fxRateSnapshot'], 8, '.', '');

        $stmt = $this->pdo->prepare('INSERT INTO ledger_entries
            (transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['transactionId'],
            $data['ledgerAccountId'],
            strtoupper($data['entrySide']),
            $amountOrigStr,
            strtoupper($data['currency']),
            $amountBaseStr,
            $fxRateStr,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $txId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT t.*, c.name as category_name,
                   tw.wallet_id, tw.wallet_name, tw.source_wallet_name, tw.destination_wallet_name
            FROM financial_transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN (
                SELECT e.transaction_id,
                       MAX(w.id) AS wallet_id,
                       MAX(w.name) AS wallet_name,
                       MAX(CASE WHEN e.entry_side = \'CREDIT\' THEN w.name END) AS source_wallet_name,
                       MAX(CASE WHEN e.entry_side = \'DEBIT\' THEN w.name END) AS destination_wallet_name
                FROM ledger_entries e
                JOIN ledger_accounts a ON a.id = e.ledger_account_id
                JOIN wallets w ON w.id = a.wallet_id
                GROUP BY e.transaction_id
            ) tw ON tw.transaction_id = t.id
            WHERE t.id = ? AND t.user_id = ?');
        $stmt->execute([$txId, $userId]);
        $tx = $stmt->fetch();
        if (!$tx) {
            return null;
        }

        // Fetch ledger entries
        $stmt = $this->pdo->prepare('SELECT e.*, a.code as account_code, a.account_type, a.wallet_id,
                   w.name AS wallet_name
            FROM ledger_entries e
            JOIN ledger_accounts a ON e.ledger_account_id = a.id
            LEFT JOIN wallets w ON w.id = a.wallet_id
            WHERE e.transaction_id = ? ORDER BY e.id ASC');
        $stmt->execute([$txId]);
        $tx['entries'] = $stmt->fetchAll();

        return $tx;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listTransactions(int $userId, array $filters = []): array
    {
        $type = $filters['type'] ?? $filters['transactionType'] ?? null;
        $startDate = $filters['startDate'] ?? $filters['from'] ?? null;
        $endDate = $filters['endDate'] ?? $filters['to'] ?? null;
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));

        $sql = 'SELECT t.*, c.name as category_name,
                       tw.wallet_id, tw.wallet_name, tw.source_wallet_name, tw.destination_wallet_name
            FROM financial_transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN (
                SELECT e.transaction_id,
                       MAX(w.id) AS wallet_id,
                       MAX(w.name) AS wallet_name,
                       MAX(CASE WHEN e.entry_side = \'CREDIT\' THEN w.name END) AS source_wallet_name,
                       MAX(CASE WHEN e.entry_side = \'DEBIT\' THEN w.name END) AS destination_wallet_name
                FROM ledger_entries e
                JOIN ledger_accounts a ON a.id = e.ledger_account_id
                JOIN wallets w ON w.id = a.wallet_id
                GROUP BY e.transaction_id
            ) tw ON tw.transaction_id = t.id
            WHERE t.user_id = ?';
        $params = [$userId];

        if (!empty($type)) {
            $sql .= ' AND t.transaction_type = ?';
            $params[] = strtoupper((string) $type);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND t.status = ?';
            $params[] = strtoupper((string) $filters['status']);
        }
        if (!empty($filters['categoryId'])) {
            $sql .= ' AND t.category_id = ?';
            $params[] = (int) $filters['categoryId'];
        }
        if (!empty($filters['walletId'])) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM ledger_entries we
                JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
                WHERE we.transaction_id = t.id AND wa.wallet_id = ?
            )';
            $params[] = (int) $filters['walletId'];
        }
        if (!empty($startDate)) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = $endDate;
        }
        if ($search !== '') {
            $sql .= ' AND (LOWER(t.description) LIKE ? OR LOWER(COALESCE(c.name, \'\')) LIKE ? OR LOWER(COALESCE(tw.wallet_name, \'\')) LIKE ?)';
            $kw = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $sql .= ' ORDER BY t.occurred_on DESC, t.id DESC';

        $limit = isset($filters['limit']) ? max(1, min(200, (int) $filters['limit'])) : 100;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $window = max(1, $offset + $limit);
        $sql .= " LIMIT {$window}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = array_merge($stmt->fetchAll(), $this->listGoalTransactions($userId, $filters, $window));
        usort($rows, static function (array $left, array $right): int {
            $dateOrder = strcmp((string) ($right['occurred_on'] ?? ''), (string) ($left['occurred_on'] ?? ''));
            if ($dateOrder !== 0) return $dateOrder;
            $sourceOrder = ((int) ($right['_source_priority'] ?? 0)) <=> ((int) ($left['_source_priority'] ?? 0));
            if ($sourceOrder !== 0) return $sourceOrder;
            return ((int) ($right['_sort_id'] ?? $right['id'] ?? 0)) <=> ((int) ($left['_sort_id'] ?? $left['id'] ?? 0));
        });
        $rows = array_slice($rows, $offset, $limit);
        foreach ($rows as &$row) unset($row['_sort_id'], $row['_source_priority']);
        unset($row);
        return $rows;
    }

    /**
     * Count matching transactions for pagination metadata (FR12.01).
     * @param array<string, mixed> $filters
     */
    public function countTransactions(int $userId, array $filters = []): int
    {
        $type = $filters['type'] ?? $filters['transactionType'] ?? null;
        $startDate = $filters['startDate'] ?? $filters['from'] ?? null;
        $endDate = $filters['endDate'] ?? $filters['to'] ?? null;
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));

        $sql = 'SELECT COUNT(*)
            FROM financial_transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN (
                SELECT e.transaction_id, MAX(w.name) AS wallet_name
                FROM ledger_entries e
                JOIN ledger_accounts a ON a.id = e.ledger_account_id
                JOIN wallets w ON w.id = a.wallet_id
                GROUP BY e.transaction_id
            ) tw ON tw.transaction_id = t.id
            WHERE t.user_id = ?';
        $params = [$userId];

        if (!empty($type)) {
            $sql .= ' AND t.transaction_type = ?';
            $params[] = strtoupper((string) $type);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND t.status = ?';
            $params[] = strtoupper((string) $filters['status']);
        }
        if (!empty($filters['categoryId'])) {
            $sql .= ' AND t.category_id = ?';
            $params[] = (int) $filters['categoryId'];
        }
        if (!empty($filters['walletId'])) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM ledger_entries we
                JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
                WHERE we.transaction_id = t.id AND wa.wallet_id = ?
            )';
            $params[] = (int) $filters['walletId'];
        }
        if (!empty($startDate)) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = $endDate;
        }
        if ($search !== '') {
            $sql .= ' AND (LOWER(t.description) LIKE ? OR LOWER(COALESCE(c.name, \'\')) LIKE ? OR LOWER(COALESCE(tw.wallet_name, \'\')) LIKE ?)';
            $kw = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $txCount = (int) $stmt->fetchColumn();
        $goalCount = count($this->listGoalTransactions($userId, $filters, 1000));
        return $txCount + $goalCount;
    }

    /**
     * Goal allocations are read-only transaction-history items. They do not create
     * ledger entries because earmarking does not change the wallet's actual balance.
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function listGoalTransactions(int $userId, array $filters, int $limit): array
    {
        $type = strtoupper(trim((string) ($filters['type'] ?? $filters['transactionType'] ?? '')));
        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        $startDate = $filters['startDate'] ?? $filters['from'] ?? null;
        $endDate = $filters['endDate'] ?? $filters['to'] ?? null;
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));

        if (($type !== '' && $type !== 'SAVINGS_GOAL')
            || ($status !== '' && $status !== 'POSTED')
            || !empty($filters['categoryId'])) {
            return [];
        }

        $sql = "SELECT ('goal-' || a.id) AS id,
                       a.id AS _sort_id,
                       1 AS _source_priority,
                       'SAVINGS_GOAL' AS transaction_type,
                       'POSTED' AS status,
                       CASE WHEN a.allocation_type = 'DEPOSIT'
                            THEN ('Chuyển vào mục tiêu: ' || g.name)
                            ELSE ('Lấy khỏi mục tiêu: ' || g.name)
                       END AS description,
                       'VND' AS currency,
                       a.amount,
                       1 AS fx_rate_snapshot,
                       a.allocated_on AS occurred_on,
                       NULL AS posted_at,
                       'Mục tiêu tiết kiệm' AS category_name,
                       w.id AS wallet_id,
                       w.name AS wallet_name,
                       NULL AS source_wallet_name,
                       NULL AS destination_wallet_name,
                       a.allocation_type AS goal_action,
                       g.name AS goal_name,
                       a.note
                FROM goal_allocations a
                JOIN financial_goals g ON g.id = a.goal_id
                LEFT JOIN wallets w ON w.id = a.wallet_id
                WHERE g.user_id = ?";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql = "SELECT CONCAT('goal-', a.id) AS id,
                           a.id AS _sort_id,
                           1 AS _source_priority,
                           'SAVINGS_GOAL' AS transaction_type,
                           'POSTED' AS status,
                           CASE WHEN a.allocation_type = 'DEPOSIT'
                                THEN CONCAT('Chuyển vào mục tiêu: ', g.name)
                                ELSE CONCAT('Lấy khỏi mục tiêu: ', g.name)
                           END AS description,
                           'VND' AS currency,
                           a.amount,
                           1 AS fx_rate_snapshot,
                           a.allocated_on AS occurred_on,
                           NULL AS posted_at,
                           'Mục tiêu tiết kiệm' AS category_name,
                           w.id AS wallet_id,
                           w.name AS wallet_name,
                           NULL AS source_wallet_name,
                           NULL AS destination_wallet_name,
                           a.allocation_type AS goal_action,
                           g.name AS goal_name,
                           a.note
                    FROM goal_allocations a
                    JOIN financial_goals g ON g.id = a.goal_id
                    LEFT JOIN wallets w ON w.id = a.wallet_id
                    WHERE g.user_id = ?";
        }
        $params = [$userId];
        if (!empty($filters['walletId'])) {
            $sql .= ' AND a.wallet_id = ?';
            $params[] = (int) $filters['walletId'];
        }
        if (!empty($startDate)) {
            $sql .= ' AND a.allocated_on >= ?';
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= ' AND a.allocated_on <= ?';
            $params[] = $endDate;
        }
        if ($search !== '') {
            $sql .= ' AND (LOWER(g.name) LIKE ? OR LOWER(COALESCE(a.note, \'\')) LIKE ?)';
            $kw = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $kw;
            $params[] = $kw;
        }
        $sql .= " ORDER BY a.allocated_on DESC, a.id DESC LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $txId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE financial_transactions SET status = ? WHERE id = ?');
        $stmt->execute([strtoupper($status), $txId]);
    }

    public function markReversed(int $txId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE financial_transactions SET status = 'REVERSED' WHERE id = ? AND user_id = ? AND status = 'POSTED'"
        );
        $stmt->execute([$txId, $userId]);
        return $stmt->rowCount() === 1;
    }

    /** @return array<string, mixed>|null */
    public function findIdempotency(int $userId, string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM idempotency_keys WHERE user_id = ? AND request_key = ?');
        $stmt->execute([$userId, $key]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function saveIdempotency(int $userId, string $key, string $hash, ?int $txId, int $code, string $responseJson): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO idempotency_keys
            (user_id, request_key, request_hash, transaction_id, result_code, response_json)
            VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $key, $hash, $txId, $code, $responseJson]);
    }
}
