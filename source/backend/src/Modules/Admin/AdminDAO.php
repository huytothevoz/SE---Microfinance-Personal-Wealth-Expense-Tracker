<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use PDO;
use WealthFlow\Core\Database;

final class AdminDAO
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
     * @return array<int, array<string, mixed>>
     */
    public function getAllDefaultCategories(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, name, category_type, is_active FROM default_categories';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY category_type ASC, name ASC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'categoryType' => (string) $r['category_type'],
                'isActive' => (bool) $r['is_active'],
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDefaultCategoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, category_type, is_active FROM default_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'categoryType' => (string) $row['category_type'],
            'isActive' => (bool) $row['is_active'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDefaultCategoryByNameAndType(string $name, string $categoryType, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT id, name, category_type, is_active FROM default_categories WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND category_type = ?';
        $params = [$name, strtoupper(trim($categoryType))];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createDefaultCategory(string $name, string $categoryType, bool $isActive = true): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO default_categories (name, category_type, is_active) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            trim($name),
            strtoupper(trim($categoryType)),
            $isActive ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateDefaultCategory(int $id, array $fields): bool
    {
        $setClauses = [];
        $params = [];

        if (array_key_exists('name', $fields)) {
            $setClauses[] = 'name = ?';
            $params[] = trim((string) $fields['name']);
        }
        if (array_key_exists('categoryType', $fields)) {
            $setClauses[] = 'category_type = ?';
            $params[] = strtoupper(trim((string) $fields['categoryType']));
        }
        if (array_key_exists('isActive', $fields)) {
            $setClauses[] = 'is_active = ?';
            $params[] = $fields['isActive'] ? 1 : 0;
        }

        if (empty($setClauses)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE default_categories SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function deleteDefaultCategory(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM default_categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Lấy toàn bộ tham số cấu hình hệ thống từ bảng system_config (FR13.02)
     * @return array<string, string>
     */
    public function getSystemConfigs(): array
    {
        $stmt = $this->pdo->query('SELECT config_key, config_value FROM system_config');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    }

    /**
     * Lấy giá trị của một tham số hệ thống cụ thể (FR13.02)
     */
    public function getSystemConfig(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT config_value FROM system_config WHERE config_key = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    /**
     * Cập nhật hoặc thêm mới tham số hệ thống (FR13.02)
     */
    public function updateSystemConfig(string $key, string $value): bool
    {
        $trimmedKey = trim($key);
        $trimmedValue = trim($value);

        $checkStmt = $this->pdo->prepare('SELECT COUNT(*) FROM system_config WHERE config_key = ?');
        $checkStmt->execute([$trimmedKey]);
        if ((int) $checkStmt->fetchColumn() > 0) {
            $update = $this->pdo->prepare('UPDATE system_config SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE config_key = ?');
            return $update->execute([$trimmedValue, $trimmedKey]);
        }

        $insert = $this->pdo->prepare('INSERT INTO system_config (config_key, config_value) VALUES (?, ?)');
        return $insert->execute([$trimmedKey, $trimmedValue]);
    }
}
