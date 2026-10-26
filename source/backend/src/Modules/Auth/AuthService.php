<?php
declare(strict_types=1);

namespace WealthFlow\Modules\Auth;

use PDO;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\Config;
use WealthFlow\Core\EmailService;
use WealthFlow\Core\TokenService;
use WealthFlow\Core\WelcomeEmailQueue;

final class AuthService
{
    private TokenService $tokens;
    private UserDAO $userDao;

    public function __construct(
        private readonly PDO $db,
        private readonly Config $config,
        ?UserDAO $userDao = null
    ) {
        $this->tokens = new TokenService($config);
        $this->userDao = $userDao ?? new UserDAO($db);
    }

    public function register(array $input): array
    {
        $this->limit('register-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 10, 3600);
        $name = trim($this->string($input, 'fullName'));
        $email = strtolower(trim($this->string($input, 'email')));
        $password = $this->string($input, 'password');
        $currency = $this->string($input, 'baseCurrency');
        $fields = $this->passwordErrors($password);
        if ($name === '' || mb_strlen($name) > 150) $fields['fullName'] = 'Họ tên cần từ 1 đến 150 ký tự.';
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $fields['email'] = 'Địa chỉ email không hợp lệ.';
        if (!in_array($currency, ['VND', 'USD', 'EUR'], true)) $fields['baseCurrency'] = 'Chọn VND, USD hoặc EUR.';
        if ($password !== $this->string($input, 'passwordConfirmation')) $fields['passwordConfirmation'] = 'Mật khẩu xác nhận không khớp.';
        if ($fields) throw new ApiException('VALIDATION_ERROR', 'Vui lòng kiểm tra thông tin đăng ký.', 422, $fields);
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->beginTransaction();
        try {
            $id = $this->userDao->createUser($name, $email, $hash, $currency);
            $this->userDao->copyDefaultCategories($id);
            $this->userDao->queueRegistrationEmail($id);
            $this->userDao->recordSecurityAudit('REGISTER', $id);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof \PDOException && ($error->errorInfo[1] ?? 0) === 1062) {
                throw new ApiException('EMAIL_EXISTS', 'Email đã được đăng ký.', 409, ['email' => 'Email đã được đăng ký.']);
            }
            throw $error;
        }
        $sent = (new WelcomeEmailQueue($this->db, new EmailService($this->config)))->deliver($id);
        return [
            'message' => 'Đăng ký thành công. Bạn có thể đăng nhập ngay.',
            'emailDelivery' => $sent ? ($this->config->get('MAIL_TRANSPORT') === 'log' ? 'preview' : 'sent') : 'queued',
        ];
    }

