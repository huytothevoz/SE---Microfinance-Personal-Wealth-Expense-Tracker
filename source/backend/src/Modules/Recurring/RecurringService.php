<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Recurring;

use DateTimeImmutable;
use InvalidArgumentException;
use WealthFlow\Modules\Wallets\WalletDAO;

final class RecurringService
{
    private RecurringRuleDAO $recurringDao;
    private WalletDAO $walletDao;

    public function __construct(
        ?RecurringRuleDAO $recurringDao = null,
        ?WalletDAO $walletDao = null
    ) {
        $this->recurringDao = $recurringDao ?? new RecurringRuleDAO();
        $this->walletDao = $walletDao ?? new WalletDAO();
    }

    /**
     * Create a recurring schedule (FR06.01, BR09).
     * @param array{
     *     name: string,
     *     amount: float|string,
     *     currency?: string,
     *     walletId: int,
     *     categoryId?: ?int,
     *     dayOfMonth: int,
     *     executionMode?: string
     * } $dto
     * @return array<string, mixed>
     */
    public function createSchedule(int $userId, array $dto): array
    {
        $name = trim((string) ($dto['name'] ?? ''));
        $amount = $this->positiveAmount($dto['amount'] ?? null);
        $walletId = (int) ($dto['walletId'] ?? 0);
        $categoryId = isset($dto['categoryId']) && $dto['categoryId'] !== '' ? (int) $dto['categoryId'] : null;
        $dayOfMonth = (int) ($dto['dayOfMonth'] ?? 1);
        $executionMode = strtoupper((string) ($dto['executionMode'] ?? 'DRAFT_REMINDER'));

        if ($name === '') {
            throw new InvalidArgumentException('Tên khoản định kỳ không được để trống.');
        }
        if ($dayOfMonth < 1 || $dayOfMonth > 31) {
            throw new InvalidArgumentException('Ngày neo định kỳ trong tháng phải từ 1 đến 31.');
        }
        if (!in_array($executionMode, ['DRAFT_REMINDER', 'AUTO_POST'], true)) {
            $executionMode = 'DRAFT_REMINDER';
        }

        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if (!$wallet || (string) $wallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Ví thanh toán đang hoạt động không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $currency = strtoupper((string) $wallet['currency']);
        if (isset($dto['currency']) && strtoupper(trim((string) $dto['currency'])) !== $currency) {
            throw new InvalidArgumentException('Đơn vị tiền định kỳ phải trùng với đơn vị tiền của ví.');
        }
        if ($categoryId !== null) {
            $category = (new \WealthFlow\Modules\Transactions\CategoryDAO($this->walletDao->getPdo()))
                ->findActiveByIdAndUserAndType($categoryId, $userId, 'EXPENSE');
            if ($category === null) {
                throw new InvalidArgumentException('Danh mục chi tiêu định kỳ không hợp lệ hoặc không thuộc quyền sở hữu.');
            }
        }
        $nextDueDate = self::calculateInitialDueDate($dayOfMonth);

        $id = $this->recurringDao->create([
            'userId' => $userId,
            'walletId' => $walletId,
            'categoryId' => $categoryId,
            'name' => $name,
            'amount' => $amount,
            'currency' => $currency,
            'dayOfMonth' => $dayOfMonth,
            'nextDueDate' => $nextDueDate,
            'executionMode' => $executionMode,
        ]);

        return $this->getSchedule($id, $userId);
    }

    /** @return array<string, mixed> */
    public function getSchedule(int $id, int $userId): array
    {
        $res = $this->recurringDao->findByIdAndUser($id, $userId);
        if (!$res) {
            throw new InvalidArgumentException('Khoản định kỳ không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        return $res;
    }

    /** @return array<int, array<string, mixed>> */
    public function listSchedules(int $userId): array
    {
        return $this->recurringDao->listByUser($userId);
    }

    public function pauseSchedule(int $id, int $userId): bool
    {
        $this->getSchedule($id, $userId);
        return $this->recurringDao->updateStatus($id, $userId, 'PAUSED');
    }

    public function resumeSchedule(int $id, int $userId): bool
    {
        $this->getSchedule($id, $userId);
        return $this->recurringDao->updateStatus($id, $userId, 'ACTIVE');
    }

    public function deleteSchedule(int $id, int $userId): bool
    {
        $this->getSchedule($id, $userId);
        return $this->recurringDao->delete($id, $userId);
    }

    /**
     * Compute next monthly due date respecting anchor day and clamping to month-end (BR09).
     * e.g., anchorDay = 31, currentDueDate = '2026-01-31' -> next is '2026-02-28'
     * then from '2026-02-28' -> next is '2026-03-31'.
     */
    public static function calculateNextMonthlyDueDate(int $anchorDay, string $currentDueDate): string
    {
        $dt = new DateTimeImmutable($currentDueDate);
        $nextMonthDt = $dt->modify('first day of next month');
        $year = (int) $nextMonthDt->format('Y');
        $month = (int) $nextMonthDt->format('m');
        $maxDays = (int) $nextMonthDt->format('t');

        $clampedDay = min($anchorDay, $maxDays);
        return sprintf('%04d-%02d-%02d', $year, $month, $clampedDay);
    }

    /**
     * Calculate initial due date for anchor day relative to today.
     * If anchorDay in current month has not passed today, use current month, else next month.
     */
    public static function calculateInitialDueDate(int $anchorDay, ?string $asOfDate = null): string
    {
        $today = new DateTimeImmutable($asOfDate ?: 'now');
        $curYear = (int) $today->format('Y');
        $curMonth = (int) $today->format('m');
        $curDay = (int) $today->format('d');
        $maxDaysThisMonth = (int) $today->format('t');

        $dueThisMonth = min($anchorDay, $maxDaysThisMonth);

        if ($dueThisMonth >= $curDay) {
            return sprintf('%04d-%02d-%02d', $curYear, $curMonth, $dueThisMonth);
        }

        // Target next month
        $nextMonthDt = $today->modify('first day of next month');
        $nextYear = (int) $nextMonthDt->format('Y');
        $nextMonth = (int) $nextMonthDt->format('m');
        $maxDaysNextMonth = (int) $nextMonthDt->format('t');

        $clampedDay = min($anchorDay, $maxDaysNextMonth);
        return sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $clampedDay);
    }

    private function positiveAmount(mixed $value): string
    {
        $amount = trim((string) ($value ?? ''));
        if (!preg_match('/^\d+(?:\.\d{1,8})?$/D', $amount) || bccomp($amount, '0', 8) !== 1) {
            throw new InvalidArgumentException('Số tiền định kỳ phải là số thập phân hợp lệ lớn hơn 0.');
        }
        return bcadd($amount, '0', 8);
    }
}
