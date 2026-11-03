<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class AnalyticsDashboardController
{
    public function __construct(private readonly ?NetWorthAnalyticsService $service = null)
    {
    }

    public function netWorth(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['dashboard' => $this->analytics()->summary($userId, $request->getQuery('month'))]);
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    public function history(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $days = (int) $request->getQuery('days', 90);
            $history = $this->analytics()->history($userId, $days);
            JsonResponse::success(['history' => $history, 'count' => count($history)]);
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    public function expenses(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success([
                'dashboard' => $this->analytics()->expenseDashboard($userId, $request->getQuery('month')),
            ]);
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    private function analytics(): NetWorthAnalyticsService
    {
        return $this->service ?? new NetWorthAnalyticsService();
    }

    private function error(Throwable $error): void
    {
        if ($error instanceof ApiException) {
            JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
            return;
        }
        if ($error instanceof InvalidArgumentException) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
            return;
        }
        error_log('Analytics read failed; type=' . get_class($error) . '; message=' . $error->getMessage());
        JsonResponse::error('ANALYTICS_UNAVAILABLE', 'Chưa thể tổng hợp tài sản ròng. Vui lòng kiểm tra dữ liệu tiền tệ và thử lại.', 503);
    }
}
