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
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, ?string $type = null): array
    {
        if ($type !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM categories WHERE user_id = ? AND category_type = ? AND archived_at IS NULL ORDER BY id ASC'
            );
            $stmt->execute([$userId, strtoupper($type)]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM categories WHERE user_id = ? AND archived_at IS NULL ORDER BY category_type ASC, id ASC'
            );
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
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
        $stmt = $this->pdo->prepare('UPDATE categories SET name = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([trim($name), $id, $userId]);
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET archived_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([$id, $userId]);
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
