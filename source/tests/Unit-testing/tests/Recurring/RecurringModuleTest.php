<?php

declare(strict_types=1);

namespace WealthFlowTest\Recurring;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use WealthFlow\Modules\Recurring\RecurringRuleDAO;
use WealthFlow\Modules\Recurring\RecurringService;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class RecurringModuleTest extends DatabaseTestCase
{
    private RecurringRuleDAO $dao;
    private RecurringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new RecurringRuleDAO($this->pdo);
        $this->service = new RecurringService($this->dao, new WalletDAO($this->pdo));
    }

    public function testGetPdoReturnsInjectedConnection(): void
    {
        self::assertSame($this->pdo, $this->dao->getPdo());
    }

    public function testDaoCreateFindListUpdateAndDeleteLifecycle(): void
    {
        $id = $this->createSchedule();
        self::assertGreaterThan(0, $id);
        self::assertSame('Tiền nhà', $this->dao->findByIdAndUser($id, 1)['name']);
        self::assertNull($this->dao->findByIdAndUser($id, 999));
        self::assertCount(1, $this->dao->listByUser(1));

        self::assertTrue($this->dao->updateStatus($id, 1, 'paused'));
        self::assertSame('PAUSED', $this->dao->findByIdAndUser($id, 1)['status']);
        $this->dao->updateNextDueDate($id, '2027-02-28');
        self::assertSame('2027-02-28', $this->dao->findByIdAndUser($id, 1)['next_due_date']);
        self::assertTrue($this->dao->delete($id, 1));
        self::assertNull($this->dao->findByIdAndUser($id, 1));
    }

    public function testFindDueSchedulesReturnsOnlyActiveDueRowsWithActiveWallet(): void
    {
        $due = $this->createSchedule(['nextDueDate' => '2026-01-01']);
        $future = $this->createSchedule(['name' => 'Future', 'nextDueDate' => '2027-01-01']);
        $paused = $this->createSchedule(['name' => 'Paused', 'nextDueDate' => '2026-01-01']);
        $this->dao->updateStatus($paused, 1, 'PAUSED');

        $rows = $this->dao->findDueSchedules('2026-12-31');
        self::assertSame([$due], array_map(static fn(array $row): int => (int) $row['id'], $rows));
        self::assertNotSame($future, $due);
    }

    public function testExecutionFunctionsDetectAndRecordUniqueDueDate(): void
    {
        $schedule = $this->createSchedule();
        self::assertFalse($this->dao->hasExecutionForDue($schedule, '2026-12-01'));
        $id = $this->dao->recordExecution($schedule, '2026-12-01', null, 'success');
        self::assertGreaterThan(0, $id);
        self::assertTrue($this->dao->hasExecutionForDue($schedule, '2026-12-01'));
    }

    public function testServiceCreatesScheduleWithNormalizedDefaults(): void
    {
        $created = $this->service->createSchedule(1, [
            'name' => '  Internet ',
            'amount' => '250000',
            'walletId' => 1,
            'categoryId' => 1,
            'dayOfMonth' => 31,
            'executionMode' => 'invalid-mode',
        ]);

        self::assertSame('Internet', $created['name']);
        self::assertSame('250000', (string) $created['amount']);
        self::assertSame('DRAFT_REMINDER', $created['execution_mode']);
        self::assertSame('VND', $created['currency']);
    }

    public static function invalidScheduleCases(): array
    {
        return [
            'zero amount' => [['name' => 'A', 'amount' => '0', 'walletId' => 1, 'dayOfMonth' => 1]],
            'invalid decimal' => [['name' => 'A', 'amount' => '1.123456789', 'walletId' => 1, 'dayOfMonth' => 1]],
            'blank name' => [['name' => ' ', 'amount' => '1', 'walletId' => 1, 'dayOfMonth' => 1]],
            'day below range' => [['name' => 'A', 'amount' => '1', 'walletId' => 1, 'dayOfMonth' => 0]],
            'day above range' => [['name' => 'A', 'amount' => '1', 'walletId' => 1, 'dayOfMonth' => 32]],
            'missing wallet' => [['name' => 'A', 'amount' => '1', 'walletId' => 999, 'dayOfMonth' => 1]],
            'currency mismatch' => [['name' => 'A', 'amount' => '1', 'walletId' => 1, 'currency' => 'USD', 'dayOfMonth' => 1]],
            'income category' => [['name' => 'A', 'amount' => '1', 'walletId' => 1, 'categoryId' => 6, 'dayOfMonth' => 1]],
        ];
    }

    #[DataProvider('invalidScheduleCases')]
    public function testServiceRejectsInvalidSchedule(array $dto): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createSchedule(1, $dto);
    }

    public function testServiceGetListPauseResumeAndDeleteLifecycle(): void
    {
        $id = $this->createSchedule();
        self::assertSame($id, (int) $this->service->getSchedule($id, 1)['id']);
        self::assertCount(1, $this->service->listSchedules(1));
        self::assertTrue($this->service->pauseSchedule($id, 1));
        self::assertSame('PAUSED', $this->service->getSchedule($id, 1)['status']);
        self::assertTrue($this->service->resumeSchedule($id, 1));
        self::assertSame('ACTIVE', $this->service->getSchedule($id, 1)['status']);
        self::assertTrue($this->service->deleteSchedule($id, 1));
    }

    public function testServiceRejectsAccessToMissingOrOtherUsersSchedule(): void
    {
        $id = $this->createSchedule();
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSchedule($id, 999);
    }

    public static function nextDueDateCases(): array
    {
        return [
            [31, '2026-01-31', '2026-02-28'],
            [31, '2028-01-31', '2028-02-29'],
            [31, '2026-02-28', '2026-03-31'],
            [5, '2026-12-05', '2027-01-05'],
        ];
    }

    #[DataProvider('nextDueDateCases')]
    public function testCalculateNextMonthlyDueDate(int $anchor, string $current, string $expected): void
    {
        self::assertSame($expected, RecurringService::calculateNextMonthlyDueDate($anchor, $current));
    }

    public static function initialDueDateCases(): array
    {
        return [
            [20, '2026-05-10', '2026-05-20'],
            [10, '2026-05-10', '2026-05-10'],
            [5, '2026-05-10', '2026-06-05'],
            [31, '2028-02-10', '2028-02-29'],
        ];
    }

    #[DataProvider('initialDueDateCases')]
    public function testCalculateInitialDueDate(int $anchor, string $today, string $expected): void
    {
        self::assertSame($expected, RecurringService::calculateInitialDueDate($anchor, $today));
    }

    private function createSchedule(array $overrides = []): int
    {
        return $this->dao->create(array_replace([
            'userId' => 1,
            'walletId' => 1,
            'categoryId' => 1,
            'name' => 'Tiền nhà',
            'amount' => '1000000',
            'currency' => 'VND',
            'dayOfMonth' => 1,
            'nextDueDate' => '2026-12-01',
            'executionMode' => 'DRAFT_REMINDER',
        ], $overrides));
    }
}
