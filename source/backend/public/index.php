<?php
/**
 * Demo API only: read-only health and feature catalog.
 * No authentication, financial transaction or database connection is implemented.
 * DO NOT expose additional private modules through this entry point.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Http/JsonResponse.php';

use WealthFlow\Http\JsonResponse;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    JsonResponse::error('METHOD_NOT_ALLOWED', 'Phương thức chưa được hỗ trợ.', 405);
    return;
}
$route = (string) ($_GET['route'] ?? 'health');
if ($route === 'health') {
    JsonResponse::success([
        'project' => 'WealthFlow',
        'phase' => 'Construction UI Prototype',
        'backend' => 'PHP',
        'status' => 'demo_read_only',
        'databaseConnected' => false,
        'authenticationEnabled' => false,
        'financialPostingEnabled' => false,
    ]);
    return;
}
if ($route === 'features') {
    JsonResponse::success([
        'status' => 'ui_only',
        'fr' => array_map(static fn(int $n): string => sprintf('FR%02d', $n), range(1, 15)),
        'useCases' => array_map(static fn(int $n): string => sprintf('UC%02d', $n), range(1, 13)),
        'note' => 'Danh mục yêu cầu tham chiếu SRS, không phải danh sách API đã hiện thực.',
    ]);
    return;
}
JsonResponse::error('NOT_FOUND', 'API này chưa được triển khai.', 404);
