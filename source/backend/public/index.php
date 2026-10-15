<?php
declare(strict_types=1);

use WealthFlow\Auth\AuthService;
use WealthFlow\Database;
use WealthFlow\Http\ApiException;
use WealthFlow\Http\JsonResponse;

require_once dirname(__DIR__) . '/src/Http/JsonResponse.php';

try {
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();
    $route = is_string($_GET['route'] ?? null) ? trim($_GET['route'], '/') : 'health';
    $routes = ['health' => 'GET', 'features' => 'GET', 'auth/me' => 'GET',
        'auth/register' => 'POST', 'auth/login' => 'POST', 'auth/refresh' => 'POST',
        'auth/logout' => 'POST', 'auth/change-password' => 'POST'];
    if (!isset($routes[$route])) throw new ApiException('NOT_FOUND', 'Không tìm thấy API.', 404);
    if ($_SERVER['REQUEST_METHOD'] !== $routes[$route]) {
        header('Allow: ' . $routes[$route]);
        throw new ApiException('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ.', 405);
    }
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // JSON + custom header require cross-origin preflight; no CORS is allowed.
        $url = parse_url($config->get('APP_URL'));
        $origin = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        if (($_SERVER['HTTP_X_WEALTHFLOW'] ?? '') !== '1'
            || (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== $origin)
            || (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && !in_array($_SERVER['HTTP_SEC_FETCH_SITE'], ['same-origin', 'none'], true))) {
            throw new ApiException('CSRF_REJECTED', 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.', 403);
        }
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            throw new ApiException('INVALID_CONTENT_TYPE', 'Yêu cầu phải dùng JSON.', 415);
        }
        $body = file_get_contents('php://input', false, null, 0, 16385);
        if ($body === false || strlen($body) > 16384) throw new ApiException('BODY_TOO_LARGE', 'Dữ liệu quá lớn.', 413);
        try {
            $decoded = json_decode($body, false, 16, JSON_THROW_ON_ERROR);
            if (!$decoded instanceof stdClass) throw new JsonException();
            $input = (array) $decoded;
        } catch (JsonException) {
            throw new ApiException('INVALID_JSON', 'Dữ liệu JSON không hợp lệ.', 400);
        }
    }
    $db = Database::connect($config);
    $auth = new AuthService($db, $config);
    $result = match ($route) {
        'health' => ['project' => 'WealthFlow', 'status' => 'ok', 'databaseConnected' => true,
            'authenticationEnabled' => true, 'financialPostingEnabled' => false],
        'features' => ['implemented' => ['FR01'], 'note' => 'Các phân hệ tài chính khác vẫn dùng dữ liệu minh họa.'],
        'auth/register' => $auth->register($input),
        'auth/login' => $auth->login($input),
        'auth/me' => $auth->me(),
        'auth/refresh' => $auth->refresh(),
        'auth/logout' => $auth->logout(),
        'auth/change-password' => $auth->changePassword($input),
    };
    JsonResponse::success($result, $route === 'auth/register' ? 201 : 200);
} catch (ApiException $error) {
    JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
} catch (Throwable $error) {
    error_log('WealthFlow API unavailable; type=' . get_class($error));
    JsonResponse::error('SERVICE_UNAVAILABLE', 'Dịch vụ chưa sẵn sàng. Vui lòng kiểm tra cài đặt máy chủ và MySQL.', 503);
}
