<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use PDO;
use WealthFlow\Config\Database;

final class TransactionRepository
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
     *     amount: float|string,
     *     fxRateSnapshot: float|string,
     *     occurredOn: string,
     *     postedAt: ?string,
     *     originalTransactionId: ?int
     * } $data
     */
    public function createTransaction(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions 
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on, posted_at, original_transaction_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['userId'],
            $data['categoryId'],
            $data['type'],
            $data['status'],
            $data['description'],
            $data['currency'],
            (float) $data['amount'],
            (float) $data['fxRateSnapshot'],
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
     *     amountOriginal: float|string,
     *     currency: string,
     *     amountBase: float|string,
     *     fxRateSnapshot: float|string
     * } $data
     */
    public function createLedgerEntry(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ledger_entries 
            (transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) 
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['transactionId'],
            $data['ledgerAccountId'],
            $data['entrySide'],
            (float) $data['amountOriginal'],
            $data['currency'],
            (float) $data['amountBase'],
            (float) $data['fxRateSnapshot'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $txId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT t.*, c.name as category_name 
            FROM financial_transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE t.id = ? AND t.user_id = ?');
        $stmt->execute([$txId, $userId]);
        $tx = $stmt->fetch();
        if (!$tx) {
            return null;
        }

        // Fetch ledger entries
        $stmt = $this->pdo->prepare('SELECT e.*, a.code as account_code, a.account_type, a.wallet_id 
            FROM ledger_entries e 
            JOIN ledger_accounts a ON e.ledger_account_id = a.id 
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
        $sql = 'SELECT t.*, c.name as category_name 
            FROM financial_transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ?';
        $params = [$userId];

        if (!empty($filters['type'])) {
            $sql .= ' AND t.transaction_type = ?';
            $params[] = strtoupper((string) $filters['type']);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND t.status = ?';
            $params[] = strtoupper((string) $filters['status']);
        }
        if (!empty($filters['categoryId'])) {
            $sql .= ' AND t.category_id = ?';
            $params[] = (int) $filters['categoryId'];
        }
        if (!empty($filters['startDate'])) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = $filters['startDate'];
        }
        if (!empty($filters['endDate'])) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = $filters['endDate'];
        }

        $sql .= ' ORDER BY t.occurred_on DESC, t.id DESC';

        $limit = isset($filters['limit']) ? max(1, min(100, (int) $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $txId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE financial_transactions SET status = ? WHERE id = ?');
        $stmt->execute([strtoupper($status), $txId]);
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
