<?php

declare(strict_types=1);

/**
 * WealthFlow FR12 Database Scenario Seeder
 * Connects to live MySQL SE instance (port 3307) and seeds exact data for each visual test case.
 */

$scenario = $argv[1] ?? 'matched';

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
use WealthFlow\Core\Database;

try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    echo json_encode(['status' => 'ERROR', 'message' => "Không thể kết nối MySQL: " . $e->getMessage()]);
    exit(1);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute(['user1@gmail.com']);
$userId = $stmt->fetchColumn();
if (!$userId) {
    $passwordHash = password_hash('User1234', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, base_currency) VALUES (?, ?, ?, 'USER', 'ACTIVE', 'VND')")
        ->execute(['Người Dùng Thử Nghiệm 1', 'user1@gmail.com', $passwordHash]);
    $userId = (int) $pdo->lastInsertId();
} else {
    $userId = (int) $userId;
    $passwordHash = password_hash('User1234', PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET status = 'ACTIVE', password_hash = ? WHERE id = ?")->execute([$passwordHash, $userId]);
}

// 1. Clean up existing records for user1 to ensure deterministic state
$pdo->beginTransaction();
try {
    // Delete audit logs
    $pdo->prepare("DELETE FROM security_audit_logs WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM export_audit_logs WHERE user_id = ?")->execute([$userId]);

    // Clear rate limits and existing sessions so test suites can run continuously without 429 RATE_LIMITED
    $pdo->exec("DELETE FROM auth_rate_limits");
    $pdo->prepare("DELETE FROM auth_sessions WHERE user_id = ?")->execute([$userId]);

    // Delete ledger entries and transactions
    $pdo->prepare("DELETE FROM ledger_entries WHERE transaction_id IN (SELECT id FROM financial_transactions WHERE user_id = ?)")->execute([$userId]);
    $pdo->prepare("DELETE FROM financial_transactions WHERE user_id = ?")->execute([$userId]);

    // Delete ledger accounts and wallets
    $pdo->prepare("DELETE FROM ledger_accounts WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM wallets WHERE user_id = ?")->execute([$userId]);

    // Ensure user 2 has default categories
    $catStmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Ăn uống' LIMIT 1");
    $catStmt->execute([$userId]);
    $catId = $catStmt->fetchColumn();
    if (!$catId) {
        $pdo->prepare("INSERT INTO categories (user_id, name, category_type) VALUES (?, 'Ăn uống', 'EXPENSE')")->execute([$userId]);
        $catId = $pdo->lastInsertId();
    }

    // 2. Seed Wallets
    $w1Cached = ($scenario === 'mismatch') ? '19999999.00000000' : '15000000.00000000';
    $w2Cached = '10000000.00000000';
    $w3Cached = '1000.00000000';

    $stmtW = $pdo->prepare("INSERT INTO wallets (user_id, name, wallet_type, currency, cached_balance, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')");
    $stmtW->execute([$userId, 'Ví Tiền mặt', 'CASH', 'VND', $w1Cached]);
    $w1Id = (int) $pdo->lastInsertId();

    $stmtW->execute([$userId, 'Ví Techcombank', 'BANK', 'VND', $w2Cached]);
    $w2Id = (int) $pdo->lastInsertId();

    $stmtW->execute([$userId, 'Ví Ngoại tệ USD', 'CASH', 'USD', $w3Cached]);
    $w3Id = (int) $pdo->lastInsertId();

    // 3. Seed Ledger Accounts
    $stmtAcc = $pdo->prepare("INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, ?)");
    $stmtAcc->execute([$userId, $w1Id, "ASSET_WALLET_{$w1Id}", 'ASSET']);
    $accW1 = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$userId, $w2Id, "ASSET_WALLET_{$w2Id}", 'ASSET']);
    $accW2 = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$userId, $w3Id, "ASSET_WALLET_{$w3Id}", 'ASSET']);
    $accW3 = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$userId, null, "EQUITY_OPENING", 'EQUITY']);
    $accEq = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$userId, null, "EXPENSE_GENERAL", 'EXPENSE']);
    $accExp = (int) $pdo->lastInsertId();

    // 4. Seed Transactions & Entries
    $stmtTx = $pdo->prepare("INSERT INTO financial_transactions (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on, posted_at) VALUES (?, ?, ?, 'POSTED', ?, ?, ?, ?, ?, ?)");
    $stmtEntry = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    // Tx 1: Số dư ban đầu Ví 1 & 2
    $stmtTx->execute([$userId, $catId, 'INCOME', 'Nạp số dư ban đầu Tiền mặt & Techcombank', 'VND', '25000000.00000000', '1.0000000000', $today, $now]);
    $tx1 = (int) $pdo->lastInsertId();

    $stmtEntry->execute([$tx1, $accW1, 'DEBIT', '15000000.00000000', 'VND', '15000000.00000000', '1.0000000000']);
    $stmtEntry->execute([$tx1, $accW2, 'DEBIT', '10000000.00000000', 'VND', '10000000.00000000', '1.0000000000']);
    $stmtEntry->execute([$tx1, $accEq, 'CREDIT', '25000000.00000000', 'VND', '25000000.00000000', '1.0000000000']);

    // Tx 2: Số dư ban đầu Ví USD
    $stmtTx->execute([$userId, $catId, 'INCOME', 'Nạp ngoại tệ ban đầu 1,000 USD', 'USD', '1000.00000000', '25000.0000000000', $today, $now]);
    $tx2 = (int) $pdo->lastInsertId();

    $stmtEntry->execute([$tx2, $accW3, 'DEBIT', '1000.00000000', 'USD', '25000000.00000000', '25000.0000000000']);
    $stmtEntry->execute([$tx2, $accEq, 'CREDIT', '1000.00000000', 'USD', '25000000.00000000', '25000.0000000000']);

    if ($scenario === 'formula_injection') {
        // Add malicious transaction to demonstrate sanitization
        $maliciousDesc = "=cmd|' /C calc'!A0";
        $stmtTx->execute([$userId, $catId, 'EXPENSE', $maliciousDesc, 'VND', '50000.00000000', '1.0000000000', $today, $now]);
        $txMal = (int) $pdo->lastInsertId();
        $stmtEntry->execute([$txMal, $accExp, 'DEBIT', '50000.00000000', 'VND', '50000.00000000', '1.0000000000']);
        $stmtEntry->execute([$txMal, $accW1, 'CREDIT', '50000.00000000', 'VND', '50000.00000000', '1.0000000000']);

        // Adjust cached balance of W1 for equilibrium
        $pdo->prepare("UPDATE wallets SET cached_balance = cached_balance - 50000 WHERE id = ?")->execute([$w1Id]);
    }

    if ($scenario === 'export_csv') {
        // Add realistic transactions
        $stmtTx->execute([$userId, $catId, 'EXPENSE', 'Ăn trưa văn phòng cùng đồng nghiệp', 'VND', '85000.00000000', '1.0000000000', $today, $now]);
        $txEx = (int) $pdo->lastInsertId();
        $stmtEntry->execute([$txEx, $accExp, 'DEBIT', '85000.00000000', 'VND', '85000.00000000', '1.0000000000']);
        $stmtEntry->execute([$txEx, $accW1, 'CREDIT', '85000.00000000', 'VND', '85000.00000000', '1.0000000000']);
        $pdo->prepare("UPDATE wallets SET cached_balance = cached_balance - 85000 WHERE id = ?")->execute([$w1Id]);
    }

    // 4. Seed User 2 (inksan27112006@gmail.com) with isolated private wallet for multi-tenant testing
    $stmtU2 = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtU2->execute(['inksan27112006@gmail.com']);
    $user2Id = $stmtU2->fetchColumn();
    if (!$user2Id) {
        $u2PassHash = password_hash('User1234', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, base_currency) VALUES (?, ?, ?, 'USER', 'ACTIVE', 'VND')")
            ->execute(['Người Dùng Độc Lập 2', 'inksan27112006@gmail.com', $u2PassHash]);
        $user2Id = (int) $pdo->lastInsertId();
    } else {
        $user2Id = (int) $user2Id;
        $u2PassHash = password_hash('User1234', PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET status = 'ACTIVE', password_hash = ? WHERE id = ?")->execute([$u2PassHash, $user2Id]);
    }
    $pdo->prepare("DELETE FROM ledger_entries WHERE transaction_id IN (SELECT id FROM financial_transactions WHERE user_id = ?)")->execute([$user2Id]);
    $pdo->prepare("DELETE FROM financial_transactions WHERE user_id = ?")->execute([$user2Id]);
    $pdo->prepare("DELETE FROM ledger_accounts WHERE user_id = ?")->execute([$user2Id]);
    $pdo->prepare("DELETE FROM wallets WHERE user_id = ?")->execute([$user2Id]);

    $stmtW->execute([$user2Id, 'Ví Tiết Kiệm BIDV (User 2)', 'BANK', 'VND', '88000000.00000000']);
    $wUser2Id = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$user2Id, $wUser2Id, "ASSET_WALLET_{$wUser2Id}", 'ASSET']);
    $accWUser2 = (int) $pdo->lastInsertId();

    $stmtAcc->execute([$user2Id, null, "EQUITY_OPENING", 'EQUITY']);
    $accEqUser2 = (int) $pdo->lastInsertId();

    $stmtTx->execute([$user2Id, null, 'INCOME', 'Nạp vốn mở tài khoản tiết kiệm BIDV', 'VND', '88000000.00000000', '1.0000000000', $today, $now]);
    $txUser2 = (int) $pdo->lastInsertId();
    $stmtEntry->execute([$txUser2, $accWUser2, 'DEBIT', '88000000.00000000', 'VND', '88000000.00000000', '1.0000000000']);
    $stmtEntry->execute([$txUser2, $accEqUser2, 'CREDIT', '88000000.00000000', 'VND', '88000000.00000000', '1.0000000000']);

    $pdo->commit();

    echo json_encode([
        'status' => 'OK',
        'scenario' => $scenario,
        'userId' => $userId,
        'wallets' => [
            'w1' => ['id' => $w1Id, 'name' => 'Ví Tiền mặt', 'cached' => $w1Cached],
            'w2' => ['id' => $w2Id, 'name' => 'Ví Techcombank', 'cached' => $w2Cached],
            'w3' => ['id' => $w3Id, 'name' => 'Ví Ngoại tệ USD', 'cached' => $w3Cached],
            'user2Wallet' => ['id' => $wUser2Id, 'name' => 'Ví Tiết Kiệm BIDV (User 2)', 'cached' => '88000000.00000000'],
        ],
        'message' => "Đã reset và nạp dữ liệu kịch bản '{$scenario}' thành công trên MySQL.",
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage() . ' at line ' . $e->getLine()]);
    exit(1);
}
