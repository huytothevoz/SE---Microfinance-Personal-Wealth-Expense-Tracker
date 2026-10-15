<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Auth;

use WealthFlow\Auth\AuthService;
use WealthFlow\Config;
use WealthFlow\Config\Database;
use WealthFlow\Database as ApplicationDatabase;
use WealthFlow\Http\ApiException;
use WealthFlow\Http\Request;

final class AuthContext
{
    private static ?int $overrideUserId = null;
    private static ?int $authenticatedUserId = null;

    public static function setUserId(?int $id): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new \LogicException('Test user overrides are only available in CLI processes.');
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
        $config = require dirname(__DIR__, 3) . '/bootstrap.php';
        $user = (new AuthService(ApplicationDatabase::connect($config), $config))->authenticate();
        self::$authenticatedUserId = (int) $user['id'];
        return self::$authenticatedUserId;
    }

    /** @return array<string, mixed>|null */
    public static function getUser(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, full_name, email, role, status, base_currency FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function requireAdmin(int $userId): void
    {
        $user = self::getUser($userId);
        if ($user === null || strtoupper((string) $user['role']) !== 'ADMIN') {
            throw new ApiException('FORBIDDEN', 'Bạn không có quyền thực hiện thao tác này.', 403);
        }
    }
}
