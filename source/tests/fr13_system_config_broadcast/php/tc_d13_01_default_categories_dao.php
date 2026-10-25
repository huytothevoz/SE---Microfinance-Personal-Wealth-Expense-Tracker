<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $dao = $ctx['adminDao'];

    // 1. Lấy tất cả danh mục mặc định
    $all = $dao->getAllDefaultCategories(false);
    $activeOnly = $dao->getAllDefaultCategories(true);

    if (count($all) !== 4) {
        throw new RuntimeException('getAllDefaultCategories(false) phải trả về 4 bản ghi ban đầu, thực tế: ' . count($all));
    }
    if (count($activeOnly) !== 3) {
        throw new RuntimeException('getAllDefaultCategories(true) phải trả về 3 bản ghi active, thực tế: ' . count($activeOnly));
    }

    // 2. Tạo danh mục mới
    $newId = $dao->createDefaultCategory('Đầu tư sinh lời', 'INCOME', true);
    if ($newId <= 0) {
        throw new RuntimeException('createDefaultCategory thất bại, ID <= 0');
    }

    // 3. Tìm theo tên và loại (không phân biệt hoa thường và khoảng trắng)
    $found = $dao->findDefaultCategoryByNameAndType('  đầu tư sinh lời  ', 'income');
    if (!$found || (int) $found['id'] !== $newId) {
        throw new RuntimeException('findDefaultCategoryByNameAndType không tìm thấy bản ghi vừa tạo hoặc sai ID');
    }

    // 4. Cập nhật tên và trạng thái
    $updateOk = $dao->updateDefaultCategory($newId, [
        'name' => 'Đầu tư & Cổ tức',
        'isActive' => false,
    ]);
    if (!$updateOk) {
        throw new RuntimeException('updateDefaultCategory trả về false');
    }

    $updated = $dao->findDefaultCategoryById($newId);
    if (!$updated || $updated['name'] !== 'Đầu tư & Cổ tức' || $updated['isActive'] !== false) {
        throw new RuntimeException('Dữ liệu sau update không khớp: ' . json_encode($updated, JSON_UNESCAPED_UNICODE));
    }

    // 5. Xóa danh mục
    $deleteOk = $dao->deleteDefaultCategory($newId);
    if (!$deleteOk) {
        throw new RuntimeException('deleteDefaultCategory trả về false');
    }
    $afterDelete = $dao->findDefaultCategoryById($newId);
    if ($afterDelete !== null) {
        throw new RuntimeException('Bản ghi vẫn còn tồn tại sau khi delete');
    }

    return [
        'test' => 'tc_d13_01_default_categories_dao',
        'passed' => true,
        'message' => 'Toàn bộ thao tác DAO (Create, Read, Update, Delete) trên default_categories đạt chuẩn.',
    ];
});
