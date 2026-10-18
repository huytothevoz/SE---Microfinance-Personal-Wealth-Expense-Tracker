<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use PDO;
use WealthFlow\Core\Database;

final class CategoryDAO
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

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId, bool $includeArchived = false): ?array
    {
        $sql = 'SELECT * FROM categories WHERE id = ? AND user_id = ?';
        if (!$includeArchived) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, ?string $type = null, bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM categories WHERE user_id = ?';
        $params = [$userId];
        if ($type !== null) {
            $sql .= ' AND category_type = ?';
            $params[] = strtoupper($type);
        }
        if (!$includeArchived) {
            $sql .= ' AND archived_at IS NULL';
        }
        $sql .= ' ORDER BY category_type ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function findDefaultCategories(?string $type = null): array
    {
        $sql = 'SELECT id, name, category_type FROM default_categories WHERE is_active = 1';
        $params = [];
        if ($type !== null) {
            $sql .= ' AND category_type = ?';
            $params[] = strtoupper($type);
        }
        $sql .= ' ORDER BY category_type ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function nameExists(int $userId, string $name, string $type, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM categories
                WHERE user_id = ? AND LOWER(name) = LOWER(?) AND category_type = ?';
        $params = [$userId, trim($name), strtoupper($type)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public function create(int $userId, string $name, string $categoryType): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, name, category_type) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, trim($name), strtoupper($categoryType)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $userId, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET name = ? WHERE id = ? AND user_id = ? AND archived_at IS NULL'
        );
        $stmt->execute([trim($name), $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET archived_at = CURRENT_TIMESTAMP
             WHERE id = ? AND user_id = ? AND archived_at IS NULL'
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function hasTransactions(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM financial_transactions WHERE category_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function getOrCreateCategoryLedgerAccountId(int $userId, int $categoryId, string $type): int
    {
        $code = ($type === 'INCOME' ? 'INCOME_CAT_' : 'EXPENSE_CAT_') . $categoryId;
        $accountType = ($type === 'INCOME' ? 'INCOME' : 'EXPENSE');

        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, code, account_type) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $code, $accountType]);
        return (int) $this->pdo->lastInsertId();
    }
}
