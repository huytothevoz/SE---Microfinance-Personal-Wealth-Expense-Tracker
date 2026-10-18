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

    public function create(int $userId, string $title, string $body, string $type, ?string $dedupKey = null): bool
    {
        if ($dedupKey !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM notifications WHERE user_id = ? AND dedup_key = ?');
            $stmt->execute([$userId, $dedupKey]);
            if ($stmt->fetchColumn() !== false) {
                return false;
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, notification_type, dedup_key)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $body, strtoupper($type), $dedupKey]);
        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET read_at = {$nowExpr} WHERE id = ? AND user_id = ?"
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

    public function broadcast(string $title, string $body, string $priority = 'NORMAL'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, title, body, notification_type)
             SELECT id, ?, ?, ? FROM users WHERE status = 'ACTIVE'"
        );
        $stmt->execute([$title, $body, strtoupper($priority)]);
        return $stmt->rowCount();
    }
}
