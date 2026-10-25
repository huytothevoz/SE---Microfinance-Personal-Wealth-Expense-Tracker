<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\ApiException;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $adminService = $ctx['adminService'];
    $auditLogDao = $ctx['auditLogDao'];
    $notifDao = $ctx['notifDao'];
    $pdo = $ctx['pdo'];

    $adminId = 1;
    $regularUserId = 2;

    // 1. Chặn người dùng thường (ID: 2) gọi broadcastAnnouncement -> 403 Forbidden
    $userBlocked = false;
    try {
        $adminService->broadcastAnnouncement($regularUserId, [
            'title' => 'Thông báo thử nghiệm',
            'body' => 'Nội dung thông báo',
            'priority' => 'INFO',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $userBlocked = true;
        }
    }
    if (!$userBlocked) {
        throw new RuntimeException('Người dùng thông thường không bị chặn khi broadcastAnnouncement (kỳ vọng 403)');
    }

    // Chặn người dùng thường xem lịch sử broadcast -> 403 Forbidden
    $historyBlocked = false;
    try {
        $adminService->getBroadcastHistory($regularUserId);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $historyBlocked = true;
        }
    }
    if (!$historyBlocked) {
        throw new RuntimeException('Người dùng thông thường không bị chặn khi getBroadcastHistory (kỳ vọng 403)');
    }

    // 2. Kiểm tra Validation tiêu đề và nội dung -> 422 Unprocessable Entity
    // Case 2.1: Tiêu đề rỗng
    $emptyTitleCaught = false;
    try {
        $adminService->broadcastAnnouncement($adminId, [
            'title' => '   ',
            'body' => 'Nội dung hợp lệ',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 422 && isset($e->fields['title'])) {
            $emptyTitleCaught = true;
        }
    }
    if (!$emptyTitleCaught) {
        throw new RuntimeException('Không bắt lỗi tiêu đề rỗng (kỳ vọng 422 fields.title)');
    }

    // Case 2.2: Tiêu đề vượt quá 255 ký tự (256 ký tự)
    $overTitleCaught = false;
    try {
        $adminService->broadcastAnnouncement($adminId, [
            'title' => str_repeat('A', 256),
            'body' => 'Nội dung hợp lệ',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 422 && isset($e->fields['title'])) {
            $overTitleCaught = true;
        }
    }
    if (!$overTitleCaught) {
        throw new RuntimeException('Không bắt lỗi tiêu đề vượt quá 255 ký tự (kỳ vọng 422 fields.title)');
    }

    // Case 2.3: Nội dung rỗng
    $emptyBodyCaught = false;
    try {
        $adminService->broadcastAnnouncement($adminId, [
            'title' => 'Tiêu đề hợp lệ',
            'body' => '',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 422 && isset($e->fields['body'])) {
            $emptyBodyCaught = true;
        }
    }
    if (!$emptyBodyCaught) {
        throw new RuntimeException('Không bắt lỗi nội dung rỗng (kỳ vọng 422 fields.body)');
    }

    // Case 2.4: Nội dung vượt quá 2000 ký tự (2001 ký tự)
    $overBodyCaught = false;
    try {
        $adminService->broadcastAnnouncement($adminId, [
            'title' => 'Tiêu đề hợp lệ',
            'body' => str_repeat('B', 2001),
        ]);
    } catch (ApiException $e) {
        if ($e->status === 422 && isset($e->fields['body'])) {
            $overBodyCaught = true;
        }
    }
    if (!$overBodyCaught) {
        throw new RuntimeException('Không bắt lỗi nội dung vượt quá 2000 ký tự (kỳ vọng 422 fields.body)');
    }

    // 3. Thực hiện Broadcast thành công với tiêu đề tối đa 255 ký tự và mức MAINTENANCE
    $title255 = str_repeat('T', 255);
    $bodyContent = 'Hệ thống bảo trì hạ tầng cơ sở dữ liệu định kỳ từ 02:00 đến 02:30 sáng mai.';
    $broadcastRes = $adminService->broadcastAnnouncement($adminId, [
        'title' => $title255,
        'body' => $bodyContent,
        'priority' => 'MAINTENANCE',
    ]);

    if ($broadcastRes['title'] !== $title255) {
        throw new RuntimeException('Broadcast kết quả title không khớp');
    }
    if ($broadcastRes['priority'] !== 'MAINTENANCE') {
        throw new RuntimeException('Broadcast priority không phải MAINTENANCE');
    }
    if ($broadcastRes['status'] !== 'DISPATCHED') {
        throw new RuntimeException('Broadcast status không phải DISPATCHED');
    }
    if (!isset($broadcastRes['recipientCount']) || (int) $broadcastRes['recipientCount'] < 3) {
        throw new RuntimeException('Số lượng người nhận broadcast không đúng (kỳ vọng >= 3 users ACTIVE)');
    }

    // 4. Kiểm tra Nhật ký kiểm toán (Audit Logs)
    $history = $adminService->getBroadcastHistory($adminId);
    if (empty($history)) {
        throw new RuntimeException('getBroadcastHistory không trả về bản ghi nào');
    }
    $latest = $history[0];
    if ($latest['action'] !== 'BROADCAST_ANNOUNCEMENT') {
        throw new RuntimeException('Action trong audit log không phải BROADCAST_ANNOUNCEMENT: ' . ($latest['action'] ?? 'null'));
    }
    if ((int) $latest['admin_user_id'] !== $adminId) {
        throw new RuntimeException('admin_user_id trong audit log không khớp Admin ID');
    }

    // 5. Kiểm tra Hộp thư thông báo của Người dùng thường (User ID: 2)
    $userNotifications = $notifDao->findAllByUser($regularUserId);
    $found = false;
    foreach ($userNotifications as $n) {
        if ($n['title'] === $title255 && $n['body'] === $bodyContent) {
            $found = true;
            if ($n['priority'] !== 'MAINTENANCE') {
                throw new RuntimeException('Priority trong notification của user không khớp: ' . $n['priority']);
            }
            if ($n['notification_type'] !== 'BROADCAST') {
                throw new RuntimeException('Notification_type trong notification không phải BROADCAST: ' . ($n['notification_type'] ?? 'null'));
            }
            if ($n['read_at'] !== null) {
                throw new RuntimeException('Thông báo mới nhận phải ở trạng thái chưa đọc (read_at is null)');
            }
            break;
        }
    }
    if (!$found) {
        throw new RuntimeException('Người dùng ID 2 không nhận được thông báo phát thanh trong hộp thư');
    }

    // 6. Broadcast thứ hai với mức URGENT
    $urgentRes = $adminService->broadcastAnnouncement($adminId, [
        'title' => 'Cảnh báo bảo mật khẩn cấp',
        'body' => 'Vui lòng kiểm tra lại thiết bị đăng nhập của bạn.',
        'priority' => 'URGENT',
    ]);
    if ($urgentRes['priority'] !== 'URGENT') {
        throw new RuntimeException('Broadcast priority không phải URGENT');
    }

    $userNotifsAfter = $notifDao->findAllByUser($regularUserId);
    $foundUrgent = false;
    foreach ($userNotifsAfter as $n) {
        if ($n['title'] === 'Cảnh báo bảo mật khẩn cấp' && $n['priority'] === 'URGENT') {
            $foundUrgent = true;
            break;
        }
    }
    if (!$foundUrgent) {
        throw new RuntimeException('Thông báo URGENT không được tìm thấy trong hộp thư người dùng');
    }

    return [
        'firstBroadcast' => $broadcastRes,
        'urgentBroadcast' => $urgentRes,
        'auditLogCount' => count($history),
        'userNotificationsCount' => count($userNotifsAfter),
    ];
});