    public function login(array $input): array
    {
        $email = strtolower(trim($this->string($input, 'email')));
        $password = $this->string($input, 'password');
        $this->limit('login-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 50, 900);
        $this->limit('login-email:' . $email, 10, 900);
        $this->db->beginTransaction();
        try {
            $user = $this->userDao->findByEmail($email, true);
            // Fixed valid cost-12 hash keeps unknown-email verification comparable.
            $hash = $user['password_hash'] ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
            if (!$valid || !$user) {
                $this->userDao->recordSecurityAudit('LOGIN_FAILED');
                $this->db->commit();
                throw new ApiException('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không chính xác.', 401);
            }
            if ($user['status'] !== 'ACTIVE') {
                $this->userDao->recordSecurityAudit('LOGIN_LOCKED', (int) $user['id']);
                $this->db->commit();
                if (!empty($user['closed_at'])) {
                    throw new ApiException('ACCOUNT_CLOSED', 'Tài khoản này đã được đóng. Các phiên đăng nhập đã bị thu hồi.', 403);
                }
                throw new ApiException('ACCOUNT_LOCKED', 'Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên.', 403);
            }
            $session = $this->newSession((int) $user['id']);
            $this->userDao->recordSecurityAudit('LOGIN', (int) $user['id']);
            $this->db->commit();
            $this->tokens->cookies(...$session);
            return ['user' => $this->profile($user)];
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    /** Every protected request checks ACTIVE and a live server-side session. */
    public function authenticate(bool $refresh = false): array
    {
        $type = $refresh ? 'refresh' : 'access';
        $token = (string) ($_COOKIE['wf_' . $type] ?? '');
        $claims = $this->tokens->decode($token, $type);
        $row = $this->userDao->findActiveSession((string) $claims->sid, (int) $claims->sub);
        if (!$row || ($refresh && !hash_equals($row['refresh_hash'], hash('sha256', $token)))) {
            throw new ApiException('UNAUTHENTICATED', 'Phiên đăng nhập không còn hiệu lực. Vui lòng đăng nhập lại.', 401);
        }
        return $row;
    }

    public function me(): array
    {
        return ['user' => $this->profile($this->authenticate())];
    }

    public function refresh(): array
    {
        $user = $this->authenticate(true);
        $this->db->beginTransaction();
        try {
            $this->userDao->findById((int) $user['id'], true);
            $user = $this->authenticate(true);
            $expiry = strtotime($user['expires_at'] . ' UTC');
            $refresh = $this->tokens->issue((int) $user['id'], $user['session_id'], 'refresh', $expiry);
            $access = $this->tokens->issue((int) $user['id'], $user['session_id'], 'access', time() + TokenService::ACCESS_TTL);
            $this->userDao->updateSessionRefresh((string) $user['session_id'], hash('sha256', $refresh));
            $this->db->commit();
            $this->tokens->cookies($access, $refresh, $expiry);
            return ['user' => $this->profile($user)];
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function logout(): array
    {
        // Refresh cookie also permits logout when the short-lived access JWT expired.
        try {
            $user = $this->authenticate(true);
            $this->db->beginTransaction();
            $this->userDao->findById((int) $user['id'], true);
            $this->userDao->deleteSession((string) $user['session_id']);
            $this->userDao->recordSecurityAudit('LOGOUT', (int) $user['id']);
            $this->db->commit();
        } catch (ApiException) {
            // Idempotent logout for expired / already revoked sessions.
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        $this->tokens->clearCookies();
        return ['message' => 'Đã đăng xuất.'];
    }

    public function changePassword(array $input): array
    {
        $user = $this->authenticate();
        $this->limit('password:' . $user['id'], 10, 900);
        $password = $this->string($input, 'newPassword');
        $fields = $this->passwordErrors($password);
        if ($password !== $this->string($input, 'passwordConfirmation')) $fields['passwordConfirmation'] = 'Mật khẩu xác nhận không khớp.';
        if ($fields) throw new ApiException('VALIDATION_ERROR', 'Mật khẩu mới chưa hợp lệ.', 422, $fields);
        $this->db->beginTransaction();
        try {
            $this->userDao->findById((int) $user['id'], true);
            $user = $this->authenticate();
            $current = $this->string($input, 'currentPassword');
            if (strlen($current) > 72 || str_contains($current, "\0") || !password_verify($current, $user['password_hash'])) {
                throw new ApiException('INVALID_PASSWORD', 'Mật khẩu hiện tại không chính xác.', 422, ['currentPassword' => 'Mật khẩu hiện tại không chính xác.']);
            }
            $this->userDao->updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
            $this->userDao->deleteSessionsByUser((int) $user['id']);
            $session = $this->newSession((int) $user['id']);
            $this->userDao->recordSecurityAudit('PASSWORD_CHANGED', (int) $user['id']);
            $this->db->commit();
            $this->tokens->cookies(...$session);
            return ['message' => 'Đã đổi mật khẩu và đăng xuất các phiên cũ trên mọi thiết bị.'];
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function closeAccount(array $input): array
    {
        $user = $this->authenticate();
        $this->limit('close-account:' . $user['id'], 5, 3600);
        if ($this->string($input, 'confirmation') !== 'DONG TAI KHOAN') {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Cụm từ xác nhận không chính xác.',
                422,
                ['confirmation' => 'Nhập chính xác DONG TAI KHOAN để xác nhận.']
            );
        }

        $this->db->beginTransaction();
        try {
            $lockedUser = $this->userDao->findById((int) $user['id'], true);
            if (!$lockedUser || $lockedUser['status'] !== 'ACTIVE' || !empty($lockedUser['closed_at'])) {
                throw new ApiException('ACCOUNT_CLOSED', 'Tài khoản đã được đóng hoặc không còn hoạt động.', 409);
            }
            $currentPassword = $this->string($input, 'currentPassword');
            if (strlen($currentPassword) > 72 || str_contains($currentPassword, "\0")
                || !password_verify($currentPassword, (string) $lockedUser['password_hash'])) {
                throw new ApiException(
                    'INVALID_PASSWORD',
                    'Mật khẩu hiện tại không chính xác.',
                    422,
                    ['currentPassword' => 'Mật khẩu hiện tại không chính xác.']
                );
            }
            if (!$this->userDao->closeAccount((int) $user['id'])) {
                throw new ApiException('ACCOUNT_CLOSED', 'Tài khoản đã được đóng hoặc không còn hoạt động.', 409);
            }
            $this->userDao->deleteSessionsByUser((int) $user['id']);
            $this->userDao->recordSecurityAudit('ACCOUNT_CLOSED', (int) $user['id']);
            $this->db->commit();
            $this->tokens->clearCookies();
            return ['message' => 'Tài khoản đã được đóng. Các phiên đã đăng xuất; dữ liệu tài chính được giữ lại theo chính sách lưu trữ.'];
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function requestPasswordReset(array $input): array
    {
        $email = strtolower(trim($this->string($input, 'email')));
        $this->limit('password-reset-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 10, 3600);
        $this->limit('password-reset-email:' . $email, 5, 3600);
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('VALIDATION_ERROR', 'Địa chỉ email không hợp lệ.', 422, ['email' => 'Địa chỉ email không hợp lệ.']);
        }

        // Check service-wide mail configuration for every submitted address.
        // Do not issue an OTP that cannot possibly be delivered.
        $mailer = new EmailService($this->config);
        try {
            $mailer->assertConfigured();
        } catch (\Throwable $error) {
            error_log('WealthFlow OTP mail configuration: ' . $error->getMessage());
            throw new ApiException(
                'OTP_SERVICE_UNAVAILABLE',
                'Dịch vụ gửi mã OTP chưa sẵn sàng. Vui lòng liên hệ quản trị viên kiểm tra cấu hình email.',
                503
            );
        }

        $otp = '';
        $this->db->beginTransaction();
        try {
            $user = $this->userDao->findByEmail($email, true);
            if ($user && $user['status'] !== 'ACTIVE') {
                $user = null;
            }
            $this->userDao->recordSecurityAudit('PASSWORD_RESET_REQUESTED', $user ? (int) $user['id'] : null);
            if ($user) {
                $otp = (string) random_int(100000, 999999);
                $this->query('DELETE FROM password_reset_tokens WHERE user_id = ?', [$user['id']]);
                $this->query(
                    'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, attempts) VALUES (?, ?, ?, 0)',
                    [$user['id'], hash_hmac('sha256', $otp, $this->config->get('APP_KEY')), gmdate('Y-m-d H:i:s', time() + 600)]
                );
            }
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        if ($user) {
            try {
                $mailer->sendPasswordResetOtp($user['email'], $user['full_name'], $otp);
            } catch (\Throwable $error) {
                // An undelivered OTP must not remain valid in the database.
                try {
                    $this->query('DELETE FROM password_reset_tokens WHERE user_id = ?', [$user['id']]);
                } catch (\Throwable $cleanupError) {
                    error_log('WealthFlow OTP cleanup failed; user_id=' . (int) $user['id']
                        . '; category=' . get_class($cleanupError));
                }
                // Keep the HTTP response generic to avoid revealing registered emails.
                // Record the failure for server administrators; never log the OTP.
                error_log('WealthFlow OTP delivery failed; user_id=' . (int) $user['id']
                    . '; category=' . $this->mailFailureCategory($error));
            }
        }

        return ['message' => 'Nếu email này đã đăng ký, mã OTP sẽ được gửi đến hộp thư của bạn. Vui lòng kiểm tra cả thư rác.'];
    }

    public function resetPassword(array $input): array
    {
        $this->limit('password-reset-submit-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 20, 900);
        $email = strtolower(trim($this->string($input, 'email')));
        $otp = $this->string($input, 'otp');
        $password = $this->string($input, 'newPassword');
        $fields = $this->passwordErrors($password);
        if ($password !== $this->string($input, 'passwordConfirmation')) {
            $fields['passwordConfirmation'] = 'Mật khẩu xác nhận không khớp.';
        }
        if ($fields) throw new ApiException('VALIDATION_ERROR', 'Mật khẩu mới chưa hợp lệ.', 422, $fields);
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('VALIDATION_ERROR', 'Địa chỉ email không hợp lệ.', 422, ['email' => 'Địa chỉ email không hợp lệ.']);
        }
        if (!preg_match('/^[0-9]{6}$/D', $otp)) {
            throw new ApiException('INVALID_RESET_OTP', 'Mã OTP không hợp lệ hoặc đã hết hạn.', 400);
        }

        $invalidOtp = false;
        $this->db->beginTransaction();
        try {
            $row = $this->query(
                'SELECT t.user_id, t.token_hash, t.expires_at, t.attempts FROM password_reset_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = ? AND u.status = ? FOR UPDATE',
                [$email, 'ACTIVE']
            )->fetch();
            if (!$row || strtotime($row['expires_at'] . ' UTC') <= time() || (int) $row['attempts'] >= 5) {
                $invalidOtp = true;
            } elseif (!hash_equals($row['token_hash'], hash_hmac('sha256', $otp, $this->config->get('APP_KEY')))) {
                $this->query('UPDATE password_reset_tokens SET attempts = attempts + 1 WHERE user_id = ?', [$row['user_id']]);
                $invalidOtp = true;
            } else {
                $userId = (int) $row['user_id'];
                $this->userDao->updatePassword($userId, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
                $this->userDao->deleteSessionsByUser($userId);
                $this->query('DELETE FROM password_reset_tokens WHERE user_id = ?', [$userId]);
                $this->userDao->recordSecurityAudit('PASSWORD_RESET', $userId);
            }
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        if ($invalidOtp) {
            throw new ApiException('INVALID_RESET_OTP', 'Mã OTP không hợp lệ hoặc đã hết hạn.', 400);
        }

        $this->tokens->clearCookies();
        return ['message' => 'Mật khẩu đã được đặt lại. Vui lòng đăng nhập bằng mật khẩu mới.'];
    }

    /** Return a safe diagnostic category without credentials or email addresses. */
    private function mailFailureCategory(\Throwable $error): string
    {
        $message = strtolower($error->getMessage());
        if (str_contains($message, 'authenticate') || str_contains($message, 'password')) {
            return 'SMTP_AUTH';
        }
        if (str_contains($message, 'connect') || str_contains($message, 'smtp connect')) {
            return 'SMTP_CONNECTION';
        }
        if (str_contains($message, 'tls') || str_contains($message, 'certificate')) {
            return 'SMTP_TLS';
        }
        if (str_contains($message, 'write') || str_contains($message, 'permission')) {
            return 'MAIL_STORAGE';
        }
        return 'MAIL_DELIVERY_ERROR';
    }

    private function newSession(int $id): array
    {
        $sid = bin2hex(random_bytes(32));
        $expiry = time() + TokenService::REFRESH_TTL;
        $refresh = $this->tokens->issue($id, $sid, 'refresh', $expiry);
        $access = $this->tokens->issue($id, $sid, 'access', time() + TokenService::ACCESS_TTL);
        $this->userDao->createSession($sid, $id, hash('sha256', $refresh), gmdate('Y-m-d H:i:s', $expiry));
        return [$access, $refresh, $expiry];
    }

    private function profile(array $user): array
    {
        return ['id' => (int) $user['id'], 'fullName' => $user['full_name'], 'email' => $user['email'], 'baseCurrency' => $user['base_currency'], 'role' => $user['role']];
    }

    private function string(array $input, string $field): string
    {
        return is_string($input[$field] ?? null) ? $input[$field] : '';
    }

    private function passwordErrors(string $password): array
    {
        // Explicit project policy; bcrypt only considers the first 72 bytes.
        return mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")
            || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)
            ? ['password' => 'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường và số; tối đa 72 byte.'] : [];
    }

    private function limit(string $key, int $max, int $seconds): void
    {
        $window = (int) floor(time() / $seconds);
        $bucket = hash_hmac('sha256', $key . ':' . $window, $this->config->get('APP_KEY'));
        $this->query('DELETE FROM auth_rate_limits WHERE expires_at < UTC_TIMESTAMP()');
        $this->query('INSERT INTO auth_rate_limits (bucket, expires_at) VALUES (?, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1', [$bucket, gmdate('Y-m-d H:i:s', ($window + 1) * $seconds)]);
        if ((int) $this->query('SELECT attempts FROM auth_rate_limits WHERE bucket = ?', [$bucket])->fetchColumn() > $max) {
            header('Retry-After: ' . (($window + 1) * $seconds - time()));
            throw new ApiException('RATE_LIMITED', 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau.', 429);
        }
    }

    private function query(string $sql, array $values = []): \PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        return $statement;
    }
}
