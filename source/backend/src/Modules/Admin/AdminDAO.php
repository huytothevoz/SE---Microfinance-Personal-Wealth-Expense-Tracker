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

    /**
     * Macro overview dashboard statistics (FR14.01, FR14.02, BR11).
     *
     * @return array<string, mixed>
     */
    public function getOverviewStats(): array
    {
        $monthPrefix = date('Y-m') . '%';

        $summarySql = "SELECT COUNT(*) AS total_users,
                              SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) AS active_users,
                              SUM(CASE WHEN status = 'LOCKED' THEN 1 ELSE 0 END) AS locked_users,
                              SUM(CASE WHEN role = 'ADMIN' THEN 1 ELSE 0 END) AS admin_users,
                              SUM(CASE WHEN role = 'USER' THEN 1 ELSE 0 END) AS personal_users,
                              SUM(CASE WHEN CAST(created_at AS CHAR) LIKE ? THEN 1 ELSE 0 END) AS new_users_this_month
                       FROM users";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $summarySql = "SELECT COUNT(*) AS total_users,
                                  SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) AS active_users,
                                  SUM(CASE WHEN status = 'LOCKED' THEN 1 ELSE 0 END) AS locked_users,
                                  SUM(CASE WHEN role = 'ADMIN' THEN 1 ELSE 0 END) AS admin_users,
                                  SUM(CASE WHEN role = 'USER' THEN 1 ELSE 0 END) AS personal_users,
                                  SUM(CASE WHEN CAST(created_at AS TEXT) LIKE ? THEN 1 ELSE 0 END) AS new_users_this_month
                           FROM users";
        }
        $stmt = $this->pdo->prepare($summarySql);
        $stmt->execute([$monthPrefix]);
        $row = $stmt->fetch() ?: [];

        $currencyStmt = $this->pdo->query(
            'SELECT base_currency, COUNT(*) AS user_count
             FROM users
             GROUP BY base_currency
             ORDER BY user_count DESC, base_currency ASC'
        );
        $currencyDistribution = [];
        if ($currencyStmt) {
            foreach ($currencyStmt->fetchAll() as $cRow) {
                $currencyDistribution[] = [
                    'currency' => (string) $cRow['base_currency'],
                    'count' => (int) $cRow['user_count'],
                ];
            }
        }

        $monthExpr = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? 'substr(CAST(created_at AS TEXT), 1, 7)'
            : 'LEFT(CAST(created_at AS CHAR), 7)';
        $trendStmt = $this->pdo->query(
            "SELECT {$monthExpr} AS month_key, COUNT(*) AS signups
             FROM users
             GROUP BY {$monthExpr}
             ORDER BY month_key DESC
             LIMIT 6"
        );
        $monthlyRegistrations = [];
        if ($trendStmt) {
            $monthlyRegistrations = array_reverse(array_map(static fn(array $r): array => [
                'month' => (string) $r['month_key'],
                'count' => (int) $r['signups'],
            ], $trendStmt->fetchAll()));
        }

        $catCountStmt = $this->pdo->query('SELECT COUNT(*) FROM default_categories WHERE is_active = 1');
        $activeDefaultCategories = $catCountStmt ? (int) $catCountStmt->fetchColumn() : 0;

        $broadcastCountStmt = $this->pdo->query(
            "SELECT COUNT(*) FROM admin_audit_logs WHERE action = 'BROADCAST_ANNOUNCEMENT'"
        );
        $totalBroadcasts = $broadcastCountStmt ? (int) $broadcastCountStmt->fetchColumn() : 0;

        return [
            'totalUsers' => (int) ($row['total_users'] ?? 0),
            'activeUsers' => (int) ($row['active_users'] ?? 0),
            'lockedUsers' => (int) ($row['locked_users'] ?? 0),
            'adminUsers' => (int) ($row['admin_users'] ?? 0),
            'personalUsers' => (int) ($row['personal_users'] ?? 0),
            'newUsersThisMonth' => (int) ($row['new_users_this_month'] ?? 0),
            'activeDefaultCategories' => $activeDefaultCategories,
            'totalBroadcasts' => $totalBroadcasts,
            'currencyDistribution' => $currencyDistribution,
            'monthlyRegistrations' => $monthlyRegistrations,
        ];
    }

    /**
     * List user accounts with macro metadata only (strictly no personal wallet or transaction amounts per BR11).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(array $filters = []): array
    {
        $sql = 'SELECT id, full_name, email, role, status, base_currency, created_at, updated_at
                FROM users
                WHERE 1 = 1';
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= ' AND role = ?';
            $params[] = strtoupper((string) $filters['role']);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = strtoupper((string) $filters['status']);
        }
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (LOWER(full_name) LIKE ? OR LOWER(email) LIKE ?)';
            $kw = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $kw;
            $params[] = $kw;
        }

        $sql .= ' ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, role, status, base_currency, created_at, updated_at
             FROM users
             WHERE id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function countActiveAdminsExcept(int $excludeUserId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE role = 'ADMIN' AND status = 'ACTIVE' AND id <> ?"
        );
        $stmt->execute([$excludeUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function updateUserStatus(int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        );
        return $stmt->execute([strtoupper($status), $userId]);
    }

    /**
     * Revoke all active refresh/access sessions of a user upon account lock (FR14.03, SQ08).
     */
    public function revokeUserSessions(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM auth_sessions WHERE user_id = ?');
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }
}
