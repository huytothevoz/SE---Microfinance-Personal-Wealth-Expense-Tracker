<?php

declare(strict_types=1);

/**
 * WealthFlow FR13 Database Scenario Seeder
 * Connects to live MySQL SE instance (port 3307 or 3306) and seeds baseline data for FR13 visual tests.
 */

$scenario = $argv[1] ?? 'baseline';

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
use WealthFlow\Core\Database;

try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    echo json_encode(['status' => 'ERROR', 'message' => "Không thể kết nối MySQL: " . $e->getMessage()]);
    exit(1);
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

    // 4. Dọn dẹp các người dùng test động và danh mục test tạm của các lần chạy trước
    $stmtTestUsers = $pdo->query("SELECT id FROM users WHERE email LIKE 'newuser_%@wealthflow.local' OR email LIKE 'testuser_%'");
    $testUserIds = $stmtTestUsers ? $stmtTestUsers->fetchAll(PDO::FETCH_COLUMN) : [];
    if (!empty($testUserIds)) {
        $inClause = implode(',', array_map('intval', $testUserIds));
        $pdo->exec("DELETE FROM categories WHERE user_id IN ($inClause)");
        $pdo->exec("DELETE FROM wallets WHERE user_id IN ($inClause)");
        $pdo->exec("DELETE FROM auth_sessions WHERE user_id IN ($inClause)");
        $pdo->exec("DELETE FROM registration_emails WHERE user_id IN ($inClause)");
        $pdo->exec("DELETE FROM users WHERE id IN ($inClause)");
    }
    $pdo->exec("DELETE FROM default_categories WHERE name LIKE '%Thưởng kinh doanh%' OR name LIKE '%Test%' OR name LIKE '%Thử nghiệm%'");
    $pdo->exec("UPDATE default_categories SET is_active = 1 WHERE name = 'Mua sắm'");

    // Đảm bảo user1@gmail.com có danh mục cá nhân 'Mua sắm' để đối chiếu bảo toàn dữ liệu
    $stmtU1 = $pdo->prepare("SELECT id FROM users WHERE email = 'user1@gmail.com'");
    $stmtU1->execute();
    $u1Id = $stmtU1->fetchColumn();
    if ($u1Id) {
        $stmtC = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Mua sắm'");
        $stmtC->execute([$u1Id]);
        if (!$stmtC->fetchColumn()) {
            $pdo->prepare("INSERT INTO categories (user_id, name, category_type) VALUES (?, 'Mua sắm', 'EXPENSE')")->execute([$u1Id]);
        }
    }

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

    // 6. Đảm bảo cấu hình hệ thống (system_config) có sẵn các tham số vận hành FR13.02
    $defaultConfigs = [
        'BUDGET_WARNING_THRESHOLD_PERCENT' => '80',
        'JWT_ACCESS_TOKEN_TTL_MINUTES' => '60',
        'CSV_EXPORT_MAX_ROWS' => '5000',
        'LOAN_REMINDER_DAYS_BEFORE' => '3',
        'MAX_FAILED_LOGINS' => '5',
        'OTP_EXPIRY_MINUTES' => '10',
        'SESSION_EXPIRY_DAYS' => '7',
        'DEFAULT_BASE_CURRENCY' => 'VND',
        'ALLOW_REGISTRATION' => 'true',
    ];
    $stmtCfg = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    foreach ($defaultConfigs as $k => $v) {
        $stmtCfg->execute([$k, $v]);
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    echo json_encode([
        'status' => 'OK',
        'scenario' => $scenario,
        'adminId' => $adminId,
        'message' => 'Seeder FR13 thiết lập dữ liệu mẫu và quyền Admin thành công.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
    exit(1);
}
