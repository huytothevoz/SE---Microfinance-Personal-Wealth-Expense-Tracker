<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Notifications;

use PDO;
use WealthFlow\Core\Database;

final class NotificationService
{
    private NotificationDAO $dao;

    public function __construct(?NotificationDAO $dao = null, ?PDO $pdo = null)
    {
        if ($dao !== null) {
            $this->dao = $dao;
        } else {
            $this->dao = new NotificationDAO($pdo ?? Database::getConnection());
        }
    }

    public function getDao(): NotificationDAO
    {
        return $this->dao;
    }

    /**
     * Send or queue a notification with deduplication key support (BR08).
     * Returns true if newly created, false if suppressed by deduplication.
     */
    public function notify(
        int $userId,
        string $title,
        string $body,
        string $type,
        ?string $dedupKey = null,
        string $priority = 'INFO'
    ): bool {
        return $this->dao->create($userId, $title, $body, $type, $dedupKey, $priority);
    }

    /** @return array<int, array<string, mixed>> */
    public function getNotificationsForUser(int $userId, int $limit = 30, bool $unreadOnly = false): array
    {
        return $this->dao->findAllByUser($userId, $limit, $unreadOnly);
    }

    public function countUnreadForUser(int $userId): int
    {
        return $this->dao->countUnreadByUser($userId);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->dao->markAsRead($id, $userId);
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->dao->markAllAsRead($userId);
    }

    public function broadcast(string $title, string $body, string $priority = 'INFO'): int
    {
        return $this->dao->broadcast($title, $body, $priority);
    }
}
