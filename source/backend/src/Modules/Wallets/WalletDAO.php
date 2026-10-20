<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use RuntimeException;
use WealthFlow\Core\Database;

final class WalletDAO
{
    /** @var array<string, string> */
    private const REFERENCE_TABLES = [
        'recurring_schedules' => 'lịch định kỳ',
        'micro_loans' => 'khoản vay hoặc nợ',
        'gold_transactions' => 'giao dịch vàng',
        'financial_goals' => 'mục tiêu tài chính',
        'goal_allocations' => 'lịch sử phân bổ mục tiêu',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM wallets WHERE id = ? AND user_id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM wallets WHERE user_id = ?';
        if (!$includeArchived) {
            $sql .= " AND status = 'ACTIVE'";
        }
        $sql .= ' ORDER BY id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function nameExists(int $userId, string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM wallets WHERE user_id = ? AND LOWER(name) = LOWER(?)';
        $params = [$userId, trim($name)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public function create(
        int $userId,
        string $name,
        string $walletType,
        string $currency = 'VND',
        string $initialBalance = '0.00000000'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type, currency, cached_balance, status)
             VALUES (?, ?, ?, ?, ?, "ACTIVE")'
        );
        $stmt->execute([
            $userId,
            trim($name),
            strtoupper($walletType),
            strtoupper($currency),
            $initialBalance,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $userId, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets SET name = ? WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([trim($name), $id, $userId]);
    }

    public function setBalance(int $id, int $userId, string $balance): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET cached_balance = ? WHERE id = ? AND user_id = ? AND status = 'ACTIVE'"
        );
        $stmt->execute([$balance, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET status = 'ARCHIVED' WHERE id = ? AND user_id = ? AND status = 'ACTIVE' "
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM wallets WHERE id = ? AND user_id = ? AND status = 'ARCHIVED'"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    /** @return array<string, int> Human-readable reference label => count. */
    public function getReferenceCounts(int $walletId): array
    {
        $counts = [];
        $transactionCount = $this->countUsageTransactions($walletId);
        if ($transactionCount > 0) {
            $counts['giao dịch tài chính'] = $transactionCount;
        }
        foreach (self::REFERENCE_TABLES as $table => $label) {
            if (!$this->tableExists($table)) {
                continue;
            }
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE wallet_id = ?");
            $stmt->execute([$walletId]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                $counts[$label] = $count;
            }
        }
        return $counts;
    }

    /**
     * Return the wallet timeline. The synthetic creation event is deliberately
     * marked as non-usage so it never prevents deletion of an otherwise unused wallet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listHistory(int $walletId, int $userId, int $limit = 100): array
    {
        $wallet = $this->findByIdAndUser($walletId, $userId);
        if ($wallet === null) {
            return [];
        }

        $limit = max(1, min(200, $limit));
        $transactionLimit = $limit - 1;
        $openingCondition = $this->openingTransactionCondition('t');
        $sql = "SELECT t.id, t.transaction_type, t.status, t.description, t.currency,
                       t.amount, t.fx_rate_snapshot, t.occurred_on, t.posted_at, t.created_at,
                       c.name AS category_name, we.entry_side AS wallet_entry_side,
                       we.amount_original AS wallet_amount_original,
                       we.amount_base AS wallet_amount_base
                FROM financial_transactions t
                JOIN ledger_entries we ON we.transaction_id = t.id
                JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.user_id = ? AND wa.wallet_id = ? AND NOT ({$openingCondition})
                ORDER BY COALESCE(t.posted_at, t.created_at) DESC, t.id DESC
                LIMIT {$transactionLimit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $walletId]);
        $rows = $stmt->fetchAll();

        $history = array_map(static function (array $row): array {
            $row['kind'] = 'TRANSACTION';
            $row['counts_as_usage'] = true;
            return $row;
        }, $rows);

        $openingStmt = $this->pdo->prepare("SELECT t.id, we.amount_original
            FROM financial_transactions t
            JOIN ledger_entries we ON we.transaction_id = t.id
            JOIN ledger_accounts wa ON wa.id = we.ledger_account_id
            WHERE t.user_id = ? AND wa.wallet_id = ? AND {$openingCondition}
            ORDER BY t.id ASC LIMIT 1");
        $openingStmt->execute([$userId, $walletId]);
        $opening = $openingStmt->fetch();
        $openingAmount = $opening ? (string) $opening['amount_original'] : '0';
        $openingTransactionId = $opening ? (int) $opening['id'] : null;

        $history[] = [
            'id' => null,
            'transaction_id' => $openingTransactionId,
            'kind' => 'WALLET_CREATED',
            'counts_as_usage' => false,
            'transaction_type' => 'WALLET_CREATED',
            'status' => 'RECORDED',
            'description' => 'Đã tạo ví',
            'currency' => (string) $wallet['currency'],
            'amount' => $openingAmount,
            'wallet_amount_original' => $openingAmount,
            'wallet_amount_base' => null,
            'wallet_entry_side' => null,
            'category_name' => null,
            'occurred_on' => substr((string) $wallet['created_at'], 0, 10),
            'posted_at' => (string) $wallet['created_at'],
            'created_at' => (string) $wallet['created_at'],
        ];

        usort($history, static function (array $left, array $right): int {
            $leftAt = (string) ($left['posted_at'] ?: $left['created_at']);
            $rightAt = (string) ($right['posted_at'] ?: $right['created_at']);
            return strcmp($rightAt, $leftAt);
        });
        return $history;
    }

    /** Remove bookkeeping that belongs only to wallet creation before deleting the wallet. */
    public function deleteCreationBookkeeping(int $walletId, int $userId): void
    {
        if ($this->countUsageTransactions($walletId) > 0) {
            throw new RuntimeException('Ví đã có giao dịch sử dụng nên không thể dọn dữ liệu tạo ví.');
        }

        $openingCondition = $this->openingTransactionCondition('t');
        $stmt = $this->pdo->prepare("SELECT DISTINCT t.id
            FROM financial_transactions t
            JOIN ledger_entries e ON e.transaction_id = t.id
            JOIN ledger_accounts a ON a.id = e.ledger_account_id
            WHERE t.user_id = ? AND a.wallet_id = ? AND {$openingCondition}");
        $stmt->execute([$userId, $walletId]);
        $transactionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($transactionIds !== []) {
            $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
            $deleteKeys = $this->pdo->prepare("DELETE FROM idempotency_keys WHERE transaction_id IN ({$placeholders})");
            $deleteKeys->execute($transactionIds);
            $deleteEntries = $this->pdo->prepare("DELETE FROM ledger_entries WHERE transaction_id IN ({$placeholders})");
            $deleteEntries->execute($transactionIds);
            $deleteTransactions = $this->pdo->prepare("DELETE FROM financial_transactions WHERE id IN ({$placeholders})");
            $deleteTransactions->execute($transactionIds);
        }

        $deleteAccounts = $this->pdo->prepare('DELETE FROM ledger_accounts WHERE wallet_id = ? AND user_id = ?');
        $deleteAccounts->execute([$walletId, $userId]);

        // Zero-balance wallet creation has no transaction_id. Remove its stale replay response as well.
        $deleteCreateReplay = $this->pdo->prepare(
            'DELETE FROM idempotency_keys WHERE user_id = ? AND transaction_id IS NULL AND response_json LIKE ?'
        );
        $deleteCreateReplay->execute([$userId, '%"id":' . $walletId . ',%']);
    }

    private function countUsageTransactions(int $walletId): int
    {
        if (!$this->tableExists('ledger_accounts') || !$this->tableExists('ledger_entries')) {
            return 0;
        }
        $openingCondition = $this->openingTransactionCondition('t');
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT t.id)
            FROM financial_transactions t
            JOIN ledger_entries e ON e.transaction_id = t.id
            JOIN ledger_accounts a ON a.id = e.ledger_account_id
            WHERE a.wallet_id = ? AND NOT ({$openingCondition})");
        $stmt->execute([$walletId]);
        return (int) $stmt->fetchColumn();
    }

    private function openingTransactionCondition(string $transactionAlias): string
    {
        return "{$transactionAlias}.transaction_type = 'OTHER'
            AND {$transactionAlias}.description LIKE 'Số dư mở sổ ví:%'
            AND EXISTS (
                SELECT 1
                FROM ledger_entries opening_entry
                JOIN ledger_accounts opening_account ON opening_account.id = opening_entry.ledger_account_id
                WHERE opening_entry.transaction_id = {$transactionAlias}.id
                  AND opening_account.wallet_id IS NULL
                  AND opening_account.code = 'EQUITY_OPENING_BALANCE'
            )";
    }

    private function tableExists(string $table): bool
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $stmt = $this->pdo->prepare(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
            );
            $stmt->execute([$table]);
            return $stmt->fetchColumn() !== false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$table]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Atomically adjust wallet balance with strict BCMath decimal precision (BR01, BR06).
     *
     * @param string|float|int $delta Signed decimal string (e.g. "-150000.00000000" or "500000.00000000")
     * @return string New cached_balance formatted as string
     */
    public function adjustBalance(int $walletId, string|float|int $delta, bool $allowNegative = false): string
    {
        $deltaStr = is_string($delta) ? $delta : number_format((float) $delta, 8, '.', '');
        $isNegative = bccomp($deltaStr, '0', 8) < 0;

        $sql = 'UPDATE wallets SET cached_balance = cached_balance + ? WHERE id = ?';
        $params = [$deltaStr, $walletId];

        if ($isNegative && !$allowNegative) {
            $absDelta = bcmul($deltaStr, '-1', 8);
            $sql .= " AND (wallet_type = 'CREDIT' OR cached_balance >= ?)";
            $params[] = $absDelta;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Số dư ví không đủ chi trả khoản chi này.', 422);
        }

        $balanceStmt = $this->pdo->prepare('SELECT cached_balance FROM wallets WHERE id = ?');
        $balanceStmt->execute([$walletId]);
        $value = $balanceStmt->fetchColumn();

        if ($value === false) {
            throw new RuntimeException('Ví không còn tồn tại.');
        }

        return (string) $value;
    }

    public function getOrCreateWalletLedgerAccountId(int $userId, int $walletId): int
    {
        $wallet = $this->findByIdAndUser($walletId, $userId);
        if (!$wallet) throw new RuntimeException('Ví không tồn tại.');
        $liability = $wallet['wallet_type'] === 'CREDIT';
        $code = ($liability ? 'LIABILITY_WALLET_' : 'ASSET_WALLET_') . $walletId;
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $walletId, $code, $liability ? 'LIABILITY' : 'ASSET']);
        return (int) $this->pdo->lastInsertId();
    }

    public function getOrCreateOpeningEquityAccountId(int $userId): int
    {
        $code = 'EQUITY_OPENING_BALANCE';
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, NULL, ?, ?)'
        );
        $stmt->execute([$userId, $code, 'EQUITY']);
        return (int) $this->pdo->lastInsertId();
    }

    public function getOrCreateBalanceAdjustmentAccountId(int $userId): int
    {
        $code = 'EQUITY_BALANCE_ADJUSTMENT';
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, NULL, ?, "EQUITY")'
        );
        $stmt->execute([$userId, $code]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getUserBaseCurrency(int $userId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT base_currency FROM users WHERE id = ? AND status = 'ACTIVE'");
        $stmt->execute([$userId]);
        $currency = $stmt->fetchColumn();
        return $currency === false ? null : strtoupper((string) $currency);
    }
}
