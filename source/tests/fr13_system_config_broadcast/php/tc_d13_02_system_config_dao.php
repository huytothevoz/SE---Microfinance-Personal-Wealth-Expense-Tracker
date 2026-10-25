<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $dao = $ctx['adminDao'];

    // 1. Kiểm tra đọc các giá trị cấu hình ban đầu
    $configs = $dao->getSystemConfigs();
    if (!isset($configs['BUDGET_WARNING_THRESHOLD_PERCENT'])) {
        throw new RuntimeException('Thiếu tham số cấu hình BUDGET_WARNING_THRESHOLD_PERCENT');
    }

    $threshold = $dao->getSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT', '80');
    if ($threshold !== '80') {
        throw new RuntimeException("Ngưỡng mặc định mong đợi '80', thực tế: '{$threshold}'");
    }

    // 2. Cập nhật tham số hệ thống
    $ok = $dao->updateSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT', '85');
    if (!$ok) {
        throw new RuntimeException('updateSystemConfig thất bại');
    }

    $newThreshold = $dao->getSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT');
    if ($newThreshold !== '85') {
        throw new RuntimeException("Sau update, mong đợi '85', thực tế: '{$newThreshold}'");
    }

    // 3. Khôi phục lại giá trị chuẩn 80
    $dao->updateSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT', '80');

    // 4. Kiểm tra tham số không tồn tại trả về default
    $nonExistent = $dao->getSystemConfig('NON_EXISTENT_KEY_XYZ', 'fallback_val');
    if ($nonExistent !== 'fallback_val') {
        throw new RuntimeException("getSystemConfig với key không tồn tại phải trả về fallback_val, thực tế: '{$nonExistent}'");
    }

    return [
        'test' => 'tc_d13_02_system_config_dao',
        'passed' => true,
        'message' => 'DAO system_config truy vấn và cập nhật tham số vận hành CSDL MySQL SE thành công chuẩn FR13.02.',
    ];
});
