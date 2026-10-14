<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Budgets;

use InvalidArgumentException;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Transactions\CategoryRepository;

final class BudgetService
{
    private BudgetRepository $repo;
    private CategoryRepository $catRepo;
    private NotificationService $notifService;

    public function __construct(
        ?BudgetRepository $repo = null,
        ?CategoryRepository $catRepo = null,
        ?NotificationService $notifService = null
    ) {
        $this->repo = $repo ?? new BudgetRepository();
        $this->catRepo = $catRepo ?? new CategoryRepository();
        $this->notifService = $notifService ?? new NotificationService();
    }

    /**
     * Create a new category budget for a month (FR05.01, BR07).
     */
    public function createBudget(int $userId, int $categoryId, string $monthYear, float $limitAmount): array
    {
        if ($limitAmount <= 0) {
            throw new InvalidArgumentException('Hạn mức ngân sách phải lớn hơn 0.');
        }

        $category = $this->catRepo->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        if ($category['category_type'] !== 'EXPENSE') {
            throw new InvalidArgumentException('Chỉ có thể thiết lập ngân sách cho danh mục Chi phí (EXPENSE).');
        }

        $existing = $this->repo->findByUserCategoryAndMonth($userId, $categoryId, $monthYear);
        if ($existing !== null) {
            throw new InvalidArgumentException('Ngân sách cho danh mục này trong tháng ' . $monthYear . ' đã tồn tại. Vui lòng chỉnh sửa hạn mức thay vì tạo mới.');
        }

        $budgetId = $this->repo->create($userId, $categoryId, $monthYear, $limitAmount);
        return $this->getBudgetDetail($budgetId, $userId, $monthYear);
    }

    /**
     * Update an existing budget's limit amount.
     */
    public function updateBudget(int $budgetId, int $userId, float $newLimitAmount): array
    {
        if ($newLimitAmount <= 0) {
            throw new InvalidArgumentException('Hạn mức ngân sách phải lớn hơn 0.');
        }

        $budget = $this->repo->findByIdAndUser($budgetId, $userId);
        if (!$budget) {
            throw new InvalidArgumentException('Ngân sách không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $this->repo->updateLimit($budgetId, $userId, $newLimitAmount);
        $month = date('Y-m', strtotime($budget['budget_month']));
        return $this->getBudgetDetail($budgetId, $userId, $month);
    }

    public function archiveBudget(int $budgetId, int $userId): bool
    {
        $budget = $this->repo->findByIdAndUser($budgetId, $userId);
        if (!$budget) {
            throw new InvalidArgumentException('Ngân sách không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        return $this->repo->archive($budgetId, $userId);
    }

    /**
     * List all active budgets for a month with calculated spend, usage %, and alert level (FR05.02).
     * @return array<int, array<string, mixed>>
     */
    public function getBudgetsForMonth(int $userId, ?string $monthYear = null): array
    {
        $month = $monthYear ?: date('Y-m');
        $list = $this->repo->listByMonth($userId, $month);
        $result = [];

        foreach ($list as $b) {
            $catId = (int) $b['category_id'];
            $limit = (float) $b['limit_amount'];
            $spent = $this->repo->getCategoryNetExpenseInMonth($userId, $catId, $month);
            $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;
            $remaining = max(0.0, $limit - $spent);

            $alertLevel = match (true) {
                $usage >= 100 => 'RED',
                $usage >= 80 => 'YELLOW',
                default => 'GREEN',
            };

            $result[] = [
                'id' => (int) $b['id'],
                'categoryId' => $catId,
                'categoryName' => $b['category_name'],
                'month' => $month,
                'limitAmount' => $limit,
                'spentAmount' => $spent,
                'remainingAmount' => $remaining,
                'usagePercent' => $usage,
                'alertLevel' => $alertLevel,
            ];
        }

        return $result;
    }

    /**
     * Check budget limit and dispatch notification upon expense posting (FR05.03, BR08).
     * Rule: Only 1 notification per level/budget/month.
     */
    public function checkBudgetAndNotify(int $userId, int $categoryId, string $occurredOn): ?array
    {
        $month = date('Y-m', strtotime($occurredOn));
        $budget = $this->repo->findByUserCategoryAndMonth($userId, $categoryId, $month);
        if (!$budget) {
            return null;
        }

        $limit = (float) $budget['limit_amount'];
        $spent = $this->repo->getCategoryNetExpenseInMonth($userId, $categoryId, $month);
        $usage = $limit > 0 ? ($spent / $limit) * 100 : 0;
        $categoryName = $budget['category_name'];

        $alertTriggered = null;

        if ($usage >= 100) {
            // RED Alert
            $dedupKey = "BUDGET_RED_{$userId}_{$categoryId}_{$month}";
            $title = "Cảnh báo Đỏ: Ngân sách [{$categoryName}] đã vượt {$usage}%";
            $body = "Chi tiêu thực tế {$spent} đ đã vượt hạn mức {$limit} đ trong tháng {$month}.";
            $sent = $this->notifService->notify($userId, $title, $body, 'BUDGET', $dedupKey);
            if ($sent) {
                $alertTriggered = 'RED';
            }
        } elseif ($usage >= 80) {
            // YELLOW Alert
            $dedupKey = "BUDGET_YELLOW_{$userId}_{$categoryId}_{$month}";
            $title = "Cảnh báo Vàng: Ngân sách [{$categoryName}] đạt {$usage}%";
            $body = "Chi tiêu thực tế {$spent} đ đã chạm ngưỡng cảnh báo 80% hạn mức {$limit} đ trong tháng {$month}.";
            $sent = $this->notifService->notify($userId, $title, $body, 'BUDGET', $dedupKey);
            if ($sent) {
                $alertTriggered = 'YELLOW';
            }
        }

        return [
            'categoryId' => $categoryId,
            'month' => $month,
            'limitAmount' => $limit,
            'spentAmount' => $spent,
            'usagePercent' => round($usage, 1),
            'alertTriggered' => $alertTriggered,
        ];
    }

    private function getBudgetDetail(int $budgetId, int $userId, string $month): array
    {
        $b = $this->repo->findByIdAndUser($budgetId, $userId);
        if (!$b) {
            return [];
        }
        $catId = (int) $b['category_id'];
        $limit = (float) $b['limit_amount'];
        $spent = $this->repo->getCategoryNetExpenseInMonth($userId, $catId, $month);
        $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

        return [
            'id' => (int) $b['id'],
            'categoryId' => $catId,
            'categoryName' => $b['category_name'],
            'month' => $month,
            'limitAmount' => $limit,
            'spentAmount' => $spent,
            'remainingAmount' => max(0.0, $limit - $spent),
            'usagePercent' => $usage,
            'alertLevel' => $usage >= 100 ? 'RED' : ($usage >= 80 ? 'YELLOW' : 'GREEN'),
        ];
    }
}
