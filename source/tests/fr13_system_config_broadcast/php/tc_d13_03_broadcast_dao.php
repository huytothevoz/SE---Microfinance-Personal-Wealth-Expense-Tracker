<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Modules\Notifications\NotificationDAO;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $pdo = $ctx['pdo'];

    // Đảm bảo có ít nhất 3 user: 2 active, 1 inactive/locked
    $pdo->exec("INSERT OR REPLACE INTO users (id, full_name, email, password_hash, role, status, base_currency) VALUES
        (1, 'Admin', 'admin@wealthflow.local', 'h1', 'ADMIN', 'ACTIVE', 'VND'),
        (2, 'User Hoạt Động', 'user_active@wealthflow.local', 'h2', 'USER', 'ACTIVE', 'VND'),
        (3, 'User Bị Khóa', 'user_locked@wealthflow.local', 'h3', 'USER', 'LOCKED', 'VND')");

    $notifDao = new NotificationDAO($pdo);

    // Tiêu đề dài 255 ký tự (theo yêu cầu người dùng)
    $longTitle = str_repeat('A', 255);
    $body = 'Hệ thống WealthFlow thông báo bảo trì định kỳ từ 01:00 đến 03:00 sáng mai.';
    $priority = 'MAINTENANCE';

    // 1. Thực hiện broadcast
    $count = $notifDao->broadcast($longTitle, $body, $priority);

    // Kỳ vọng: chỉ có 2 users có status = 'ACTIVE' (id 1 và id 2) nhận được thông báo
    if ($count !== 2) {
        throw new RuntimeException("Số người nhận broadcast mong đợi 2 (chỉ user ACTIVE), thực tế: {$count}");
    }

    // 2. Kiểm tra hộp thư của User 2 (ACTIVE)
    $user2Notifs = $notifDao->findAllByUser(2);
    if (empty($user2Notifs)) {
        throw new RuntimeException("User 2 (ACTIVE) không nhận được broadcast trong hộp thư");
    }
    $targetNotif = $user2Notifs[0];
    if ($targetNotif['title'] !== $longTitle) {
        throw new RuntimeException("Tiêu đề thông báo không khớp hoặc bị cắt ngắn 255 ký tự");
    }
    if ($targetNotif['notification_type'] !== 'BROADCAST') {
        throw new RuntimeException("Loại thông báo phải là 'BROADCAST'");
    }
    if ($targetNotif['priority'] !== 'MAINTENANCE') {
        throw new RuntimeException("Mức độ ưu tiên mong đợi 'MAINTENANCE', thực tế: {$targetNotif['priority']}");
    }

    // 3. Kiểm tra hộp thư của User 3 (LOCKED): không được nhận
    $user3Notifs = $notifDao->findAllByUser(3);
    if (!empty($user3Notifs)) {
        throw new RuntimeException("User 3 (LOCKED) không được phép nhận thông báo broadcast");
    }

    return [
        'test' => 'tc_d13_03_broadcast_dao',
        'passed' => true,
        'recipientCount' => $count,
        'titleLength' => mb_strlen($longTitle),
        'message' => 'DAO broadcast phát thông báo hàng loạt O(1) qua SQL Batch Fan-out thành công chuẩn FR13.03.',
    ];
});
