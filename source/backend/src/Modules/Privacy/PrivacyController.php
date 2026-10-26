<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Privacy;

use WealthFlow\Core\AuthContext;
use WealthFlow\Core\Database;
use WealthFlow\Core\Request;

final class PrivacyController
{
    public function exportAllData(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $export = (new AccountDataExportService(Database::getConnection()))->export(
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        echo $export['content'];
    }
}
