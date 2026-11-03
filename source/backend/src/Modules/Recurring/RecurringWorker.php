<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Recurring;

use Exception;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Transactions\LedgerService;

final class RecurringWorker
{
    private RecurringRuleDAO $recurringDao;
    private LedgerService $ledgerService;
    private LedgerDAO $ledgerDao;
    private NotificationService $notifService;

    public function __construct(
        ?RecurringRuleDAO $recurringDao = null,
        ?LedgerService $ledgerService = null,
        ?LedgerDAO $ledgerDao = null,
        ?NotificationService $notifService = null
    ) {
        $this->recurringDao = $recurringDao ?? new RecurringRuleDAO();
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO();
        $this->notifService = $notifService ?? new NotificationService();
    }

    /**
     * Process due recurring schedules as of a specific date (SYS02, BR09).
     * @return array{processed: int, autoPosted: int, draftReminders: int, failedInsufficientFunds: int}
     */
    public function processDueSchedules(?string $asOfDate = null): array
    {
        $pdo = $this->ledgerDao->getPdo();
        $locked = false;
        if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $lock = $pdo->prepare('SELECT GET_LOCK(?, 0)');
            $lock->execute(['wealthflow_recurring_worker']);
            if ((int) $lock->fetchColumn() !== 1) {
                throw new \RuntimeException('A recurring-schedule worker is already running.');
            }
            $locked = true;
        }

