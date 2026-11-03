<?php

declare(strict_types=1);

namespace WealthFlowTest\Support;

use PDO;
use PHPUnit\Framework\TestCase;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Admin\AdminDAO;
use WealthFlow\Modules\Admin\AdminService;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Notifications\NotificationDAO;

abstract class AdminTestCase extends TestCase
{
    protected PDO $pdo;
    protected AdminDAO $adminDao;
    protected AuditLogDAO $auditLogDao;
    protected NotificationDAO $notificationDao;
    protected AdminService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        Database::initSqliteSchema($this->pdo);
        Database::setConnection($this->pdo, 'sqlite');

        $this->pdo->exec("UPDATE users SET
            full_name = 'Admin chính', email = 'admin@wealthflow.test',
            password_hash = 'hash', role = 'ADMIN', status = 'ACTIVE', base_currency = 'VND'
            WHERE id = 1");
        $this->pdo->exec("INSERT INTO users
            (id, full_name, email, password_hash, role, status, base_currency) VALUES
            (2, 'Người dùng A', 'user-a@wealthflow.test', 'hash', 'USER', 'ACTIVE', 'VND'),
            (3, 'Người dùng khóa', 'locked@wealthflow.test', 'hash', 'USER', 'LOCKED', 'USD')");

        $this->pdo->exec('DELETE FROM default_categories');
        $this->pdo->exec("INSERT INTO default_categories
            (id, name, category_type, is_active) VALUES
            (1, 'Lương', 'INCOME', 1),
            (2, 'Ăn uống', 'EXPENSE', 1),
            (3, 'Danh mục cũ', 'EXPENSE', 0)");

        $this->adminDao = new AdminDAO($this->pdo);
        $this->auditLogDao = new AuditLogDAO($this->pdo);
        $this->notificationDao = new NotificationDAO($this->pdo);
        $this->service = new AdminService(
            $this->adminDao,
            $this->auditLogDao,
            $this->notificationDao
        );
    }

    protected function tearDown(): void
    {
        Database::reset();
        parent::tearDown();
    }

    protected function assertApiException(
        callable $operation,
        string $errorCode,
        int $status,
        ?string $field = null
    ): void {
        try {
            $operation();
            self::fail("Expected ApiException {$errorCode} was not thrown.");
        } catch (\WealthFlow\Core\ApiException $error) {
            self::assertSame($errorCode, $error->errorCode);
            self::assertSame($status, $error->status);
            if ($field !== null) {
                self::assertArrayHasKey($field, $error->fields);
            }
        }
    }
}
