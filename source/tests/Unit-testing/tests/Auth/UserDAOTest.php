<?php

declare(strict_types=1);

namespace WealthFlowTest\Auth;

use WealthFlow\Modules\Auth\UserDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class UserDAOTest extends DatabaseTestCase
{
    private UserDAO $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo->exec('CREATE TABLE auth_sessions (
            id TEXT PRIMARY KEY, user_id INTEGER, refresh_hash TEXT, expires_at DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('CREATE TABLE registration_emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, status TEXT DEFAULT "PENDING", attempts INTEGER DEFAULT 0,
            sent_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->dao = new UserDAO($this->pdo);
    }

    public function testGetPdoCreateFindAndListUsers(): void
    {
        self::assertSame($this->pdo, $this->dao->getPdo());
        $id = $this->dao->createUser('  Nguyễn Văn A ', ' A@Example.COM ', 'hash', 'vnd');
        self::assertGreaterThan(1, $id);
        self::assertSame($id, (int) $this->dao->findByEmail('a@example.com')['id']);
        self::assertSame($id, (int) $this->dao->findById($id)['id']);
        self::assertNull($this->dao->findById(999));
        self::assertNotEmpty($this->dao->listUsers(10, 0));
    }

    public function testPasswordStatusAndCloseAccountMutations(): void
    {
        self::assertTrue($this->dao->updatePassword(1, 'new-hash'));
        self::assertSame('new-hash', $this->dao->findById(1)['password_hash']);
        self::assertTrue($this->dao->closeAccount(1));
        self::assertSame('LOCKED', $this->dao->findById(1)['status']);
        $this->pdo->exec("UPDATE users SET status='ACTIVE', closed_at=NULL WHERE id=1");
        self::assertTrue($this->dao->updateStatus(1, 'locked'));
        self::assertSame('LOCKED', $this->dao->findById(1)['status']);
    }

    public function testCopyDefaultsAndQueueRegistrationEmail(): void
    {
        $this->pdo->exec("INSERT INTO default_categories (name, category_type, is_active) VALUES
            ('Test Expense', 'EXPENSE', 1), ('Inactive', 'EXPENSE', 0)");
        $this->dao->copyDefaultCategories(1);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM categories WHERE user_id=1 AND name='Test Expense'")->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM categories WHERE user_id=1 AND name='Inactive'")->fetchColumn());
        $this->dao->queueRegistrationEmail(1);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM registration_emails')->fetchColumn());
    }

    public function testSessionLifecycleAndExpiry(): void
    {
        $this->dao->createSession('session-a', 1, 'hash-a', '2099-01-01 00:00:00');
        self::assertSame('session-a', $this->dao->findActiveSession('session-a', 1)['session_id']);
        self::assertTrue($this->dao->updateSessionRefresh('session-a', 'hash-b'));
        self::assertSame('hash-b', $this->dao->findActiveSession('session-a', 1)['refresh_hash']);
        self::assertTrue($this->dao->deleteSession('session-a'));
        self::assertNull($this->dao->findActiveSession('session-a', 1));
        $this->dao->createSession('one', 1, 'x', '2099-01-01 00:00:00');
        $this->dao->createSession('two', 1, 'y', '2099-01-01 00:00:00');
        self::assertTrue($this->dao->deleteSessionsByUser(1));
        self::assertNull($this->dao->findActiveSession('one', 1));
    }

    public function testSecurityAuditIsNormalized(): void
    {
        $this->dao->recordSecurityAudit(' login_failed ', 1);
        self::assertSame(' login_failed ', $this->pdo->query('SELECT event FROM security_audit_logs')->fetchColumn());
    }
}
