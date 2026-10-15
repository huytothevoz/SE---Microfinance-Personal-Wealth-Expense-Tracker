<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Budgets;

use Exception;
use InvalidArgumentException;
use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Modules\Auth\AuthContext;

final class BudgetController
{
    private BudgetService $budgetService;

    public function __construct(?BudgetService $budgetService = null)
    {
        $this->budgetService = $budgetService ?? new BudgetService();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $month = (string) $request->getQuery('month', date('Y-m'));

        $budgets = $this->budgetService->getBudgetsForMonth($userId, $month);
        JsonResponse::success([
            'month' => $month,
            'budgets' => $budgets,
            'count' => count($budgets),
        ]);
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();

        $categoryId = (int) ($body['categoryId'] ?? 0);
        $month = (string) ($body['month'] ?? date('Y-m'));
        $limitAmount = (float) ($body['limitAmount'] ?? 0);

        try {
            $budget = $this->budgetService->createBudget($userId, $categoryId, $month, $limitAmount);
            JsonResponse::success(['budget' => $budget], 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi thiết lập ngân sách: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $limitAmount = (float) ($body['limitAmount'] ?? 0);

        try {
            $budget = $this->budgetService->updateBudget($id, $userId, $limitAmount);
            JsonResponse::success(['budget' => $budget], 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi cập nhật ngân sách: ' . $e->getMessage(), 500);
        }
    }

    public function delete(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);

        try {
            $this->budgetService->archiveBudget($id, $userId);
            JsonResponse::success(['message' => 'Đã lưu trữ ngân sách thành công.'], 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi lưu trữ ngân sách: ' . $e->getMessage(), 500);
        }
    }

    public function summary(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $month = (string) $request->getQuery('month', date('Y-m'));

        $budgets = $this->budgetService->getBudgetsForMonth($userId, $month);

        $totalLimit = 0.0;
        $totalSpent = 0.0;
        $overBudgetCount = 0;
        $warningCount = 0;

        foreach ($budgets as $b) {
            $totalLimit += (float) $b['limitAmount'];
            $totalSpent += (float) $b['spentAmount'];
            if ($b['alertLevel'] === 'RED') {
                $overBudgetCount++;
            } elseif ($b['alertLevel'] === 'YELLOW') {
                $warningCount++;
            }
        }

        $overallUsage = $totalLimit > 0 ? round(($totalSpent / $totalLimit) * 100, 1) : 0;

        JsonResponse::success([
            'month' => $month,
            'totalBudgetLimit' => $totalLimit,
            'totalSpent' => $totalSpent,
            'overallRemaining' => max(0.0, $totalLimit - $totalSpent),
            'overallUsagePercent' => $overallUsage,
            'overBudgetCount' => $overBudgetCount,
            'warningCount' => $warningCount,
            'budgetCount' => count($budgets),
        ]);
    }
}
