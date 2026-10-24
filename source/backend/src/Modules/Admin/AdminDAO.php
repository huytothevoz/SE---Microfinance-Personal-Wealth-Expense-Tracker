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
     * Retrieve macro system statistics strictly without accessing personal financial balances/transactions (BR11, FR14.01, FR14.02).
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
        foreach ($currencyStmt->fetchAll() as $cRow) {
            $currencyDistribution[] = [
                'currency' => (string) $cRow['base_currency'],
                'count' => (int) $cRow['user_count'],
            ];
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
        $monthlyRegistrations = array_reverse(array_map(static fn(array $r): array => [
            'month' => (string) $r['month_key'],
            'count' => (int) $r['signups'],
        ], $trendStmt->fetchAll()));

        $catCountStmt = $this->pdo->query('SELECT COUNT(*) FROM default_categories WHERE is_active = 1');
        $activeDefaultCategories = (int) $catCountStmt->fetchColumn();

        $broadcastCountStmt = $this->pdo->query(
            "SELECT COUNT(*) FROM admin_audit_logs WHERE action = 'BROADCAST_ANNOUNCEMENT'"
        );
        $totalBroadcasts = (int) $broadcastCountStmt->fetchColumn();

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
        return $stmt->fetchAll();
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
        $row = $stmt->fetch();
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

    /**
     * List system default categories (FR13.01).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDefaultCategories(?string $type = null, bool $includeInactive = true): array
    {
        $sql = 'SELECT id, name, category_type, is_active FROM default_categories WHERE 1 = 1';
        $params = [];

        if ($type !== null && trim($type) !== '') {
            $sql .= ' AND category_type = ?';
            $params[] = strtoupper(trim($type));
        }
        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY category_type ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findDefaultCategoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, category_type, is_active FROM default_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findDefaultCategoryByNameAndType(string $name, string $categoryType, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT id, name, category_type, is_active
                FROM default_categories
                WHERE LOWER(name) = LOWER(?) AND category_type = ?';
        $params = [trim($name), strtoupper(trim($categoryType))];
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createDefaultCategory(string $name, string $categoryType, bool $isActive = true): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO default_categories (name, category_type, is_active) VALUES (?, ?, ?)'
        );
        $stmt->execute([trim($name), strtoupper(trim($categoryType)), $isActive ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateDefaultCategory(int $id, string $name, string $categoryType, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE default_categories SET name = ?, category_type = ?, is_active = ? WHERE id = ?'
        );
        return $stmt->execute([trim($name), strtoupper(trim($categoryType)), $isActive ? 1 : 0, $id]);
    }

    /**
     * Fetch all system_config rows as an associative map and raw row list (FR13.02).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listSystemConfigs(): array
    {
        $stmt = $this->pdo->query(
            'SELECT config_key, config_value, updated_at FROM system_config ORDER BY config_key ASC'
        );
        return $stmt->fetchAll();
    }

    public function upsertSystemConfig(string $key, string $value): void
    {
        $check = $this->pdo->prepare('SELECT 1 FROM system_config WHERE config_key = ? LIMIT 1');
        $check->execute([$key]);
        if ($check->fetchColumn() !== false) {
            $stmt = $this->pdo->prepare(
                'UPDATE system_config SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE config_key = ?'
            );
            $stmt->execute([$value, $key]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO system_config (config_key, config_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Retrieve broadcast announcement history from admin_audit_logs (FR13.03).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBroadcastHistory(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT l.id,
                    l.admin_user_id,
                    u.full_name AS admin_name,
                    l.reason,
                    l.created_at
             FROM admin_audit_logs l
             LEFT JOIN users u ON u.id = l.admin_user_id
             WHERE l.action = 'BROADCAST_ANNOUNCEMENT'
             ORDER BY l.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $announcements = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['reason'] ?? ''), true);
            if (is_array($decoded)) {
                $announcements[] = [
                    'id' => (int) $row['id'],
                    'title' => (string) ($decoded['title'] ?? 'Thông báo hệ thống'),
                    'body' => (string) ($decoded['body'] ?? ''),
                    'priority' => (string) ($decoded['priority'] ?? 'INFO'),
                    'recipientCount' => (int) ($decoded['recipientCount'] ?? 0),
                    'adminId' => (int) $row['admin_user_id'],
                    'adminName' => (string) ($row['admin_name'] ?? 'Admin'),
                    'createdAt' => (string) $row['created_at'],
                ];
            } else {
                $announcements[] = [
                    'id' => (int) $row['id'],
                    'title' => 'Thông báo hệ thống',
                    'body' => (string) ($row['reason'] ?? ''),
                    'priority' => 'INFO',
                    'recipientCount' => 0,
                    'adminId' => (int) $row['admin_user_id'],
                    'adminName' => (string) ($row['admin_name'] ?? 'Admin'),
                    'createdAt' => (string) $row['created_at'],
                ];
            }
        }

        return $announcements;
    }
}
