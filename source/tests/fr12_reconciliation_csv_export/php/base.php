<?php

declare(strict_types=1);

// Ensure deterministic test environment
putenv('APP_KEY=test_app_key_32_bytes_super_secure_key!');
putenv('APP_ENV=local');

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

use WealthFlow\Core\AuthContext;
use WealthFlow\Core\Database;
use WealthFlow\Core\Request;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Reports\ExportService;
use WealthFlow\Modules\Reports\ReconciliationService;
use WealthFlow\Modules\Reports\ReportController;
use WealthFlow\Modules\Reports\ReportDAO;
use WealthFlow\Modules\Wallets\WalletDAO;

/**
 * SQLite Compatibility wrapper for PDO in tests.
 */
class SqliteCompatPDO extends PDO
{
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->sqliteCreateFunction('UTC_TIMESTAMP', fn() => gmdate('Y-m-d H:i:s'));
    }

    public static function rewrite(string $sql): string
    {
        return preg_replace('/\s+FOR\s+UPDATE\b/i', '', $sql);
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return parent::prepare(self::rewrite($query), $options);
    }

    public function exec(string $statement): int|false
    {
        return parent::exec(self::rewrite($statement));
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $rewritten = self::rewrite($query);
        if ($fetchMode === null) {
            return parent::query($rewritten);
        }
        return parent::query($rewritten, $fetchMode, ...$fetchModeArgs);
    }
}

/**
 * Helper to build Request instance using $_GET and $_SERVER in CLI.
 */
function createTestRequest(array $query = [], string $method = 'GET'): Request
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = $query;
    return new Request();
}

/**
 * Standard test runner wrapper that outputs clean JSON for Python.
 */
