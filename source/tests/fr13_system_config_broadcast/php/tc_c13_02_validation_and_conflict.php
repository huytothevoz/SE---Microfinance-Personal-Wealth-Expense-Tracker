<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\ApiException;

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $service = $ctx['adminService'];
    $adminId = 1;

    $validationErrors = 0;
    $conflictErrors = 0;

    // 1. Tên để trống -> 422
    try {
        $service->createDefaultCategory($adminId, ['name' => '   ', 'categoryType' => 'EXPENSE']);
    } catch (ApiException $e) {
        if ($e->status === 422) {
            $validationErrors++;
        }
    }

    // 2. Tên vượt quá 100 ký tự -> 422
    try {
        $service->createDefaultCategory($adminId, ['name' => str_repeat('A', 101), 'categoryType' => 'EXPENSE']);
    } catch (ApiException $e) {
        if ($e->status === 422) {
            $validationErrors++;
        }
    }

    // 3. Loại danh mục không hợp lệ -> 422
    try {
        $service->createDefaultCategory($adminId, ['name' => 'Hợp lệ', 'categoryType' => 'INVALID_TYPE']);
    } catch (ApiException $e) {
        if ($e->status === 422) {
            $validationErrors++;
        }
    }

    // 4. Trùng danh mục đã tồn tại ('Ăn uống', EXPENSE) -> 409 Conflict
    try {
        $service->createDefaultCategory($adminId, ['name' => 'Ăn uống', 'categoryType' => 'EXPENSE']);
    } catch (ApiException $e) {
        if ($e->status === 409) {
            $conflictErrors++;
        }
    }

    // 5. Trùng chữ hoa chữ thường và khoảng trắng thừa ('  ăn uống  ', EXPENSE) -> 409 Conflict
    try {
        $service->createDefaultCategory($adminId, ['name' => '  ăn uống  ', 'categoryType' => 'EXPENSE']);
    } catch (ApiException $e) {
        if ($e->status === 409) {
            $conflictErrors++;
        }
    }

    // 6. Cùng tên nhưng KHÁC loại ('Ăn uống', INCOME) -> HỢP LỆ!
    $incomeFood = $service->createDefaultCategory($adminId, ['name' => 'Ăn uống', 'categoryType' => 'INCOME']);
    if ($incomeFood['name'] !== 'Ăn uống' || $incomeFood['categoryType'] !== 'INCOME') {
        throw new RuntimeException('Cùng tên nhưng khác loại (INCOME vs EXPENSE) phải được phép tạo.');
    }

    // 7. Sửa tên dẫn đến trùng với danh mục khác -> 409 Conflict
    // Thử đổi tên ID 2 ('Mua sắm', EXPENSE) thành 'Ăn uống' (EXPENSE)
    try {
        $service->updateDefaultCategory($adminId, 2, ['name' => 'Ăn uống']);
    } catch (ApiException $e) {
        if ($e->status === 409) {
            $conflictErrors++;
        }
    }

    if ($validationErrors !== 3) {
        throw new RuntimeException("Cả 3 trường hợp sai dữ liệu phải trả về 422 Validation Error. Thực tế: {$validationErrors}");
    }
    if ($conflictErrors !== 3) {
        throw new RuntimeException("Cả 3 trường hợp trùng lặp phải trả về 409 Conflict Error. Thực tế: {$conflictErrors}");
    }

    return [
        'test' => 'tc_c13_02_validation_and_conflict',
        'passed' => true,
        'validationErrors' => $validationErrors,
        'conflictErrors' => $conflictErrors,
        'message' => 'Quy tắc xác thực (422) và kiểm tra chống trùng lặp danh mục (409) đạt chuẩn FR13.01.',
    ];
});
