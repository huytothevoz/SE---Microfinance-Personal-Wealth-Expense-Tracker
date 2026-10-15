<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use WealthFlow\Auth\AuthService;
use WealthFlow\Config\Database as ModuleDatabase;
use WealthFlow\Database;
use WealthFlow\Http\ApiException;
use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Http\Router;
use WealthFlow\Modules\Auth\AuthContext;
use WealthFlow\Modules\Budgets\BudgetController;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Recurring\RecurringController;
use WealthFlow\Modules\Transactions\TransactionController;
use WealthFlow\Modules\Wallets\WalletRepository;

try {
    /** @var \WealthFlow\Config $config */
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();

    $route = is_string($_GET['route'] ?? null) ? trim($_GET['route'], '/') : 'health';
    if ($route === '') {
        $route = 'health';
    }
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $authRoutes = [
        'auth/me' => 'GET',
        'auth/register' => 'POST',
        'auth/login' => 'POST',
        'auth/refresh' => 'POST',
        'auth/logout' => 'POST',
        'auth/change-password' => 'POST',
    ];

    if (in_array($route, ['health', 'features', ...array_keys($authRoutes)], true)) {
        $allowedMethod = $route === 'health' || $route === 'features' ? 'GET' : $authRoutes[$route];
        if ($method !== $allowedMethod) {
            header('Allow: ' . $allowedMethod);
            throw new ApiException('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ.', 405);
        }

        if ($method === 'POST') {
            validateSameOriginRequest($config);
        }

        $input = [];
        if ($method === 'POST') {
            if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
                throw new ApiException('INVALID_CONTENT_TYPE', 'Yêu cầu phải dùng JSON.', 415);
            }
            $body = file_get_contents('php://input', false, null, 0, 16385);
            if ($body === false || strlen($body) > 16384) {
                throw new ApiException('BODY_TOO_LARGE', 'Dữ liệu quá lớn.', 413);
            }
            try {
                $decoded = json_decode($body, false, 16, JSON_THROW_ON_ERROR);
                if (!$decoded instanceof stdClass) {
                    throw new JsonException();
                }
                $input = (array) $decoded;
            } catch (JsonException) {
                throw new ApiException('INVALID_JSON', 'Dữ liệu JSON không hợp lệ.', 400);
            }
        }

        $db = Database::connect($config);
        $auth = new AuthService($db, $config);
        $result = match ($route) {
            'health' => [
                'project' => 'WealthFlow',
                'status' => 'ok',
                'databaseConnected' => true,
                'authenticationEnabled' => true,
                'financialPostingEnabled' => true,
            ],
            'features' => [
                'implemented' => ['FR01', 'FR03', 'FR04', 'FR05', 'FR06'],
                'note' => 'Các phân hệ tài chính yêu cầu phiên đăng nhập WealthFlow hợp lệ.',
            ],
            'auth/register' => $auth->register($input),
            'auth/login' => $auth->login($input),
            'auth/me' => $auth->me(),
            'auth/refresh' => $auth->refresh(),
            'auth/logout' => $auth->logout(),
            'auth/change-password' => $auth->changePassword($input),
        };
        JsonResponse::success($result, $route === 'auth/register' ? 201 : 200);
        return;
    }

    $request = new Request();
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        validateSameOriginRequest($config);
        if (in_array($method, ['POST', 'PUT'], true)
            && strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            throw new ApiException('INVALID_CONTENT_TYPE', 'Yêu cầu phải dùng JSON.', 415);
        }
    }

    $router = new Router();
    $router->get('/health', static function (): void {
        JsonResponse::success([
            'project' => 'WealthFlow',
            'status' => 'ok',
            'databaseConnected' => true,
            'databaseDriver' => ModuleDatabase::getDriver(),
            'modulesImplemented' => ['FR03', 'FR04', 'FR05', 'FR06'],
        ]);
    });
    $router->get('/features', static function (): void {
        JsonResponse::success([
            'status' => 'active_implementation',
            'implementedFR' => ['FR01', 'FR03', 'FR04', 'FR05', 'FR06'],
            'implementedUseCases' => ['UC03', 'UC04', 'UC05', 'SYS02'],
            'note' => 'Các phân hệ tài chính yêu cầu phiên đăng nhập WealthFlow hợp lệ.',
        ]);
    });
    $router->get('/wallets', static function (Request $request): void {
        $wallets = (new WalletRepository())->findAllByUser(AuthContext::getUserId($request));
        JsonResponse::success(['wallets' => $wallets, 'count' => count($wallets)]);
    });

    $txController = new TransactionController();
    $router->get('/transactions', [$txController, 'list']);
    $router->post('/transactions', [$txController, 'create']);
    $router->post('/transactions/transfer', [$txController, 'transfer']);
    $router->get('/transactions/{id}', [$txController, 'show']);
    $router->post('/transactions/{id}/reverse', [$txController, 'reverse']);
    $router->get('/categories', [$txController, 'listCategories']);
    $router->post('/categories', [$txController, 'createCategory']);
    $router->get('/exchange-rates', [$txController, 'listRates']);
    $router->post('/exchange-rates', [$txController, 'recordRate']);

    $budgetController = new BudgetController();
    $router->get('/budgets', [$budgetController, 'list']);
    $router->post('/budgets', [$budgetController, 'create']);
    $router->get('/budgets/summary', [$budgetController, 'summary']);
    $router->put('/budgets/{id}', [$budgetController, 'update']);
    $router->delete('/budgets/{id}', [$budgetController, 'delete']);

    $recurringController = new RecurringController();
    $router->get('/recurring', [$recurringController, 'list']);
    $router->post('/recurring', [$recurringController, 'create']);
    $router->post('/recurring/{id}/pause', [$recurringController, 'pause']);
    $router->post('/recurring/{id}/resume', [$recurringController, 'resume']);
    $router->delete('/recurring/{id}', [$recurringController, 'delete']);

    $router->get('/notifications', static function (Request $request): void {
        $notifications = (new NotificationService())->getNotificationsForUser(AuthContext::getUserId($request));
        JsonResponse::success(['notifications' => $notifications, 'count' => count($notifications)]);
    });
    $router->post('/notifications/{id}/read', static function (Request $request, int $id): void {
        (new NotificationService())->markAsRead($id, AuthContext::getUserId($request));
        JsonResponse::success(['message' => 'Đã đánh dấu thông báo đã đọc.']);
    });
    $router->post('/notifications/read-all', static function (Request $request): void {
        (new NotificationService())->markAllAsRead(AuthContext::getUserId($request));
        JsonResponse::success(['message' => 'Đã đánh dấu tất cả thông báo đã đọc.']);
    });

    $router->dispatch($request);
} catch (ApiException $error) {
    JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
} catch (Throwable $error) {
    error_log('WealthFlow API unavailable; type=' . get_class($error));
    JsonResponse::error('SERVICE_UNAVAILABLE', 'Dịch vụ chưa sẵn sàng. Vui lòng kiểm tra cài đặt máy chủ và MySQL.', 503);
}

function validateSameOriginRequest(\WealthFlow\Config $config): void
{
    $url = parse_url($config->get('APP_URL'));
    $expectedOrigin = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;

    if (($_SERVER['HTTP_X_WEALTHFLOW'] ?? '') !== '1'
        || ($origin !== null && !hash_equals($expectedOrigin, $origin))
        || ($fetchSite !== null && !in_array($fetchSite, ['same-origin', 'none'], true))) {
        throw new ApiException('CSRF_REJECTED', 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.', 403);
    }
}
