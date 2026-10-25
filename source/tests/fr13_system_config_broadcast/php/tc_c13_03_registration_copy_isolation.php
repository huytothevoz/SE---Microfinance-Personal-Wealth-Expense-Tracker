<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function (): array {
    $ctx = setupAdminTestContext();
    $service = $ctx['adminService'];
    $userDao = $ctx['userDao'];
    $pdo = $ctx['pdo'];

    $adminId = 1;
    $existingUserId = 2;
    $newUserId = 3;

    // 1. Lưu lại danh mục ban đầu của người dùng hiện hữu (User 2)
    $stmt = $pdo->prepare('SELECT id, name, category_type FROM categories WHERE user_id = ? ORDER BY id ASC');
    $stmt->execute([$existingUserId]);
    $beforeUser2Categories = $stmt->fetchAll();

    // 2. Admin thêm danh mục mẫu mới đang hoạt động: "Thưởng dự án" (INCOME)
    $newCat = $service->createDefaultCategory($adminId, [
        'name' => 'Thưởng dự án',
        'categoryType' => 'INCOME',
        'isActive' => true,
    ]);

    // 3. Admin tạm ngưng một danh mục mẫu hiện có: ID 2 ("Mua sắm")
    $service->updateDefaultCategory($adminId, 2, [
        'isActive' => false,
    ]);

    // 4. Giả lập đăng ký người dùng mới (User ID 3) -> kích hoạt sao chép danh mục mẫu
    $copiedCount = $userDao->copyDefaultCategories($newUserId);

    // 5. Kiểm tra danh mục của người dùng mới (User 3)
    $stmt = $pdo->prepare('SELECT name, category_type FROM categories WHERE user_id = ? ORDER BY name ASC');
    $stmt->execute([$newUserId]);
    $user3Categories = $stmt->fetchAll();

    $categoryNames = array_column($user3Categories, 'name');

    // Phải có: "Ăn uống", "Lương", "Thưởng dự án"
    if (!in_array('Thưởng dự án', $categoryNames, true)) {
        throw new RuntimeException('Người dùng mới phải kế thừa danh mục mẫu mới tạo "Thưởng dự án".');
    }
    if (!in_array('Ăn uống', $categoryNames, true) || !in_array('Lương', $categoryNames, true)) {
        throw new RuntimeException('Người dùng mới phải có các danh mục mặc định active ban đầu.');
    }

    // Không được có: "Mua sắm" (đã tạm ngưng) và "Danh mục tạm ngưng cũ"
    if (in_array('Mua sắm', $categoryNames, true)) {
        throw new RuntimeException('Người dùng mới KHÔNG ĐƯỢC kế thừa danh mục đã tạm ngưng ("Mua sắm").');
    }
    if (in_array('Danh mục tạm ngưng cũ', $categoryNames, true)) {
        throw new RuntimeException('Người dùng mới KHÔNG ĐƯỢC kế thừa danh mục tạm ngưng cũ.');
    }

    // 6. Kiểm tra tính toàn vẹn và bất biến của người dùng hiện hữu (User 2)
    $stmt = $pdo->prepare('SELECT id, name, category_type FROM categories WHERE user_id = ? ORDER BY id ASC');
    $stmt->execute([$existingUserId]);
    $afterUser2Categories = $stmt->fetchAll();

    if ($beforeUser2Categories !== $afterUser2Categories) {
        throw new RuntimeException('VI PHẠM NGUYÊN TẮC: Thay đổi danh mục mẫu đã làm biến động danh mục của người dùng cũ!');
    }

    return [
        'test' => 'tc_c13_03_registration_copy_isolation',
        'passed' => true,
        'newUserId' => $newUserId,
        'user3InheritedCategories' => $categoryNames,
        'user2CategoryCountUnchanged' => count($afterUser2Categories),
        'message' => 'Quy tắc kế thừa cho tài khoản mới và cách ly tuyệt đối tài khoản cũ hoạt động chính xác 100%.',
    ];
});
