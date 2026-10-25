<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Budgets\BudgetDAO;
use WealthFlow\Modules\Budgets\BudgetService;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Transactions\CategoryDAO;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $adminService = $ctx['adminService'];
    $adminDao = $ctx['adminDao'];
    $auditLogDao = $ctx['auditLogDao'];
    $pdo = $ctx['pdo'];

    $adminId = 1;
    $regularUserId = 2;

    // 1. Admin đọc cấu hình hệ thống
    $result = $adminService->getSystemConfigs($adminId);
    if (!isset($result['configs']['BUDGET_WARNING_THRESHOLD_PERCENT'])) {
        throw new RuntimeException('getSystemConfigs không trả về BUDGET_WARNING_THRESHOLD_PERCENT');
    }
    if ($result['configs']['BUDGET_WARNING_THRESHOLD_PERCENT'] !== '80') {
        throw new RuntimeException('Giá trị mặc định của BUDGET_WARNING_THRESHOLD_PERCENT phải là 80');
    }
    if (!isset($result['definitions']['BUDGET_WARNING_THRESHOLD_PERCENT'])) {
        throw new RuntimeException('getSystemConfigs thiếu definitions');
    }

    // 2. Chặn Người dùng thường (ID: 2) truy cập cấu hình hệ thống -> 403 Forbidden
    $userBlocked = false;
    try {
        $adminService->getSystemConfigs($regularUserId);
    } catch (ApiException $e) {
        if ($e->status === 403) {
            $userBlocked = true;
        }
    }
    if (!$userBlocked) {
        throw new RuntimeException('Người dùng thông thường không bị chặn khi truy cập getSystemConfigs (kỳ vọng 403)');
    }

    // 3. Kiểm tra Validation khi giá trị ngoài giới hạn cho phép -> 422 Unprocessable Entity
    $invalidCases = [
        ['BUDGET_WARNING_THRESHOLD_PERCENT' => 120], // > 95
        ['BUDGET_WARNING_THRESHOLD_PERCENT' => 40],  // < 50
        ['JWT_ACCESS_TOKEN_TTL_MINUTES' => 5],       // < 15
        ['JWT_ACCESS_TOKEN_TTL_MINUTES' => 2000],    // > 1440
        ['CSV_EXPORT_MAX_ROWS' => 50],               // < 100
        ['CSV_EXPORT_MAX_ROWS' => 999999],           // > 50000
        ['LOAN_REMINDER_DAYS_BEFORE' => 0],          // < 1
        ['LOAN_REMINDER_DAYS_BEFORE' => 45],         // > 30
    ];

    foreach ($invalidCases as $invalidPayload) {
        $failedAsExpected = false;
        try {
            $adminService->updateSystemConfigs($adminId, $invalidPayload);
        } catch (ApiException $e) {
            if ($e->status === 422) {
                $failedAsExpected = true;
            }
        }
        if (!$failedAsExpected) {
            throw new RuntimeException('Payload không hợp lệ không bị từ chối bằng 422: ' . json_encode($invalidPayload));
        }
    }

    // 4. Admin cập nhật cấu hình hợp lệ (BUDGET_WARNING_THRESHOLD_PERCENT = 85, JWT_ACCESS_TOKEN_TTL_MINUTES = 120)
    $updatePayload = [
        'BUDGET_WARNING_THRESHOLD_PERCENT' => 85,
        'JWT_ACCESS_TOKEN_TTL_MINUTES' => 120,
        'CSV_EXPORT_MAX_ROWS' => 10000,
        'LOAN_REMINDER_DAYS_BEFORE' => 7,
    ];
    $updatedResult = $adminService->updateSystemConfigs($adminId, $updatePayload);

    if ($updatedResult['configs']['BUDGET_WARNING_THRESHOLD_PERCENT'] !== '85') {
        throw new RuntimeException("Sau update, BUDGET_WARNING_THRESHOLD_PERCENT mong đợi '85', thực tế: " . $updatedResult['configs']['BUDGET_WARNING_THRESHOLD_PERCENT']);
    }
    if ($updatedResult['configs']['JWT_ACCESS_TOKEN_TTL_MINUTES'] !== '120') {
        throw new RuntimeException("Sau update, JWT_ACCESS_TOKEN_TTL_MINUTES mong đợi '120', thực tế: " . $updatedResult['configs']['JWT_ACCESS_TOKEN_TTL_MINUTES']);
    }

    // 5. Kiểm tra ghi nhận Audit Log
    $logs = $auditLogDao->getAdminLogs(5);
    $foundAudit = false;
    foreach ($logs as $log) {
        if ($log['action'] === 'UPDATE_SYSTEM_CONFIG') {
            $foundAudit = true;
            break;
        }
    }
    if (!$foundAudit) {
        throw new RuntimeException('Không tìm thấy bản ghi UPDATE_SYSTEM_CONFIG trong admin_audit_logs');
    }

    // 6. Kiểm tra tích hợp sâu với BudgetService (động hóa ngưỡng cảnh báo)
    $budgetDao = new BudgetDAO($pdo);
    $catDao = new CategoryDAO($pdo);
    $notifService = new NotificationService(null, $pdo);
    $budgetService = new BudgetService($budgetDao, $catDao, $notifService, $adminDao);

    // Tạo ngân sách cho user 2 (Hạn mức 1.000.000)
    $month = date('Y-m');
    $budgetId = $budgetDao->create($regularUserId, 1, $month, 1000000.0);

    // Giả lập chi tiêu 820.000 (tương đương 82% hạn mức)
    // Với ngưỡng hiện tại = 85%, 82% chưa chạm ngưỡng -> alertLevel phải là 'GREEN'
    $pdo->exec("INSERT INTO financial_transactions (user_id, category_id, transaction_type, status, description, currency, amount, occurred_on)
        VALUES ({$regularUserId}, 1, 'EXPENSE', 'POSTED', 'Chi tiêu thử nghiệm', 'VND', 820000, '{$month}-10')");

    $budgets = $budgetService->getBudgetsForMonth($regularUserId, $month);
    $targetBudget = null;
    foreach ($budgets as $b) {
        if ($b['id'] === $budgetId) {
            $targetBudget = $b;
            break;
        }
    }

    if (!$targetBudget) {
        throw new RuntimeException('Không tìm thấy ngân sách vừa tạo');
    }

    if ($targetBudget['alertLevel'] !== 'GREEN') {
        throw new RuntimeException("Với ngưỡng cảnh báo 85% và mức chi 82%, alertLevel phải là 'GREEN', nhưng nhận được '{$targetBudget['alertLevel']}'");
    }

    // Đổi lại ngưỡng về 80% -> alertLevel của cùng mức chi 82% bây giờ phải đổi thành 'YELLOW'!
    $adminService->updateSystemConfigs($adminId, ['BUDGET_WARNING_THRESHOLD_PERCENT' => 80]);
    $budgetsUpdated = $budgetService->getBudgetsForMonth($regularUserId, $month);
    foreach ($budgetsUpdated as $b) {
        if ($b['id'] === $budgetId) {
            $targetBudget = $b;
            break;
        }
    }

    if ($targetBudget['alertLevel'] !== 'YELLOW') {
        throw new RuntimeException("Sau khi đổi ngưỡng về 80%, với mức chi 82%, alertLevel phải là 'YELLOW', nhưng nhận được '{$targetBudget['alertLevel']}'");
    }

    return [
        'test' => 'tc_c13_05_system_config_validation',
        'passed' => true,
        'message' => 'Kiểm thử FR13.02 cấu hình tham số vận hành hệ thống, validation ràng buộc, audit log và tích hợp động với BudgetService thành công.',
    ];
});
