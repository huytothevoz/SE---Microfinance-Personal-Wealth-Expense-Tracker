<?php

declare(strict_types=1);

namespace WealthFlow\Core;

final class JsonResponse
{
    /** @param array<string, mixed> $data */
    public static function success(array $data, int $status = 200): void
    {
        self::send(['success' => true, 'data' => $data, 'error' => null, 'requestId' => bin2hex(random_bytes(8))], $status);
    }

    public static function error(string $code, string $message, int $status, array $fields = []): void
    {
        self::send(['success' => false, 'data' => null, 'error' => ['code' => $code, 'message' => $message, 'fieldErrors' => (object) $fields], 'requestId' => bin2hex(random_bytes(8))], $status);
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
