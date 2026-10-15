<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Notifications;

use PDO;
use WealthFlow\Config\Database;

final class NotificationService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Send or queue a notification with deduplication key support (BR08).
     * Returns true if newly created, false if suppressed by deduplication.
     */
    public function notify(int $userId, string $title, string $body, string $type, ?string $dedupKey = null): bool
    {
        if ($dedupKey !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM notifications WHERE user_id = ? AND dedup_key = ?');
            $stmt->execute([$userId, $dedupKey]);
            if ($stmt->fetchColumn() !== false) {
                // Already notified for this dedup key
                return false;
            }
        }

        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, notification_type, dedup_key)
            VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $body, strtoupper($type), $dedupKey]);
        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function getNotificationsForUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND read_at IS NULL');
        return $stmt->execute([$userId]);
    }
}
