<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use WealthFlow\Core\Database;
use WealthFlow\Modules\Auth\UserDAO;
use WealthFlow\Modules\Privacy\AccountDataExportService;

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "[SKIP] pdo_sqlite is required for privacy tests.\n");
    exit(2);
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
Database::initSqliteSchema($db);
$db->exec("INSERT INTO users (id, full_name, email, password_hash, role, status, base_currency)
    VALUES (2, 'Privacy Test', 'privacy.test@wealthflow.local', 'must-not-export', 'USER', 'ACTIVE', 'VND')");
$db->exec("INSERT INTO categories (id, user_id, name, category_type) VALUES (20, 2, 'Test category', 'EXPENSE')");
$db->exec("INSERT INTO financial_transactions
    (id, user_id, category_id, transaction_type, status, description, currency, amount, occurred_on)
    VALUES (20, 2, 20, 'EXPENSE', 'POSTED', 'Owned transaction', 'VND', 1500, '2026-01-01'),
           (21, 1, 1, 'EXPENSE', 'POSTED', 'Other user transaction', 'VND', 9999, '2026-01-01')");
$db->exec("INSERT INTO ledger_accounts (id, user_id, wallet_id, code, account_type)
    VALUES (20, 2, NULL, 'TEST_EQUITY', 'EQUITY')");
$db->exec("INSERT INTO ledger_entries
    (transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base)
    VALUES (20, 20, 'DEBIT', 1500, 'VND', 1500)");

$result = (new AccountDataExportService($db))->export(2, '127.0.0.1');
$payload = json_decode($result['content'], true, 512, JSON_THROW_ON_ERROR);
if ($payload['account']['email'] !== 'privacy.test@wealthflow.local'
    || count($payload['data']['transactions']) !== 1
    || (int) $payload['data']['transactions'][0]['id'] !== 20
    || count($payload['data']['ledgerEntries']) !== 1
    || str_contains($result['content'], 'must-not-export')
    || str_contains($result['content'], 'password_hash')
    || !str_ends_with($result['filename'], '.json')) {
    fwrite(STDERR, "[FAIL] Export scope or credential exclusion failed.\n");
    exit(1);
}

$db->exec('CREATE TABLE auth_sessions (id TEXT PRIMARY KEY, user_id INTEGER NOT NULL)');
$db->exec("INSERT INTO auth_sessions (id, user_id) VALUES ('session-to-revoke', 2)");
$userDao = new UserDAO($db);
if (!$userDao->closeAccount(2)) {
    fwrite(STDERR, "[FAIL] Account was not closed.\n");
    exit(1);
}
$userDao->deleteSessionsByUser(2);
$userDao->recordSecurityAudit('ACCOUNT_CLOSED', 2);
$account = $userDao->findById(2);
$sessions = (int) $db->query('SELECT COUNT(*) FROM auth_sessions WHERE user_id = 2')->fetchColumn();
$auditCount = (int) $db->query("SELECT COUNT(*) FROM security_audit_logs WHERE user_id = 2 AND event = 'ACCOUNT_CLOSED'")->fetchColumn();
$financeCount = (int) $db->query('SELECT COUNT(*) FROM financial_transactions WHERE user_id = 2')->fetchColumn();
if ($account['status'] !== 'LOCKED' || empty($account['closed_at']) || $sessions !== 0 || $auditCount !== 1 || $financeCount !== 1) {
    fwrite(STDERR, "[FAIL] Closure did not preserve records and revoke sessions as expected.\n");
    exit(1);
}

echo "[PASS] Export includes only account-owned data and omits credentials.\n";
echo "[PASS] Account closure locks account, revokes sessions, audits, and retains finance records.\n";
