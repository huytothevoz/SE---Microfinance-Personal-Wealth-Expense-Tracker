<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use WealthFlow\Config\Database;
use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Http\Router;
use WealthFlow\Modules\Auth\AuthContext;
use WealthFlow\Modules\Budgets\BudgetController;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Recurring\RecurringController;
use WealthFlow\Modules\Transactions\TransactionController;
use WealthFlow\Modules\Wallets\WalletRepository;

$request = new Request();
$router = new Router();

// 1. Health & Features Metadata
$router->get('/health', static function (): void {
    JsonResponse::success([
        'project' => 'WealthFlow',
        'phase' => 'Construction Core Implementation',
        'backend' => 'PHP 8.2+',
        'status' => 'operational',
        'databaseConnected' => true,
        'databaseDriver' => Database::getDriver(),
        'modulesImplemented' => [
            'FR03' => 'Core Ledger & Double-Entry Transaction Engine',
            'FR04' => 'Multi-Currency & Snapshot Exchange Rates',
            'FR05' => 'Expense & Multi-Category Budgeting Engine',
            'FR06' => 'Recurring Schedules & Background Task Engine (SYS02)',
        ],
    ]);
});

$router->get('/features', static function (): void {
    JsonResponse::success([
        'status' => 'active_implementation',
        'implementedFR' => ['FR03', 'FR04', 'FR05', 'FR06'],
        'implementedUseCases' => ['UC03', 'UC04', 'UC05', 'SYS02'],
        'businessRules' => ['BR01', 'BR02', 'BR03', 'BR04', 'BR05', 'BR06', 'BR07', 'BR08', 'BR09', 'BR12', 'BR13', 'BR15'],
        'note' => 'Backend modules FR03, FR04, FR05, and FR06 are live with full double-entry ledger, budget thresholds, and recurring scheduling.',
    ]);
});

// 2. Wallets (Supporting FR03 & FR06)
$router->get('/wallets', static function (Request $req): void {
    $userId = AuthContext::getUserId($req);
    $walletRepo = new WalletRepository();
    $wallets = $walletRepo->findAllByUser($userId);
    JsonResponse::success(['wallets' => $wallets, 'count' => count($wallets)]);
});

// 3. Transactions & Ledger Engine (FR03, FR04)
$txController = new TransactionController();
$router->get('/transactions', [$txController, 'list']);
$router->post('/transactions', [$txController, 'create']);
$router->post('/transactions/transfer', [$txController, 'transfer']);
$router->get('/transactions/{id}', [$txController, 'show']);
$router->post('/transactions/{id}/reverse', [$txController, 'reverse']);

// Categories (FR03.05)
$router->get('/categories', [$txController, 'listCategories']);
$router->post('/categories', [$txController, 'createCategory']);

// Exchange Rates (FR04)
$router->get('/exchange-rates', [$txController, 'listRates']);
$router->post('/exchange-rates', [$txController, 'recordRate']);

// 4. Budgets Engine (FR05)
$budgetController = new BudgetController();
$router->get('/budgets', [$budgetController, 'list']);
$router->post('/budgets', [$budgetController, 'create']);
$router->get('/budgets/summary', [$budgetController, 'summary']);
$router->put('/budgets/{id}', [$budgetController, 'update']);
$router->delete('/budgets/{id}', [$budgetController, 'delete']);

// 5. Recurring Schedules & Subscriptions (FR06, SYS02)
$recurringController = new RecurringController();
$router->get('/recurring', [$recurringController, 'list']);
$router->post('/recurring', [$recurringController, 'create']);
$router->post('/recurring/{id}/pause', [$recurringController, 'pause']);
$router->post('/recurring/{id}/resume', [$recurringController, 'resume']);
$router->delete('/recurring/{id}', [$recurringController, 'delete']);
$router->post('/recurring/process-due', [$recurringController, 'processDue']);

// 6. Notifications (Deduplicated alerts from FR05, FR06)
$router->get('/notifications', static function (Request $req): void {
    $userId = AuthContext::getUserId($req);
    $service = new NotificationService();
    $notifications = $service->getNotificationsForUser($userId);
    JsonResponse::success(['notifications' => $notifications, 'count' => count($notifications)]);
});

$router->post('/notifications/{id}/read', static function (Request $req, int $id): void {
    $userId = AuthContext::getUserId($req);
    $service = new NotificationService();
    $service->markAsRead($id, $userId);
    JsonResponse::success(['message' => 'Đã đánh dấu thông báo đã đọc.']);
});

$router->post('/notifications/read-all', static function (Request $req): void {
    $userId = AuthContext::getUserId($req);
    $service = new NotificationService();
    $service->markAllAsRead($userId);
    JsonResponse::success(['message' => 'Đã đánh dấu tất cả thông báo đã đọc.']);
});

// Dispatch request
$router->dispatch($request);