function runAndSendJson(callable $testCallback): void
{
    try {
        $result = $testCallback();
        echo json_encode(['status' => 'OK', 'data' => $result], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    } catch (\Throwable $e) {
        echo json_encode([
            'status' => 'ERROR',
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(1);
    }
}

/**
 * Common test context setup with seeded test data for FR12.
 *
 * @return array{
 *   pdo: SqliteCompatPDO,
 *   reportDao: ReportDAO,
 *   auditLogDao: AuditLogDAO,
 *   walletDao: WalletDAO,
 *   reconService: ReconciliationService,
 *   exportService: ExportService,
 *   controller: ReportController
 * }
 */
function setupTestContext(): array
{
    $pdo = new SqliteCompatPDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    Database::initSqliteSchema($pdo);
    Database::setConnection($pdo, 'sqlite');

    // Seed test users
    $pdo->exec("INSERT OR IGNORE INTO users (id, full_name, email, password_hash, role, status, base_currency) VALUES
        (1, 'Võ Văn Khôi Nguyên', 'nguyen.user@wealthflow.local', 'hash1', 'USER', 'ACTIVE', 'VND'),
        (2, 'Trần Thị B', 'user2@wealthflow.local', 'hash2', 'USER', 'ACTIVE', 'VND')");

    // Seed wallets:
    // Wallet 10: Cash VND, cached_balance = 15,000,000 (Matched)
    // Wallet 11: USD Bank, cached_balance = 1,000 (Matched)
    // Wallet 12: Corrupted balance VND, cached_balance = 9,999,999, actual ledger = 5,000,000 (Mismatch)
    // Wallet 20: User 2 wallet (Ownership test)
    $pdo->exec("INSERT OR IGNORE INTO wallets (id, user_id, name, active_name_key, wallet_type, currency, cached_balance, status) VALUES
        (10, 1, 'Tiền mặt', 'tien-mat', 'CASH', 'VND', 15000000.00000000, 'ACTIVE'),
        (11, 1, 'USD Techcombank', 'usd-techcombank', 'BANK', 'USD', 1000.00000000, 'ACTIVE'),
        (12, 1, 'Ví Lỗi Số Dư', 'vi-loi-so-du', 'E_WALLET', 'VND', 9999999.00000000, 'ACTIVE'),
        (20, 2, 'Ví Người Khác', 'vi-nguoi-khac', 'CASH', 'VND', 2000000.00000000, 'ACTIVE')");

    // Seed categories
    $pdo->exec("INSERT OR IGNORE INTO categories (id, user_id, name, category_type) VALUES
        (1, 1, 'Ăn uống', 'EXPENSE'),
        (2, 1, 'Lương thưởng', 'INCOME'),
        (3, 1, 'Mua sắm', 'EXPENSE')");

    // Get or create EQUITY_OPENING account
    $equityAccount = $pdo->query("SELECT id FROM ledger_accounts WHERE user_id = 1 AND code = 'EQUITY_OPENING'")->fetch();
    $equityId = $equityAccount ? (int) $equityAccount['id'] : 100;

    // Seed ledger accounts
    $pdo->exec("INSERT OR IGNORE INTO ledger_accounts (id, user_id, wallet_id, code, account_type) VALUES
        ({$equityId}, 1, NULL, 'EQUITY_OPENING', 'EQUITY'),
        (101, 1, 10, 'ASSET_WALLET_10', 'ASSET'),
        (102, 1, 11, 'ASSET_WALLET_11', 'ASSET'),
        (103, 1, 12, 'ASSET_WALLET_12', 'ASSET'),
        (104, 1, NULL, 'EXP_FOOD', 'EXPENSE'),
        (105, 1, NULL, 'INC_SALARY', 'INCOME'),
        (201, 2, 20, 'ASSET_WALLET_20', 'ASSET')");

    // Seed financial transactions & ledger entries:
    // Tx 1: Opening balance Wallet 10 (+15.05M so after 50K expense it has 15M)
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (1, 1, NULL, 'OTHER', 'POSTED', 'Số dư mở sổ ví: Tiền mặt', 'VND', 15050000, 1, '2026-10-01')");
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (1, 1, 101, 'DEBIT', 15050000, 'VND', 15050000, 1),
        (2, 1, {$equityId}, 'CREDIT', 15050000, 'VND', 15050000, 1)");

    // Tx 2: Opening balance Wallet 11 (+1000 USD, fx=25000)
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (2, 1, NULL, 'OTHER', 'POSTED', 'Số dư mở sổ ví: USD Techcombank', 'USD', 1000, 25000, '2026-10-01')");
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (3, 2, 102, 'DEBIT', 1000, 'USD', 25000000, 25000),
        (4, 2, {$equityId}, 'CREDIT', 1000, 'USD', 25000000, 25000)");

    // Tx 3: Opening balance Wallet 12 (+5M) -> Intentionally lower than cached 9,999,999
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (3, 1, NULL, 'OTHER', 'POSTED', 'Số dư mở sổ ví: Ví Lỗi Số Dư', 'VND', 5000000, 1, '2026-10-01')");
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (5, 3, 103, 'DEBIT', 5000000, 'VND', 5000000, 1),
        (6, 3, {$equityId}, 'CREDIT', 5000000, 'VND', 5000000, 1)");

    // Tx 4: Formula Injection test transaction
    $stmt4 = $pdo->prepare("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (4, 1, 1, 'EXPENSE', 'POSTED', ?, 'VND', 50000, 1, '2026-10-15')");
    $stmt4->execute(["=cmd|' /C calc'!A0"]);
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (7, 4, 104, 'DEBIT', 50000, 'VND', 50000, 1),
        (8, 4, 101, 'CREDIT', 50000, 'VND', 50000, 1)");

    // Tx 5: Draft transaction (status = DRAFT, must be ignored by reconciliation and export filters)
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (5, 1, 3, 'EXPENSE', 'DRAFT', 'Mua sắm nháp', 'VND', 2000000, 1, '2026-10-20')");

    // Seed User 2 Tx (for multi-tenant isolation)
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (6, 2, NULL, 'OTHER', 'POSTED', 'Giao dịch bí mật User 2', 'VND', 2000000, 1, '2026-10-01')");
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (9, 6, 201, 'DEBIT', 2000000, 'VND', 2000000, 1)");

    // Authenticate test user 1
    AuthContext::setUserId(1);

    $reportDao = new ReportDAO($pdo);
    $auditLogDao = new AuditLogDAO($pdo);
    $walletDao = new WalletDAO($pdo);
    $reconService = new ReconciliationService($reportDao, $walletDao, $auditLogDao);
    $exportService = new ExportService($reportDao, $auditLogDao);
    $controller = new ReportController($reconService, $exportService);

    return [
        'pdo' => $pdo,
        'reportDao' => $reportDao,
        'auditLogDao' => $auditLogDao,
        'walletDao' => $walletDao,
        'reconService' => $reconService,
        'exportService' => $exportService,
        'controller' => $controller,
    ];
}
