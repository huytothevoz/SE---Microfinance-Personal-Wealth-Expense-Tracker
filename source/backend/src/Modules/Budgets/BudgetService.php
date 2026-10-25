<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Budgets;

use InvalidArgumentException;
use WealthFlow\Modules\Admin\AdminDAO;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Transactions\CategoryDAO;

final class BudgetService
{
    private BudgetDAO $budgetDao;
    private CategoryDAO $catDao;
    private NotificationService $notifService;
    private AdminDAO $adminDao;

    public function __construct(
        ?BudgetDAO $budgetDao = null,
        ?CategoryDAO $catDao = null,
        ?NotificationService $notifService = null,
        ?AdminDAO $adminDao = null
    ) {
        $this->budgetDao = $budgetDao ?? new BudgetDAO();
        $this->catDao = $catDao ?? new CategoryDAO();
        $this->notifService = $notifService ?? new NotificationService();
        $this->adminDao = $adminDao ?? new AdminDAO();
    }

    /**
     * Create a new category budget for a month (FR05.01, BR07).
     */
    public function createBudget(int $userId, int $categoryId, string $monthYear, float $limitAmount): array
    {
        $monthYear = trim($monthYear);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthYear)) {
            throw new InvalidArgumentException('Kỳ ngân sách không hợp lệ (định dạng chuẩn: YYYY-MM).');
        }
        if ($limitAmount <= 0) {
            throw new InvalidArgumentException('Hạn mức ngân sách phải lớn hơn 0.');
        }

        $category = $this->catDao->findByIdAndUser($categoryId, $userId);
        if (!$category) {
            throw new InvalidArgumentException('Danh mục không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        if ($category['category_type'] !== 'EXPENSE') {
            throw new InvalidArgumentException('Chỉ có thể thiết lập ngân sách cho danh mục Chi phí (EXPENSE).');
        }

        $existing = $this->budgetDao->findByUserCategoryAndMonth($userId, $categoryId, $monthYear);
        if ($existing !== null) {
            throw new InvalidArgumentException('Ngân sách cho danh mục này trong tháng ' . $monthYear . ' đã tồn tại. Vui lòng chỉnh sửa hạn mức thay vì tạo mới.');
        }

        $budgetId = $this->budgetDao->create($userId, $categoryId, $monthYear, $limitAmount);
        $this->checkBudgetAndNotify($userId, $categoryId, $monthYear . '-01');
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

        $budget = $this->budgetDao->findByIdAndUser($budgetId, $userId);
        if (!$budget || ($budget['status'] ?? 'ACTIVE') !== 'ACTIVE') {
            throw new InvalidArgumentException('Ngân sách không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $this->budgetDao->updateLimit($budgetId, $userId, $newLimitAmount);
        $month = date('Y-m', strtotime($budget['budget_month']));
        $this->checkBudgetAndNotify($userId, (int) $budget['category_id'], $month . '-01');
        return $this->getBudgetDetail($budgetId, $userId, $month);
    }

    public function archiveBudget(int $budgetId, int $userId): bool
    {
        $budget = $this->budgetDao->findByIdAndUser($budgetId, $userId);
        if (!$budget) {
            throw new InvalidArgumentException('Ngân sách không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        return $this->budgetDao->archive($budgetId, $userId);
    }

    /**
     * List all active budgets for a month with calculated spend, usage %, and alert level (FR05.02).
     * @return array<int, array<string, mixed>>
     */
    public function getBudgetsForMonth(int $userId, ?string $monthYear = null): array
    {
        $month = $monthYear && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim($monthYear))
            ? trim($monthYear)
            : date('Y-m');
        $list = $this->budgetDao->listByMonth($userId, $month);
        $result = [];
        $warningThreshold = $this->getWarningThreshold();

        foreach ($list as $b) {
            $catId = (int) $b['category_id'];
            $limit = (float) $b['limit_amount'];
            $spent = (float) $this->budgetDao->getCategoryNetExpenseInMonth($userId, $catId, $month);
            $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
            $remaining = max(0.0, $limit - $spent);

            $alertLevel = match (true) {
                $usage >= 100 => 'RED',
                $usage >= $warningThreshold => 'YELLOW',
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
     * If usage jumps from <80% directly to >=100%, only RED is sent and YELLOW is marked as sent (BR08).
     */
    public function checkBudgetAndNotify(int $userId, int $categoryId, string $occurredOn): ?array
    {
        $month = date('Y-m', strtotime($occurredOn));
        $budget = $this->budgetDao->findByUserCategoryAndMonth($userId, $categoryId, $month);
        if (!$budget) {
            return null;
        }

        $budgetId = (int) $budget['id'];
        $limit = (float) $budget['limit_amount'];
        $spent = (float) $this->budgetDao->getCategoryNetExpenseInMonth($userId, $categoryId, $month);
        $usage = $limit > 0 ? ($spent / $limit) * 100 : 0.0;
        $usageRounded = round($usage, 1);
        $categoryName = $budget['category_name'];

        $yellowSent = !empty($budget['is_yellow_sent']);
        $redSent = !empty($budget['is_red_sent']);
        $alertTriggered = null;
        $warningThreshold = $this->getWarningThreshold();

        if ($usage >= 100) {
            // RED Alert (BR08: also marks Yellow as activated so reversal to 80-99% won't emit a belated Yellow alert)
            $dedupKey = "BUDGET_RED_{$userId}_{$categoryId}_{$month}";
            $title = "Cảnh báo Đỏ: Ngân sách [{$categoryName}] đã vượt {$usageRounded}%";
            $body = 'Chi tiêu thực tế ' . number_format($spent, 0, ',', '.') . ' đ đã vượt hạn mức ' . number_format($limit, 0, ',', '.') . " đ trong tháng {$month}.";
            $sent = $this->notifService->notify($userId, $title, $body, 'BUDGET', $dedupKey, 'CRITICAL');
            if ($sent) {
                $alertTriggered = 'RED';
            }
            $redSent = true;
            $yellowSent = true;
        } elseif ($usage >= $warningThreshold) {
            // YELLOW Alert — only if neither Yellow nor Red was previously triggered in this period
            if (!$yellowSent && !$redSent) {
                $dedupKey = "BUDGET_YELLOW_{$userId}_{$categoryId}_{$month}";
                $title = "Cảnh báo Vàng: Ngân sách [{$categoryName}] đạt {$usageRounded}%";
                $body = 'Chi tiêu thực tế ' . number_format($spent, 0, ',', '.') . ' đ đã chạm ngưỡng cảnh báo ' . (int) $warningThreshold . '% hạn mức ' . number_format($limit, 0, ',', '.') . " đ trong tháng {$month}.";
                $sent = $this->notifService->notify($userId, $title, $body, 'BUDGET', $dedupKey, 'WARNING');
                if ($sent) {
                    $alertTriggered = 'YELLOW';
                }
            }
            $yellowSent = true;
        }

        $dbAlertLevel = $usage >= 100 ? 'RED_100' : ($usage >= $warningThreshold ? 'YELLOW_80' : 'NORMAL');
        $this->budgetDao->syncBudgetState($budgetId, $userId, $spent, $dbAlertLevel, $yellowSent, $redSent);

        return [
            'categoryId' => $categoryId,
            'month' => $month,
            'limitAmount' => $limit,
            'spentAmount' => $spent,
            'usagePercent' => $usageRounded,
            'alertLevel' => $usage >= 100 ? 'RED' : ($usage >= $warningThreshold ? 'YELLOW' : 'GREEN'),
            'alertTriggered' => $alertTriggered,
        ];
    }

    private function getBudgetDetail(int $budgetId, int $userId, string $month): array
    {
        $b = $this->budgetDao->findByIdAndUser($budgetId, $userId);
        if (!$b) {
            return [];
        }
        $catId = (int) $b['category_id'];
        $limit = (float) $b['limit_amount'];
        $spent = (float) $this->budgetDao->getCategoryNetExpenseInMonth($userId, $catId, $month);
        $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
        $warningThreshold = $this->getWarningThreshold();

        return [
            'id' => (int) $b['id'],
            'categoryId' => $catId,
            'categoryName' => $b['category_name'],
            'month' => $month,
            'limitAmount' => $limit,
            'spentAmount' => $spent,
            'remainingAmount' => max(0.0, $limit - $spent),
            'usagePercent' => $usage,
            'alertLevel' => $usage >= 100 ? 'RED' : ($usage >= $warningThreshold ? 'YELLOW' : 'GREEN'),
        ];
    }

    private function getWarningThreshold(): float
    {
        try {
            $val = $this->adminDao->getSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT', '80');
            $num = (float) $val;
            return ($num >= 50 && $num <= 95) ? $num : 80.0;
        } catch (\Throwable) {
            return 80.0;
        }
    }
}
