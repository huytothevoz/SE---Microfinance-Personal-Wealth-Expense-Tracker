<?php

declare(strict_types=1);

namespace WealthFlow\Core;

use LogicException;
use WealthFlow\Modules\Auth\AuthService;
use WealthFlow\Modules\Auth\UserDAO;

final class AuthContext
{
    private static ?int $overrideUserId = null;
    private static ?int $authenticatedUserId = null;

    public static function setUserId(?int $id): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Test user overrides are only available in CLI processes.');
        }
        self::$overrideUserId = $id;
        self::$authenticatedUserId = null;
    }

    public static function getUserId(Request $request): int
    {
        if (self::$overrideUserId !== null) {
            return self::$overrideUserId;
        }

        if (self::$authenticatedUserId !== null) {
            return self::$authenticatedUserId;
        }

        /** @var Config $config */
        $config = require dirname(__DIR__, 2) . '/bootstrap.php';
        $user = (new AuthService(Database::connect($config), $config))->authenticate();
        self::$authenticatedUserId = (int) $user['id'];
        return self::$authenticatedUserId;
    }

    /** @return array<string, mixed>|null */
    public static function getUser(int $userId): ?array
    {
        return (new UserDAO())->findById($userId);
    }

    public static function requireAdmin(int $userId): void
    {
        $user = self::getUser($userId);
        if ($user === null || strtoupper((string) $user['role']) !== 'ADMIN') {
            throw new ApiException('FORBIDDEN', 'Bạn không có quyền thực hiện thao tác này.', 403);
        }
    }

    public static function requirePersonalUser(int $userId): int
    {
        $user = self::getUser($userId);
        if ($user === null) {
            throw new ApiException('UNAUTHORIZED', 'Phiên đăng nhập không hợp lệ.', 401);
        }
        if (strtoupper((string) $user['role']) === 'ADMIN') {
            throw new ApiException('FORBIDDEN', 'Tài khoản quản trị không được truy cập sổ sách cá nhân.', 403);
        }
        return $userId;
    }
}
