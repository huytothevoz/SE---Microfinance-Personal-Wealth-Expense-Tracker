<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\AuthContext;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $controller = $ctx['controller'];

    // 1. Quản trị viên (Admin ID 1) gọi GET /api/admin/default-categories
    AuthContext::setUserId(1);
    $reqGet = createTestRequest([], 'GET');

    ob_start();
    $controller->listDefaultCategories($reqGet);
    $getOutput = ob_get_clean();
    $getJson = json_decode($getOutput, true);

    if (!($getJson['success'] ?? false) || !isset($getJson['data']['categories'])) {
        throw new RuntimeException('GET /api/admin/default-categories trả về thất bại: ' . $getOutput);
    }
    $initialCount = count($getJson['data']['categories']);

    // 2. Admin gọi POST /api/admin/default-categories
    $reqPost = createTestRequest([
        'name' => 'Thu nhập thụ động',
        'categoryType' => 'INCOME',
    ], 'POST');

    ob_start();
    $controller->createDefaultCategory($reqPost);
    $postOutput = ob_get_clean();
    $postJson = json_decode($postOutput, true);
    $postStatus = http_response_code();

    if (!($postJson['success'] ?? false) || $postStatus !== 201) {
        throw new RuntimeException("POST /api/admin/default-categories thất bại (status {$postStatus}): {$postOutput}");
    }
    $createdId = (int) $postJson['data']['id'];

    // 3. Admin gọi PUT /api/admin/default-categories/{id}
    $reqPut = createTestRequest([
        'name' => 'Thu nhập thụ động & Cổ phiếu',
        'isActive' => false,
    ], 'PUT');

    ob_start();
    $controller->updateDefaultCategory($reqPut, $createdId);
    $putOutput = ob_get_clean();
    $putJson = json_decode($putOutput, true);

    if (!($putJson['success'] ?? false) || ($putJson['data']['name'] ?? '') !== 'Thu nhập thụ động & Cổ phiếu') {
        throw new RuntimeException('PUT /api/admin/default-categories thất bại: ' . $putOutput);
    }

    // 4. Admin gọi DELETE /api/admin/default-categories/{id}
    $reqDel = createTestRequest([], 'DELETE');

    ob_start();
    $controller->deleteDefaultCategory($reqDel, $createdId);
    $delOutput = ob_get_clean();
    $delJson = json_decode($delOutput, true);

    if (!($delJson['success'] ?? false)) {
        throw new RuntimeException('DELETE /api/admin/default-categories thất bại: ' . $delOutput);
    }

    // 5. Thử gọi API khi là người dùng thường (User ID 2) -> Phải nhận 403 Forbidden
    AuthContext::setUserId(2);
    ob_start();
    $controller->listDefaultCategories($reqGet);
    $userGetOutput = ob_get_clean();
    $userGetJson = json_decode($userGetOutput, true);
    $userGetStatus = http_response_code();

    if ($userGetStatus !== 403 || ($userGetJson['success'] ?? true) !== false) {
        throw new RuntimeException('Người dùng thường truy cập API Admin phải nhận 403 Forbidden: ' . $userGetOutput);
    }

    return [
        'test' => 'tc_b13_01_api_controller',
        'passed' => true,
        'initialCount' => $initialCount,
        'postStatus' => $postStatus,
        'createdId' => $createdId,
        'userForbiddenStatus' => $userGetStatus,
        'message' => 'Toàn bộ 4 endpoint API của AdminController hoạt động chính xác với đúng mã HTTP (200, 201, 403).',
    ];
});
