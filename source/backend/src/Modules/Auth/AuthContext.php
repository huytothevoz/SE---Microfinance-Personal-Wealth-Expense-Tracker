<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Auth;

use WealthFlow\Config\Database;
use WealthFlow\Http\Request;

final class AuthContext
{
    private static ?int $overrideUserId = null;

    public static function setUserId(?int $id): void
    {
        self::$overrideUserId = $id;
    }

    public static function getUserId(Request $request): int
    {
        if (self::$overrideUserId !== null) {
            return self::$overrideUserId;
        }

        // 1. Check custom X-User-Id header (for testing and dev integration)
        $headerUserId = $request->getHeader('X-User-Id');
        if ($headerUserId !== null && is_numeric($headerUserId) && (int) $headerUserId > 0) {
            return (int) $headerUserId;
        }

        // 2. Check query parameter ?user_id=
        $queryUserId = $request->getQuery('user_id');
        if ($queryUserId !== null && is_numeric($queryUserId) && (int) $queryUserId > 0) {
            return (int) $queryUserId;
        }

        // 3. Fallback to default active demo user (ID 1)
        return 1;
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
}
