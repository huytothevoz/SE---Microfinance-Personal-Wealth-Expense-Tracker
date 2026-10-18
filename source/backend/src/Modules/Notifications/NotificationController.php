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
        $userId = AuthContext::getUserId($request);
        $limit = (int) $request->getQuery('limit', 30);
        $notifications = $this->service->getNotificationsForUser($userId, $limit);
        JsonResponse::success(['notifications' => $notifications, 'count' => count($notifications)]);
    }

    public function markRead(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $this->service->markAsRead($id, $userId);
        JsonResponse::success(['message' => 'Đã đánh dấu thông báo đã đọc.']);
    }

    public function markAllRead(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $this->service->markAllAsRead($userId);
        JsonResponse::success(['message' => 'Đã đánh dấu tất cả thông báo đã đọc.']);
    }
}
