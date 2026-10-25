<?php

declare(strict_types=1);

/**
 * WealthFlow FR13 Database Scenario Seeder
 * Connects to live MySQL SE instance (port 3307 or 3306) and seeds baseline data for FR13 visual tests.
 */

$scenario = $argv[1] ?? 'baseline';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3307';
$dbName = getenv('DB_NAME') ?: 'wealthflow';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Thử fallback sang port 3306 nếu 3307 không kết nối được
    try {
        $dbPort = '3306';
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e2) {
        echo json_encode(['status' => 'ERROR', 'message' => "Không thể kết nối MySQL trên port 3307 hoặc 3306: " . $e2->getMessage()]);
        exit(1);
    }
}

$pdo->beginTransaction();
try {
    // 1. Xóa rate limits và sessions cũ để test suite chạy thông suốt
    $pdo->exec("DELETE FROM auth_rate_limits");

    // 2. Đảm bảo tài khoản ADMIN tồn tại với role ADMIN
    // Password hash cho 'User1234'
    $adminPasswordHash = password_hash('User1234', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@wealthflow.local']);
    $adminUser = $stmt->fetch();

    if ($adminUser) {
        $pdo->prepare("UPDATE users SET role = 'ADMIN', password_hash = ?, status = 'ACTIVE' WHERE email = ?")
            ->execute([$adminPasswordHash, 'admin@wealthflow.local']);
        $adminId = (int) $adminUser['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, base_currency)
            VALUES (?, ?, ?, 'ADMIN', 'ACTIVE', 'VND')");
        $stmt->execute(['Quản Trị Viên Hệ Thống', 'admin@wealthflow.local', $adminPasswordHash]);
        $adminId = (int) $pdo->lastInsertId();
    }

    // 3. Đảm bảo tài khoản USER thường tồn tại: user1@gmail.com
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['user1@gmail.com']);
    $regUser = $stmt->fetch();
    if ($regUser) {
        $pdo->prepare("UPDATE users SET role = 'USER', password_hash = ?, status = 'ACTIVE' WHERE email = ?")
            ->execute([$adminPasswordHash, 'user1@gmail.com']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, base_currency)
            VALUES (?, ?, ?, 'USER', 'ACTIVE', 'VND')");
        $stmt->execute(['Nguyễn Văn A', 'user1@gmail.com', $adminPasswordHash]);
    }

    // 4. Dọn dẹp các danh mục test tạm của các lần chạy trước
    $pdo->exec("DELETE FROM default_categories WHERE name LIKE '%Thưởng kinh doanh%' OR name LIKE '%Test%' OR name LIKE '%Thử nghiệm%'");

    // 5. Đảm bảo các danh mục mặc định chuẩn luôn có sẵn
    $defaults = [
        ['Ăn uống', 'EXPENSE', 1],
        ['Mua sắm', 'EXPENSE', 1],
        ['Nhà ở & Tiện ích', 'EXPENSE', 1],
        ['Di chuyển', 'EXPENSE', 1],
        ['Lương', 'INCOME', 1],
        ['Thưởng & Hoa hồng', 'INCOME', 1],
        ['Thu nhập đầu tư', 'INCOME', 1],
    ];

    foreach ($defaults as [$name, $type, $active]) {
        $stmt = $pdo->prepare("SELECT id FROM default_categories WHERE name = ? AND category_type = ?");
        $stmt->execute([$name, $type]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO default_categories (name, category_type, is_active) VALUES (?, ?, ?)")
                ->execute([$name, $type, $active]);
        }
    }

    $pdo->commit();
    echo json_encode([
        'status' => 'OK',
        'scenario' => $scenario,
        'adminId' => $adminId,
        'message' => 'Seeder FR13 thiết lập dữ liệu mẫu và quyền Admin thành công.',
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
    exit(1);
}
