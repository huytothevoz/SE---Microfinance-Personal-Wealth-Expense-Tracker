<?php

declare(strict_types=1);

use WealthFlow\Core\ApiException;
use WealthFlow\Core\Config;
use WealthFlow\Core\Database;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;
use WealthFlow\Core\Router;
use WealthFlow\Modules\Auth\AuthController;
use WealthFlow\Modules\Budgets\BudgetController;
use WealthFlow\Modules\Notifications\NotificationController;
use WealthFlow\Modules\Recurring\RecurringController;
use WealthFlow\Modules\Transactions\TransactionController;
use WealthFlow\Modules\Wallets\WalletController;

try {
    /** @var Config $config */
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();

    $request = new Request();
    $method = $request->getMethod();

    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        validateSameOriginRequest($config);
        if (in_array($method, ['POST', 'PUT'], true)
            && strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            throw new ApiException('INVALID_CONTENT_TYPE', 'Yêu cầu phải dùng JSON.', 415);
        }
    }

    $router = new Router();

    // 1. Health & Features
    $router->get('/health', static function (): void {
        Database::getConnection()->query('SELECT 1');
        JsonResponse::success([
            'project' => 'WealthFlow',
            'status' => 'ok',
            'databaseConnected' => true,
            'authenticationEnabled' => true,
            'financialPostingEnabled' => true,
        ]);
    });
    $router->get('/features', static function (): void {
        JsonResponse::success([
            'integrated' => ['FR01', 'FR02', 'FR03', 'FR04', 'FR05', 'FR06'],
            'integrationVerifiedWithMysql' => false,
            'note' => 'Đã nối giao diện và backend nhưng cần kiểm thử nghiệm thu MySQL/SMTP thực tế trước khi sử dụng dữ liệu tài chính.',
        ]);
    });

    // 2. Auth Routes
    $authController = new AuthController(null, $config);
    $router->get('/auth/me', [$authController, 'me']);
    $router->post('/auth/register', [$authController, 'register']);
    $router->post('/auth/login', [$authController, 'login']);
    $router->post('/auth/refresh', [$authController, 'refresh']);
    $router->post('/auth/logout', [$authController, 'logout']);
    $router->post('/auth/change-password', [$authController, 'changePassword']);
    $router->post('/auth/password/forgot', [$authController, 'forgotPassword']);
    $router->post('/auth/password/reset', [$authController, 'resetPassword']);

    // 3. Wallets
    $walletController = new WalletController();
    $router->get('/wallets', [$walletController, 'list']);
    $router->post('/wallets', [$walletController, 'create']);
    $router->get('/wallets/{id}', [$walletController, 'show']);
    $router->get('/wallets/{id}/history', [$walletController, 'history']);
    $router->put('/wallets/{id}', [$walletController, 'update']);
    $router->post('/wallets/{id}/adjustments', [$walletController, 'adjust']);
    $router->delete('/wallets/{id}', [$walletController, 'delete']);
    $router->post('/wallets/{id}/archive', [$walletController, 'archive']);

    // 4. Transactions & Ledger
    $txController = new TransactionController();
    $router->get('/transactions', [$txController, 'list']);
    $router->post('/transactions', [$txController, 'create']);
    $router->post('/transactions/transfer', [$txController, 'transfer']);
    $router->get('/transactions/{id}', [$txController, 'show']);
    $router->post('/transactions/{id}/reverse', [$txController, 'reverse']);
    $router->get('/categories', [$txController, 'listCategories']);
    $router->post('/categories', [$txController, 'createCategory']);
    $router->put('/categories/{id}', [$txController, 'updateCategory']);
    $router->delete('/categories/{id}', [$txController, 'archiveCategory']);
    $router->get('/exchange-rates', [$txController, 'listRates']);
    $router->post('/exchange-rates/sync', [$txController, 'syncRates']);
    $router->post('/exchange-rates', [$txController, 'recordRate']);

    // 5. Budgets
    $budgetController = new BudgetController();
    $router->get('/budgets', [$budgetController, 'list']);
    $router->post('/budgets', [$budgetController, 'create']);
    $router->get('/budgets/summary', [$budgetController, 'summary']);
    $router->put('/budgets/{id}', [$budgetController, 'update']);
    $router->delete('/budgets/{id}', [$budgetController, 'delete']);

    // 6. Recurring
    $recurringController = new RecurringController();
    $router->get('/recurring', [$recurringController, 'list']);
    $router->post('/recurring', [$recurringController, 'create']);
    $router->post('/recurring/{id}/pause', [$recurringController, 'pause']);
    $router->post('/recurring/{id}/resume', [$recurringController, 'resume']);
    $router->delete('/recurring/{id}', [$recurringController, 'delete']);

    // 7. Notifications
    $notifController = new NotificationController();
    $router->get('/notifications', [$notifController, 'list']);
    $router->post('/notifications/{id}/read', [$notifController, 'markRead']);
    $router->post('/notifications/read-all', [$notifController, 'markAllRead']);

    $router->dispatch($request);
} catch (ApiException $error) {
    JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
} catch (Throwable $error) {
    error_log('WealthFlow API unavailable; type=' . get_class($error) . '; message=' . $error->getMessage());
    JsonResponse::error('SERVICE_UNAVAILABLE', 'Dịch vụ chưa sẵn sàng. Vui lòng kiểm tra cài đặt máy chủ và MySQL.', 503);
}

function validateSameOriginRequest(Config $config): void
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
