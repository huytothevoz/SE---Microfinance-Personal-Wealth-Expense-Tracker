<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Notifications;

use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class NotificationController
{
    private NotificationService $service;

    public function __construct(?NotificationService $service = null)
    {
        $this->service = $service ?? new NotificationService();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $limit = (int) $request->getQuery('limit', 30);
        $unreadOnlyParam = (string) $request->getQuery('unreadOnly', '0');
        $unreadOnly = in_array(strtolower($unreadOnlyParam), ['1', 'true'], true);

        $notifications = $this->service->getNotificationsForUser($userId, $limit, $unreadOnly);
        $unreadCount = $this->service->countUnreadForUser($userId);
        JsonResponse::success([
            'notifications' => $notifications,
            'count' => count($notifications),
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $updated = $this->service->markAsRead($id, $userId);
        if (!$updated) {
            JsonResponse::error('NOT_FOUND', 'Thông báo không tồn tại hoặc không thuộc quyền sở hữu.', 404);
            return;
        }
        JsonResponse::success([
            'id' => $id,
            'unreadCount' => $this->service->countUnreadForUser($userId),
            'message' => 'Đã đánh dấu thông báo đã đọc.',
        ]);
    }

    public function markAllRead(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $this->service->markAllAsRead($userId);
        JsonResponse::success([
            'unreadCount' => 0,
            'message' => 'Đã đánh dấu tất cả thông báo đã đọc.',
        ]);
    }
}