        try {
            return $this->processDueSchedulesLocked($asOfDate);
        } finally {
            if ($locked) {
                $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute(['wealthflow_recurring_worker']);
            }
        }
    }

    /** @return array{processed: int, autoPosted: int, draftReminders: int, failedInsufficientFunds: int} */
    private function processDueSchedulesLocked(?string $asOfDate): array
    {
        $pdo = $this->ledgerDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $summary = $this->processDueSchedulesBatch($asOfDate);
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $summary;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /** @return array{processed: int, autoPosted: int, draftReminders: int, failedInsufficientFunds: int} */
    private function processDueSchedulesBatch(?string $asOfDate): array
    {
        $date = $asOfDate ?: date('Y-m-d');
        $dueList = $this->recurringDao->findDueSchedules($date);

        $summary = [
            'processed' => 0,
            'autoPosted' => 0,
            'draftReminders' => 0,
            'failedInsufficientFunds' => 0,
        ];

        foreach ($dueList as $schedule) {
            $scheduleId = (int) $schedule['id'];
            $userId = (int) $schedule['user_id'];
            $dueDate = $schedule['next_due_date'];
            $anchorDay = (int) $schedule['day_of_month'];
            $amount = (string) $schedule['amount'];
            $walletId = (int) $schedule['wallet_id'];
            $categoryId = !empty($schedule['category_id']) ? (int) $schedule['category_id'] : null;
            $name = $schedule['name'];
            $mode = $schedule['execution_mode'];
            $currency = $schedule['currency'];
            $walletBalance = (string) $schedule['wallet_balance'];

            // Skip if already executed for this due date (Idempotent per due cycle)
            if ($this->recurringDao->hasExecutionForDue($scheduleId, $dueDate)) {
                // Advance next due date if stuck
                $nextDueDate = RecurringService::calculateNextMonthlyDueDate($anchorDay, $dueDate);
                $this->recurringDao->updateNextDueDate($scheduleId, $nextDueDate);
                continue;
            }

            $summary['processed']++;

            if ($mode === 'AUTO_POST') {
                if (bccomp($walletBalance, $amount, 8) >= 0 && $categoryId !== null) {
                    $pdo = $this->ledgerDao->getPdo();
                    $pdo->exec('SAVEPOINT recurring_post');
                    $savepointActive = true;
                    try {
                        $txResult = $this->ledgerService->postExpense($userId, [
                            'walletId' => $walletId,
                            'categoryId' => $categoryId,
                            'amount' => $amount,
                            'currency' => $currency,
                            'description' => '[Tự động định kỳ] ' . $name,
                            'occurredOn' => $dueDate,
                        ]);
                        $pdo->exec('RELEASE SAVEPOINT recurring_post');
                        $savepointActive = false;

                        $txId = (int) $txResult['transactionId'];
                        $this->recurringDao->recordExecution($scheduleId, $dueDate, $txId, 'COMPLETED');
                        $summary['autoPosted']++;

                        $budgetService = new \WealthFlow\Modules\Budgets\BudgetService(
                            new \WealthFlow\Modules\Budgets\BudgetDAO($pdo),
                            new \WealthFlow\Modules\Transactions\CategoryDAO($pdo),
                            $this->notifService
                        );
                        $budgetService->checkBudgetAndNotify($userId, $categoryId, $dueDate);

                        $this->notifService->notify(
                            $userId,
                            "Tự động thanh toán: {$name}",
                            "Đã tự động trích " . $this->formatAmount($amount) . " {$currency} từ ví {$schedule['wallet_name']} cho khoản định kỳ.",
                            'RECURRING',
                            "RECURRING_POSTED_{$scheduleId}_{$dueDate}"
                        );
                    } catch (Exception $e) {
                        if (!$savepointActive) {
                            throw $e;
                        }
                        $pdo->exec('ROLLBACK TO SAVEPOINT recurring_post');
                        $pdo->exec('RELEASE SAVEPOINT recurring_post');
                        $this->recurringDao->recordExecution($scheduleId, $dueDate, null, 'FAILED');
                        $summary['failedInsufficientFunds']++;
                    }
                } else {
                    // Insufficient funds or no category
                    $txId = $this->ledgerDao->createTransaction([
                        'userId' => $userId,
                        'categoryId' => $categoryId,
                        'type' => 'EXPENSE',
                        'status' => 'DRAFT',
                        'description' => '[Bản nháp định kỳ - Thiếu số dư] ' . $name,
                        'currency' => $currency,
                        'amount' => $amount,
                        'fxRateSnapshot' => 1.0,
                        'occurredOn' => $dueDate,
                        'postedAt' => null,
                        'originalTransactionId' => null,
                    ]);

                    $this->recurringDao->recordExecution($scheduleId, $dueDate, $txId, 'PENDING');
                    $summary['failedInsufficientFunds']++;

                    $this->notifService->notify(
                        $userId,
                        "Cảnh báo ví thiếu tiền: {$name}",
                        "Ví {$schedule['wallet_name']} không đủ số dư để tự động trích " . $this->formatAmount($amount) . " {$currency}. Hệ thống đã tạo bản nháp chờ bạn nạp tiền và xác nhận.",
                        'RECURRING',
                        "RECURRING_INSUFFICIENT_{$scheduleId}_{$dueDate}"
                    );
                }
            } else {
                // DRAFT_REMINDER mode (default)
                $txId = $this->ledgerDao->createTransaction([
                    'userId' => $userId,
                    'categoryId' => $categoryId,
                    'type' => 'EXPENSE',
                    'status' => 'DRAFT',
                    'description' => '[Bản nháp định kỳ] ' . $name,
                    'currency' => $currency,
                    'amount' => $amount,
                    'fxRateSnapshot' => 1.0,
                    'occurredOn' => $dueDate,
                    'postedAt' => null,
                    'originalTransactionId' => null,
                ]);

                $this->recurringDao->recordExecution($scheduleId, $dueDate, $txId, 'PENDING');
                $summary['draftReminders']++;

                $this->notifService->notify(
                    $userId,
                    "Nhắc nhở định kỳ: {$name}",
                    "Khoản định kỳ " . $this->formatAmount($amount) . " {$currency} đến hạn hôm nay. Bản nháp đã được tạo sẵn để bạn xác nhận ghi sổ.",
                    'RECURRING',
                    "RECURRING_REMINDER_{$scheduleId}_{$dueDate}"
                );
            }

            // Advance next due date respecting anchor day and clamping
            $nextDueDate = RecurringService::calculateNextMonthlyDueDate($anchorDay, $dueDate);
            $this->recurringDao->updateNextDueDate($scheduleId, $nextDueDate);
        }

        return $summary;
    }

    private function formatAmount(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $formatted = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', ltrim($whole, '0') ?: '0') ?? $whole;
        $fraction = rtrim($fraction, '0');
        return $fraction === '' ? $formatted : $formatted . ',' . $fraction;
    }
}
