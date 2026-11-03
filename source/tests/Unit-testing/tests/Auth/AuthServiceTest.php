<?php
declare(strict_types=1);
namespace WealthFlowTest\Auth;

use WealthFlow\Core\ApiException;
use WealthFlow\Core\Config;
use WealthFlow\Core\TokenService;
use WealthFlow\Modules\Auth\AuthService;
use WealthFlow\Modules\Auth\UserDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class AuthServiceTest extends DatabaseTestCase
{
    private AuthService $service;
    private UserDAO $dao;
    private Config $config;
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['APP_KEY','APP_URL','APP_ENV','MAIL_TRANSPORT','MAIL_FROM_ADDRESS'] as $key) {
            $this->environment[$key] = getenv($key);
        }
        putenv('APP_KEY=0123456789abcdef0123456789abcdef');
        putenv('APP_URL=http://localhost:8080');
        putenv('APP_ENV=local');
        putenv('MAIL_TRANSPORT=log');
        putenv('MAIL_FROM_ADDRESS=invalid-address');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.99';
        $_COOKIE = [];
        $this->pdo->exec('CREATE TABLE auth_sessions (id TEXT PRIMARY KEY,user_id INTEGER,refresh_hash TEXT,expires_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('CREATE TABLE registration_emails (id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,status TEXT DEFAULT "PENDING",attempts INTEGER DEFAULT 0,sent_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('CREATE TABLE password_reset_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,token_hash TEXT,expires_at DATETIME,attempts INTEGER DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('CREATE TABLE auth_rate_limits (bucket TEXT PRIMARY KEY,attempts INTEGER DEFAULT 1,expires_at DATETIME)');
        $this->config = new Config(dirname(__DIR__, 4) . '/backend');
        $this->dao = new UserDAO($this->pdo);
        $this->service = new AuthService($this->pdo, $this->config, $this->dao);
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        foreach ($this->environment as $key => $value) {
            $value === false ? putenv($key) : putenv("{$key}={$value}");
        }
        parent::tearDown();
    }

    public function testRegisterSuccessAndValidation(): void
    {
        $result = $this->service->register([
            'fullName'=>' Nguyễn A ','email'=>'AUTH.NEW@EXAMPLE.COM','password'=>'Strong123',
            'passwordConfirmation'=>'Strong123','baseCurrency'=>'VND'
        ]);
        self::assertStringContainsString('Đăng ký thành công', $result['message']);
        self::assertSame('queued', $result['emailDelivery']);
        self::assertNotNull($this->dao->findByEmail('auth.new@example.com'));
        $this->assertApi(fn()=>$this->service->register([]), 'VALIDATION_ERROR', 422);
    }

    public function testLoginAuthenticateMeRefreshAndLogout(): void
    {
        $this->pdo->exec("UPDATE users SET email='login@example.com',password_hash='" . password_hash('Strong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE' WHERE id=1");
        $result = $this->service->login(['email'=>'LOGIN@example.com','password'=>'Strong123']);
        self::assertSame(1, $result['user']['id']);
        $session = $this->pdo->query('SELECT * FROM auth_sessions ORDER BY created_at DESC LIMIT 1')->fetch();
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $session['id'], 'access', time()+300);
        $refresh = $tokens->issue(1, $session['id'], 'refresh', strtotime($session['expires_at'].' UTC'));
        $this->dao->updateSessionRefresh($session['id'], hash('sha256', $refresh));
        $_COOKIE['wf_refresh'] = $refresh;
        self::assertSame(1, (int)$this->service->authenticate()['id']);
        self::assertSame(1, $this->service->me()['user']['id']);
        self::assertSame(1, $this->service->refresh()['user']['id']);
        self::assertStringContainsString('Đã đăng xuất', $this->service->logout()['message']);
    }

    public function testLoginRejectsUnknownAndLockedAccounts(): void
    {
        $this->assertApi(fn()=>$this->service->login(['email'=>'none@example.com','password'=>'Wrong123']), 'INVALID_CREDENTIALS', 401);
        $this->pdo->exec("UPDATE users SET email='locked@example.com',password_hash='" . password_hash('Strong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='LOCKED' WHERE id=1");
        $this->assertApi(fn()=>$this->service->login(['email'=>'locked@example.com','password'=>'Strong123']), 'ACCOUNT_LOCKED', 403);
    }

    public function testProtectedMethodsRejectMissingAuthentication(): void
    {
        $this->assertApi(fn()=>$this->service->authenticate(), 'UNAUTHENTICATED', 401);
        $this->assertApi(fn()=>$this->service->changePassword([]), 'UNAUTHENTICATED', 401);
        $this->assertApi(fn()=>$this->service->closeAccount([]), 'UNAUTHENTICATED', 401);
        self::assertStringContainsString('Đã đăng xuất', $this->service->logout()['message']);
    }

    public function testPasswordResetValidationAndUnavailableMail(): void
    {
        $this->assertApi(fn()=>$this->service->requestPasswordReset(['email'=>'bad']), 'VALIDATION_ERROR', 422);
        $this->assertApi(fn()=>$this->service->requestPasswordReset(['email'=>'user@example.com']), 'OTP_SERVICE_UNAVAILABLE', 503);
        $this->assertApi(fn()=>$this->service->resetPassword(['email'=>'bad','otp'=>'1','newPassword'=>'x','passwordConfirmation'=>'y']), 'VALIDATION_ERROR', 422);
        $this->assertApi(fn()=>$this->service->resetPassword(['email'=>'user@example.com','otp'=>'123','newPassword'=>'Strong123','passwordConfirmation'=>'Strong123']), 'INVALID_RESET_OTP', 400);
    }

    private function assertApi(callable $call, string $code, int $status): void
    {
        try { $call(); self::fail("Expected {$code}"); }
        catch (ApiException $e) { self::assertSame($code,$e->errorCode); self::assertSame($status,$e->status); }
    }
}
