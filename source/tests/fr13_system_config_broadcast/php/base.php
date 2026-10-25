<?php

declare(strict_types=1);

putenv('APP_KEY=test_app_key_32_bytes_super_secure_key!');
putenv('APP_ENV=local');

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

use WealthFlow\Core\AuthContext;
use WealthFlow\Core\Database;
use WealthFlow\Core\Request;
use WealthFlow\Modules\Admin\AdminController;
use WealthFlow\Modules\Admin\AdminDAO;
use WealthFlow\Modules\Admin\AdminService;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Auth\UserDAO;
use WealthFlow\Modules\Notifications\NotificationDAO;

class SqliteCompatPDO extends PDO
{
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->sqliteCreateFunction('UTC_TIMESTAMP', fn() => gmdate('Y-m-d H:i:s'));
        $this->sqliteCreateFunction('LOWER', fn($str) => mb_strtolower((string) $str, 'UTF-8'));
        $this->sqliteCreateFunction('UPPER', fn($str) => mb_strtoupper((string) $str, 'UTF-8'));
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

function createTestRequest(array $body = [], string $method = 'GET'): Request
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $_SERVER['HTTP_X_WEALTHFLOW'] = '1';
    $_POST = $body;
    return new Request();
}

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
 * @return array{
 *   pdo: SqliteCompatPDO,
 *   adminDao: AdminDAO,
 *   auditLogDao: AuditLogDAO,
 *   adminService: AdminService,
 *   controller: AdminController,
 *   userDao: UserDAO
 * }
 */
function setupAdminTestContext(): array
{
    $pdo = new SqliteCompatPDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    Database::initSqliteSchema($pdo);
    Database::setConnection($pdo, 'sqlite');

    // Seed test users: ID 1 is ADMIN, ID 2 is regular USER, ID 3 is new registrant
    $pdo->exec("INSERT OR REPLACE INTO users (id, full_name, email, password_hash, role, status, base_currency) VALUES
        (1, 'Hệ thống Quản trị', 'admin@wealthflow.local', 'hash_admin', 'ADMIN', 'ACTIVE', 'VND'),
        (2, 'Người dùng hiện hữu', 'user@wealthflow.local', 'hash_user', 'USER', 'ACTIVE', 'VND'),
        (3, 'Người dùng mới đăng ký', 'newuser@wealthflow.local', 'hash_new', 'USER', 'ACTIVE', 'VND')");

    // Seed initial default categories
    $pdo->exec("INSERT OR IGNORE INTO default_categories (id, name, category_type, is_active) VALUES
        (1, 'Ăn uống', 'EXPENSE', 1),
        (2, 'Mua sắm', 'EXPENSE', 1),
        (3, 'Lương', 'INCOME', 1),
        (4, 'Danh mục tạm ngưng cũ', 'EXPENSE', 0)");

    // Seed existing categories for User ID 2 (to verify isolation)
    $pdo->exec("INSERT INTO categories (user_id, name, category_type) VALUES
        (2, 'Ăn uống cá nhân', 'EXPENSE'),
        (2, 'Lương cá nhân', 'INCOME')");

    $adminDao = new AdminDAO($pdo);
    $auditLogDao = new AuditLogDAO($pdo);
    $notifDao = new NotificationDAO($pdo);
    $adminService = new AdminService($adminDao, $auditLogDao, $notifDao);
    $controller = new AdminController($adminService);
    $userDao = new UserDAO($pdo);

    return [
        'pdo' => $pdo,
        'adminDao' => $adminDao,
        'auditLogDao' => $auditLogDao,
        'notifDao' => $notifDao,
        'adminService' => $adminService,
        'controller' => $controller,
        'userDao' => $userDao,
    ];
}
