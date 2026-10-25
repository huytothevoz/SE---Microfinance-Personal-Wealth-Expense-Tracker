<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $service = $ctx['adminService'];
    $auditLogDao = $ctx['auditLogDao'];
    $adminId = 1;

    // 1. Thao tác CREATE
    $created = $service->createDefaultCategory($adminId, [
        'name' => 'Khen thưởng',
        'categoryType' => 'INCOME',
    ]);
    $catId = (int) $created['id'];

    // 2. Thao tác UPDATE
    $service->updateDefaultCategory($adminId, $catId, [
        'name' => 'Khen thưởng & Quà tặng',
    ]);

    // 3. Thao tác DELETE
    $service->deleteDefaultCategory($adminId, $catId);

    // 4. Kiểm tra các bản ghi audit log
    $logs = $auditLogDao->getAdminLogs(10);
    $actions = array_column($logs, 'action');

    if (!in_array('CREATE_DEFAULT_CATEGORY', $actions, true)) {
        throw new RuntimeException('Thiếu audit log cho hành động CREATE_DEFAULT_CATEGORY');
    }
    if (!in_array('UPDATE_DEFAULT_CATEGORY', $actions, true)) {
        throw new RuntimeException('Thiếu audit log cho hành động UPDATE_DEFAULT_CATEGORY');
    }
    if (!in_array('DELETE_DEFAULT_CATEGORY', $actions, true)) {
        throw new RuntimeException('Thiếu audit log cho hành động DELETE_DEFAULT_CATEGORY');
    }

    return [
        'test' => 'tc_c13_04_admin_audit_logging',
        'passed' => true,
        'loggedActions' => $actions,
        'message' => 'Toàn bộ hành động quản trị danh mục đều được ghi vết vào admin_audit_logs.',
    ];
});
