<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Notifications;

use PDO;
use WealthFlow\Core\Database;

final class NotificationDAO
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

    public function create(
        int $userId,
        string $title,
        string $body,
        string $type,
        ?string $dedupKey = null,
        string $priority = 'INFO'
    ): bool {
        if ($dedupKey !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM notifications WHERE user_id = ? AND dedup_key = ?');
            $stmt->execute([$userId, $dedupKey]);
            if ($stmt->fetchColumn() !== false) {
                return false;
            }
        }

        $allowedPriorities = ['INFO', 'WARNING', 'CRITICAL', 'MAINTENANCE', 'URGENT'];
        $normPriority = strtoupper(trim($priority));
        if (!in_array($normPriority, $allowedPriorities, true)) {
            $normPriority = 'INFO';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, notification_type, priority, dedup_key)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $body, strtoupper($type), $normPriority, $dedupKey]);
        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, int $limit = 30, bool $unreadOnly = false): array
    {
        $limit = max(1, min(100, $limit));
        $sql = 'SELECT * FROM notifications WHERE user_id = ?'
            . ($unreadOnly ? ' AND read_at IS NULL' : '')
            . ' ORDER BY created_at DESC, id DESC LIMIT ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnreadByUser(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $existing = $this->findByIdAndUser($id, $userId);
        if (!$existing) {
            return false;
        }
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET read_at = COALESCE(read_at, {$nowExpr}) WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET read_at = {$nowExpr} WHERE user_id = ? AND read_at IS NULL"
        );
        return $stmt->execute([$userId]);
    }

    public function broadcast(string $title, string $body, string $priority = 'INFO'): int
    {
        $allowedPriorities = ['INFO', 'WARNING', 'CRITICAL', 'MAINTENANCE', 'URGENT'];
        $normPriority = strtoupper(trim($priority));
        if (!in_array($normPriority, $allowedPriorities, true)) {
            $normPriority = 'INFO';
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, title, body, notification_type, priority)
             SELECT id, ?, ?, 'BROADCAST', ? FROM users WHERE status = 'ACTIVE'"
        );
        $stmt->execute([$title, $body, $normPriority]);
        return $stmt->rowCount();
    }
}
