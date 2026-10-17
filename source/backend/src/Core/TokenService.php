<?php

declare(strict_types=1);

namespace WealthFlow\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

final class TokenService
{
    public const ACCESS_TTL = 900;
    public const REFRESH_TTL = 604800;

    public function __construct(private readonly Config $config) {}

    public function issue(int $userId, string $session, string $type, int $expires): string
    {
        return JWT::encode([
            'iss' => $this->config->get('APP_URL'),
            'aud' => 'wealthflow',
            'sub' => (string) $userId,
            'sid' => $session,
            'type' => $type,
            'iat' => time(),
            'exp' => $expires,
            'jti' => bin2hex(random_bytes(16)),
        ], $this->config->get('APP_KEY'), 'HS256');
    }

    public function decode(string $token, string $type): object
    {
        try {
            $claims = JWT::decode($token, new Key($this->config->get('APP_KEY'), 'HS256'));
            if (($claims->iss ?? '') !== $this->config->get('APP_URL') || ($claims->aud ?? '') !== 'wealthflow'
                || ($claims->type ?? '') !== $type || !isset($claims->exp, $claims->iat)
                || !is_string($claims->sub ?? null) || !ctype_digit($claims->sub)
                || !is_string($claims->sid ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $claims->sid)) {
                throw new RuntimeException('Invalid claims.');
            }
            return $claims;
        } catch (Throwable) {
            throw new ApiException('UNAUTHENTICATED', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 401);
        }
    }

    public function cookies(string $access, string $refresh, int $refreshExpiry): void
    {
        $this->cookie('wf_access', $access, time() + self::ACCESS_TTL);
        $this->cookie('wf_refresh', $refresh, $refreshExpiry);
    }

    public function clearCookies(): void
    {
        $this->cookie('wf_access', '', time() - 3600);
        $this->cookie('wf_refresh', '', time() - 3600);
    }

    private function cookie(string $name, string $value, int $expiry): void
    {
        setcookie($name, $value, [
            'expires' => $expiry,
            'path' => rtrim((string) parse_url($this->config->get('APP_URL'), PHP_URL_PATH), '/') . '/',
            'secure' => $this->config->secureCookies(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
