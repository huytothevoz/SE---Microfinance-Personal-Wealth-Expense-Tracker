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
        $this->pdo->exec('DROP TABLE IF EXISTS auth_sessions; CREATE TABLE auth_sessions (id TEXT PRIMARY KEY,user_id INTEGER,refresh_hash TEXT,expires_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('DROP TABLE IF EXISTS registration_emails; CREATE TABLE registration_emails (id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,status TEXT DEFAULT "PENDING",attempts INTEGER DEFAULT 0,sent_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('DROP TABLE IF EXISTS password_reset_tokens; CREATE TABLE password_reset_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,token_hash TEXT,expires_at DATETIME,attempts INTEGER DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec('DROP TABLE IF EXISTS auth_rate_limits; CREATE TABLE auth_rate_limits (bucket TEXT PRIMARY KEY,attempts INTEGER DEFAULT 1,expires_at DATETIME)');
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

    private function randomSid(): string
    {
        return bin2hex(random_bytes(32));
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

    public function testPasswordRejectsMissingComplexity(): void
    {
        $cases = [
            ['password' => 'strong123', 'desc' => 'Thiếu chữ hoa'],
            ['password' => 'STRONG123', 'desc' => 'Thiếu chữ thường'],
            ['password' => 'StrongAbc', 'desc' => 'Thiếu chữ số'],
            ['password' => 'Str1', 'desc' => 'Dưới 8 ký tự'],
        ];

        foreach ($cases as $c) {
            $this->assertApi(
                fn() => $this->service->register([
                    'fullName' => 'Test User',
                    'email' => 'complex.' . uniqid() . '@example.com',
                    'password' => $c['password'],
                    'passwordConfirmation' => $c['password'],
                    'baseCurrency' => 'VND'
                ]),
                'VALIDATION_ERROR',
                422
            );
        }

        // Mật khẩu xác nhận không khớp
        $this->assertApi(
            fn() => $this->service->register([
                'fullName' => 'Test User',
                'email' => 'mismatch@example.com',
                'password' => 'Strong123',
                'passwordConfirmation' => 'Strong456',
                'baseCurrency' => 'VND'
            ]),
            'VALIDATION_ERROR',
            422
        );
    }

    public function testPasswordRejectsNullByteAndOver72Bytes(): void
    {
        // Chứa byte null (chống Bcrypt truncation)
        $this->assertApi(
            fn() => $this->service->register([
                'fullName' => 'Test User',
                'email' => 'nullbyte@example.com',
                'password' => "Strong123\0tail",
                'passwordConfirmation' => "Strong123\0tail",
                'baseCurrency' => 'VND'
            ]),
            'VALIDATION_ERROR',
            422
        );

        // Vượt quá 72 bytes (Bcrypt limit)
        $longPass = 'Strong123' . str_repeat('A', 70);
        $this->assertApi(
            fn() => $this->service->register([
                'fullName' => 'Test User',
                'email' => 'toolong@example.com',
                'password' => $longPass,
                'passwordConfirmation' => $longPass,
                'baseCurrency' => 'VND'
            ]),
            'VALIDATION_ERROR',
            422
        );
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $email = 'duplicate.test@example.com';
        $payload = [
            'fullName' => 'Nguyen Van A',
            'email' => $email,
            'password' => 'Strong123',
            'passwordConfirmation' => 'Strong123',
            'baseCurrency' => 'VND'
        ];

        // Lần đầu thành công
        $this->service->register($payload);

        // Lần hai phải bắt lỗi trùng lặp EMAIL_EXISTS (HTTP 409)
        $this->assertApi(
            fn() => $this->service->register($payload),
            'EMAIL_EXISTS',
            409
        );
    }

    public function testRequestPasswordResetPreventsUserEnumeration(): void
    {
        putenv('MAIL_FROM_ADDRESS=noreply@wealthflow.test');
        $validConfig = new Config(dirname(__DIR__, 4) . '/backend');
        $validService = new AuthService($this->pdo, $validConfig, $this->dao);

        $result = $validService->requestPasswordReset(['email' => 'nonexistent@example.com']);
        self::assertStringContainsString('Nếu email này đã đăng ký', $result['message']);
    }

    public function testChangePasswordSuccessAndRevokesOldSessions(): void
    {
        $this->pdo->exec("UPDATE users SET email='changepass@example.com',password_hash='" . password_hash('OldStrong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE' WHERE id=1");

        $sid1 = $this->randomSid();
        $sid2 = $this->randomSid();
        $this->dao->createSession($sid1, 1, 'hash1', '2099-01-01 00:00:00');
        $this->dao->createSession($sid2, 1, 'hash2', '2099-01-01 00:00:00');

        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid1, 'access', time() + 300);

        $res = $this->service->changePassword([
            'currentPassword' => 'OldStrong123',
            'newPassword' => 'NewStrong123',
            'passwordConfirmation' => 'NewStrong123'
        ]);
        self::assertStringContainsString('Đã đổi mật khẩu', $res['message']);

        self::assertNull($this->dao->findActiveSession($sid1, 1));
        self::assertNull($this->dao->findActiveSession($sid2, 1));

        $loginRes = $this->service->login(['email' => 'changepass@example.com', 'password' => 'NewStrong123']);
        self::assertSame(1, $loginRes['user']['id']);
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $this->pdo->exec("UPDATE users SET email='changepass2@example.com',password_hash='" . password_hash('OldStrong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE' WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $this->assertApi(
            fn() => $this->service->changePassword([
                'currentPassword' => 'WrongCurrentPassword123',
                'newPassword' => 'NewStrong123',
                'passwordConfirmation' => 'NewStrong123'
            ]),
            'INVALID_PASSWORD',
            422
        );
    }

    public function testChangePasswordRejectsMismatchedConfirmation(): void
    {
        $this->pdo->exec("UPDATE users SET email='changepass3@example.com',password_hash='" . password_hash('OldStrong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE' WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $this->assertApi(
            fn() => $this->service->changePassword([
                'currentPassword' => 'OldStrong123',
                'newPassword' => 'NewStrong123',
                'passwordConfirmation' => 'Different123'
            ]),
            'VALIDATION_ERROR',
            422
        );
    }

    public function testCloseAccountSuccess(): void
    {
        $this->pdo->exec("UPDATE users SET email='closeme@example.com',password_hash='" . password_hash('CloseStrong123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE',closed_at=NULL WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $res = $this->service->closeAccount([
            'confirmation' => 'DONG TAI KHOAN',
            'currentPassword' => 'CloseStrong123'
        ]);
        self::assertStringContainsString('Tài khoản đã được đóng', $res['message']);

        $user = $this->dao->findById(1);
        self::assertSame('LOCKED', $user['status']);
        self::assertNotNull($user['closed_at']);

        self::assertNull($this->dao->findActiveSession($sid, 1));
    }

    public function testCloseAccountRejectsWrongConfirmationPhrase(): void
    {
        $this->pdo->exec("UPDATE users SET status='ACTIVE',closed_at=NULL WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $this->assertApi(
            fn() => $this->service->closeAccount([
                'confirmation' => 'dong tai khoan',
                'currentPassword' => 'CloseStrong123'
            ]),
            'VALIDATION_ERROR',
            422
        );
    }

    public function testCloseAccountRejectsWrongPassword(): void
    {
        $this->pdo->exec("UPDATE users SET password_hash='" . password_hash('CorrectPass123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE',closed_at=NULL WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $this->assertApi(
            fn() => $this->service->closeAccount([
                'confirmation' => 'DONG TAI KHOAN',
                'currentPassword' => 'WrongPass123'
            ]),
            'INVALID_PASSWORD',
            422
        );
    }

    public function testCloseAccountRejectsAlreadyClosedAccount(): void
    {
        // Tài khoản có status=ACTIVE nhưng đã ghi nhận closed_at (kịch bản dữ liệu đóng)
        $this->pdo->exec("UPDATE users SET status='ACTIVE',closed_at='2026-11-01 10:00:00' WHERE id=1");
        $sid = $this->randomSid();
        $this->dao->createSession($sid, 1, 'hash', '2099-01-01 00:00:00');
        $tokens = new TokenService($this->config);
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);

        $this->assertApi(
            fn() => $this->service->closeAccount([
                'confirmation' => 'DONG TAI KHOAN',
                'currentPassword' => 'Any123'
            ]),
            'ACCOUNT_CLOSED',
            409
        );
    }

    public function testLoginDifferentiatesClosedVsLockedAccounts(): void
    {
        $passHash = password_hash('Strong123', PASSWORD_BCRYPT, ['cost' => 12]);

        // 1. Tài khoản tự nguyện đóng (closed_at có giá trị) -> ACCOUNT_CLOSED (403)
        $this->pdo->exec("UPDATE users SET email='selfclosed@example.com',password_hash='{$passHash}',status='LOCKED',closed_at='2026-11-01 12:00:00' WHERE id=1");
        $this->assertApi(
            fn() => $this->service->login(['email' => 'selfclosed@example.com', 'password' => 'Strong123']),
            'ACCOUNT_CLOSED',
            403
        );

        // 2. Tài khoản bị Admin khóa vi phạm (closed_at là NULL) -> ACCOUNT_LOCKED (403)
        $this->pdo->exec("UPDATE users SET email='adminlocked@example.com',password_hash='{$passHash}',status='LOCKED',closed_at=NULL WHERE id=1");
        $this->assertApi(
            fn() => $this->service->login(['email' => 'adminlocked@example.com', 'password' => 'Strong123']),
            'ACCOUNT_LOCKED',
            403
        );
    }

    public function testResetPasswordSuccessWithValidOtp(): void
    {
        $this->pdo->exec("UPDATE users SET email='resetme@example.com',password_hash='" . password_hash('OldPass123', PASSWORD_BCRYPT, ['cost'=>12]) . "',status='ACTIVE' WHERE id=1");
        $otp = '654321';
        $tokenHash = hash_hmac('sha256', $otp, $this->config->get('APP_KEY'));
        $this->pdo->exec("DELETE FROM password_reset_tokens WHERE user_id=1");
        $this->pdo->exec("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, attempts) VALUES (1, '{$tokenHash}', datetime('now', '+10 minutes'), 0)");

        $res = $this->service->resetPassword([
            'email' => 'resetme@example.com',
            'otp' => '654321',
            'newPassword' => 'NewStrongPass123',
            'passwordConfirmation' => 'NewStrongPass123'
        ]);
        self::assertStringContainsString('Mật khẩu đã được đặt lại', $res['message']);

        $cnt = (int) $this->pdo->query('SELECT COUNT(*) FROM password_reset_tokens WHERE user_id=1')->fetchColumn();
        self::assertSame(0, $cnt);

        $loginRes = $this->service->login(['email' => 'resetme@example.com', 'password' => 'NewStrongPass123']);
        self::assertSame(1, $loginRes['user']['id']);
    }

    public function testResetPasswordRejectsExpiredOtp(): void
    {
        $this->pdo->exec("UPDATE users SET email='expired@example.com',password_hash='dummy',status='ACTIVE' WHERE id=1");
        $otp = '112233';
        $tokenHash = hash_hmac('sha256', $otp, $this->config->get('APP_KEY'));
        $this->pdo->exec("DELETE FROM password_reset_tokens WHERE user_id=1");
        $this->pdo->exec("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, attempts) VALUES (1, '{$tokenHash}', datetime('now', '-10 minutes'), 0)");

        $this->assertApi(
            fn() => $this->service->resetPassword([
                'email' => 'expired@example.com',
                'otp' => '112233',
                'newPassword' => 'NewStrongPass123',
                'passwordConfirmation' => 'NewStrongPass123'
            ]),
            'INVALID_RESET_OTP',
            400
        );
    }

    public function testResetPasswordLocksAfter5FailedAttempts(): void
    {
        $this->pdo->exec("UPDATE users SET email='lockedotp@example.com',password_hash='dummy',status='ACTIVE' WHERE id=1");
        $otp = '999999';
        $tokenHash = hash_hmac('sha256', $otp, $this->config->get('APP_KEY'));
        $this->pdo->exec("DELETE FROM password_reset_tokens WHERE user_id=1");
        $this->pdo->exec("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, attempts) VALUES (1, '{$tokenHash}', datetime('now', '+10 minutes'), 5)");

        $this->assertApi(
            fn() => $this->service->resetPassword([
                'email' => 'lockedotp@example.com',
                'otp' => '999999',
                'newPassword' => 'NewStrongPass123',
                'passwordConfirmation' => 'NewStrongPass123'
            ]),
            'INVALID_RESET_OTP',
            400
        );
    }

    public function testLoginRateLimitingTriggered(): void
    {
        $this->pdo->exec("UPDATE users SET email='ratelimit@example.com',status='ACTIVE' WHERE id=1");

        for ($i = 0; $i < 10; $i++) {
            try {
                $this->service->login(['email' => 'ratelimit@example.com', 'password' => 'Wrong123']);
            } catch (ApiException $e) {
                self::assertSame('INVALID_CREDENTIALS', $e->errorCode);
            }
        }

        $this->assertApi(
            fn() => $this->service->login(['email' => 'ratelimit@example.com', 'password' => 'Wrong123']),
            'RATE_LIMITED',
            429
        );
    }

    public function testAuthenticateRejectsTamperedOrRevokedToken(): void
    {
        $_COOKIE['wf_access'] = 'invalid.jwt.signature.here';
        $this->assertApi(fn() => $this->service->authenticate(), 'UNAUTHENTICATED', 401);

        $tokens = new TokenService($this->config);
        $sid = $this->randomSid();
        $_COOKIE['wf_access'] = $tokens->issue(1, $sid, 'access', time() + 300);
        $this->assertApi(fn() => $this->service->authenticate(), 'UNAUTHENTICATED', 401);
    }

    private function assertApi(callable $call, string $code, int $status): void
    {
        try { $call(); self::fail("Expected {$code}"); }
        catch (ApiException $e) { self::assertSame($code,$e->errorCode); self::assertSame($status,$e->status); }
    }
}
