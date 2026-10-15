<?php
declare(strict_types=1);

namespace WealthFlow\Auth;

use PDO;
use WealthFlow\Config;
use WealthFlow\Http\ApiException;
use WealthFlow\Mail\EmailService;
use WealthFlow\Mail\WelcomeEmailQueue;

final class AuthService
{
    private TokenService $tokens;

    public function __construct(private readonly PDO $db, private readonly Config $config)
    {
        $this->tokens = new TokenService($config);
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
            $this->query('INSERT INTO users (full_name, email, password_hash, base_currency) VALUES (?, ?, ?, ?)', [$name, $email, $hash, $currency]);
            $id = (int) $this->db->lastInsertId();
            $this->query('INSERT INTO categories (user_id, name, category_type) SELECT ?, name, category_type FROM default_categories WHERE is_active = 1', [$id]);
            $this->query('INSERT INTO registration_emails (user_id) VALUES (?)', [$id]);
            $this->audit('REGISTER', $id);
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
            $user = $this->query('SELECT * FROM users WHERE email = ? FOR UPDATE', [$email])->fetch();
            // Fixed valid cost-12 hash keeps unknown-email verification comparable.
            $hash = $user['password_hash'] ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
            if (!$valid || !$user) {
                $this->audit('LOGIN_FAILED');
                $this->db->commit();
                throw new ApiException('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không chính xác.', 401);
            }
            if ($user['status'] !== 'ACTIVE') {
                $this->audit('LOGIN_LOCKED', (int) $user['id']);
                $this->db->commit();
                throw new ApiException('ACCOUNT_LOCKED', 'Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên.', 403);
            }
            $session = $this->newSession((int) $user['id']);
            $this->audit('LOGIN', (int) $user['id']);
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
        $row = $this->query('SELECT u.*, s.id AS session_id, s.refresh_hash, s.expires_at FROM auth_sessions s JOIN users u ON u.id = s.user_id WHERE s.id = ? AND u.id = ? AND s.expires_at > UTC_TIMESTAMP() AND u.status = ?', [$claims->sid, $claims->sub, 'ACTIVE'])->fetch();
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
            $this->query('SELECT id FROM users WHERE id = ? FOR UPDATE', [$user['id']]);
            $user = $this->authenticate(true);
            $expiry = strtotime($user['expires_at'] . ' UTC');
            $refresh = $this->tokens->issue((int) $user['id'], $user['session_id'], 'refresh', $expiry);
            $access = $this->tokens->issue((int) $user['id'], $user['session_id'], 'access', time() + TokenService::ACCESS_TTL);
            $this->query('UPDATE auth_sessions SET refresh_hash = ? WHERE id = ?', [hash('sha256', $refresh), $user['session_id']]);
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
            $this->query('SELECT id FROM users WHERE id = ? FOR UPDATE', [$user['id']]);
            $this->query('DELETE FROM auth_sessions WHERE id = ?', [$user['session_id']]);
            $this->audit('LOGOUT', (int) $user['id']);
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
            $this->query('SELECT id FROM users WHERE id = ? FOR UPDATE', [$user['id']]);
            $user = $this->authenticate();
            $current = $this->string($input, 'currentPassword');
            if (strlen($current) > 72 || str_contains($current, "\0") || !password_verify($current, $user['password_hash'])) {
                throw new ApiException('INVALID_PASSWORD', 'Mật khẩu hiện tại không chính xác.', 422, ['currentPassword' => 'Mật khẩu hiện tại không chính xác.']);
            }
            $this->query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $user['id']]);
            $this->query('DELETE FROM auth_sessions WHERE user_id = ?', [$user['id']]);
            $session = $this->newSession((int) $user['id']);
            $this->audit('PASSWORD_CHANGED', (int) $user['id']);
            $this->db->commit();
            $this->tokens->cookies(...$session);
            return ['message' => 'Đã đổi mật khẩu và đăng xuất các phiên cũ trên mọi thiết bị.'];
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function newSession(int $id): array
    {
        $sid = bin2hex(random_bytes(32));
        $expiry = time() + TokenService::REFRESH_TTL;
        $refresh = $this->tokens->issue($id, $sid, 'refresh', $expiry);
        $access = $this->tokens->issue($id, $sid, 'access', time() + TokenService::ACCESS_TTL);
        $this->query('INSERT INTO auth_sessions (id, user_id, refresh_hash, expires_at) VALUES (?, ?, ?, ?)', [$sid, $id, hash('sha256', $refresh), gmdate('Y-m-d H:i:s', $expiry)]);
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

    private function audit(string $event, ?int $user = null): void
    {
        $this->query('INSERT INTO security_audit_logs (user_id, event) VALUES (?, ?)', [$user, $event]);
    }

    private function query(string $sql, array $values = []): \PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        return $statement;
    }
}
