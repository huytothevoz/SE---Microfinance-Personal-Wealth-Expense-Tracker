<?php

declare(strict_types=1);

// Ensure deterministic test environment
putenv('APP_KEY=test_app_key_32_bytes_super_secure_key!');
putenv('APP_ENV=local');

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

use WealthFlow\Core\Config;
use WealthFlow\Core\Database;
use WealthFlow\Core\Request;
use WealthFlow\Core\TokenService;
use WealthFlow\Modules\Auth\AuthController;
use WealthFlow\Modules\Auth\AuthService;
use WealthFlow\Modules\Auth\UserDAO;

/**
 * SQLite Compatibility wrapper for PDO.
 * Translates MySQL-specific queries (FOR UPDATE, ON DUPLICATE KEY UPDATE)
 * to standard SQLite syntax for isolated testing.
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
        $sql = preg_replace('/\s+FOR\s+UPDATE\b/i', '', $sql);
        if (stripos($sql, 'ON DUPLICATE KEY UPDATE') !== false) {
            $sql = preg_replace(
                '/INSERT\s+INTO\s+auth_rate_limits\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)\s*ON\s+DUPLICATE\s+KEY\s+UPDATE\s+attempts\s*=\s*attempts\s*\+\s*1/i',
                'INSERT INTO auth_rate_limits ($1) VALUES ($2) ON CONFLICT(bucket) DO UPDATE SET attempts = attempts + 1',
                $sql
            );
        }
        return $sql;
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
 * Helper to build Request instance using $_POST and $_SERVER in CLI.
 */
function createTestRequest(array $body = [], string $method = 'POST'): Request
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_POST = $body;
    return new Request();
}

/**
 * Common test context setup.
 *
 * @return array{
 *   pdo: SqliteCompatPDO,
 *   config: Config,
 *   userDao: UserDAO,
 *   authService: AuthService,
 *   authController: AuthController,
 *   tokenService: TokenService
 * }
 */
function setupTestContext(): array
{
    $pdo = new SqliteCompatPDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    Database::initSqliteSchema($pdo);

    $schema = <<<SQL
    CREATE TABLE IF NOT EXISTS auth_sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER NOT NULL,
        refresh_hash TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token_hash TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS auth_rate_limits (
        bucket TEXT PRIMARY KEY,
        attempts INTEGER NOT NULL DEFAULT 1,
        expires_at DATETIME NOT NULL
    );

    CREATE TABLE IF NOT EXISTS security_audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        event TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS registration_emails (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'PENDING',
        attempts INTEGER NOT NULL DEFAULT 0,
        sent_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
SQL;
    $pdo->exec($schema);

    // Seed default categories for copyDefaultCategories test
    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM default_categories')->fetchColumn();
    if ($catCount === 0) {
        $pdo->exec("INSERT INTO default_categories (name, category_type, is_active) VALUES
            ('Lương', 'INCOME', 1),
            ('Thưởng & Phụ cấp', 'INCOME', 1),
            ('Thu nhập đầu tư', 'INCOME', 1),
            ('Thu nhập khác', 'INCOME', 1),
            ('Ăn uống', 'EXPENSE', 1),
            ('Đi lại & Xăng xe', 'EXPENSE', 1),
            ('Nhà ở & Tiền điện nước', 'EXPENSE', 1),
            ('Mua sắm & Tiêu dùng', 'EXPENSE', 1),
            ('Giải trí & Du lịch', 'EXPENSE', 1),
            ('Y tế & Sức khỏe', 'EXPENSE', 1),
            ('Giáo dục & Đào tạo', 'EXPENSE', 1),
            ('Chi phí tài chính & Lãi vay', 'EXPENSE', 1)");
    }

    Database::setConnection($pdo, 'sqlite');
    $config = new Config(dirname(__DIR__, 3) . '/backend');
    $userDao = new UserDAO($pdo);
    $authService = new AuthService($pdo, $config, $userDao);
    $authController = new AuthController($authService, $config);
    $tokenService = new TokenService($config);

    return [
        'pdo' => $pdo,
        'config' => $config,
        'userDao' => $userDao,
        'authService' => $authService,
        'authController' => $authController,
        'tokenService' => $tokenService,
    ];
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
