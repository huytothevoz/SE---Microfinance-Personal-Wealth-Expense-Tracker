<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\ApiException;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $service = $ctx['adminService'];

    $adminId = 1;
    $regularUserId = 2;

    $forbiddenCount = 0;

    // 1. Thử lấy danh sách khi là người dùng thường
    try {
        $service->getDefaultCategories($regularUserId);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $forbiddenCount++;
        }
    }

    // 2. Thử tạo danh mục khi là người dùng thường
    try {
        $service->createDefaultCategory($regularUserId, [
            'name' => 'Hack Category',
            'categoryType' => 'EXPENSE',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $forbiddenCount++;
        }
    }

    // 3. Thử sửa danh mục khi là người dùng thường
    try {
        $service->updateDefaultCategory($regularUserId, 1, [
            'name' => 'Hack Name',
        ]);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $forbiddenCount++;
        }
    }

    // 4. Thử xóa danh mục khi là người dùng thường
    try {
        $service->deleteDefaultCategory($regularUserId, 1);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $forbiddenCount++;
        }
    }

    if ($forbiddenCount !== 4) {
        throw new RuntimeException("Cả 4 hành động của người dùng thường phải bị từ chối 403 Forbidden. Số lần chặn thực tế: {$forbiddenCount}");
    }

    // 5. Quản trị viên (Admin ID 1) thực hiện thành công
    $categories = $service->getDefaultCategories($adminId);
    if (!is_array($categories) || empty($categories)) {
        throw new RuntimeException('Admin phải lấy được danh sách danh mục mẫu hợp lệ.');
    }

    return [
        'test' => 'tc_c13_01_admin_authorization',
        'passed' => true,
        'forbiddenChecks' => $forbiddenCount,
        'message' => 'Quyền truy cập khu vực quản trị được bảo vệ nghiêm ngặt: Non-admin 403, Admin 200 OK.',
    ];
});
