<?php

declare(strict_types=1);

namespace WealthFlowTest\Budgets;

use InvalidArgumentException;
use WealthFlow\Modules\Admin\AdminDAO;
use WealthFlow\Modules\Budgets\BudgetDAO;
use WealthFlow\Modules\Budgets\BudgetService;
use WealthFlow\Modules\Notifications\NotificationDAO;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlow\Modules\Transactions\CategoryDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class BudgetModuleTest extends DatabaseTestCase
{
    private BudgetDAO $dao;
    private BudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new BudgetDAO($this->pdo);
        $this->service = new BudgetService(
            $this->dao,
            new CategoryDAO($this->pdo),
            new NotificationService(new NotificationDAO($this->pdo), $this->pdo),
            new AdminDAO($this->pdo)
        );
    }

    public function testDaoCreateReadUpdateStateListArchiveAndReactivate(): void
    {
        self::assertSame($this->pdo, $this->dao->getPdo());
        $id = $this->dao->create(1, 1, '2026-11', 1000000);
        self::assertSame($id, (int) $this->dao->findByIdAndUser($id, 1)['id']);
        self::assertNotNull($this->dao->findByUserCategoryAndMonth(1, 1, '2026-11'));
        self::assertCount(1, $this->dao->listByMonth(1, '2026-11'));
        self::assertTrue($this->dao->updateLimit($id, 1, '2000000'));
        self::assertTrue($this->dao->syncBudgetState($id, 1, '500000', 'YELLOW_80', true, false));
        self::assertTrue($this->dao->archive($id, 1));
        self::assertNull($this->dao->findByUserCategoryAndMonth(1, 1, '2026-11'));
        self::assertSame($id, $this->dao->create(1, 1, '2026-11', 3000000));
    }

    public function testDaoCalculatesOnlyPostedExpensesInMonth(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, ?, ?, ?, "VND", ?, 1, ?)');
        $stmt->execute(['EXPENSE', 'POSTED', 'Counted', 800000, '2026-11-02']);
        $stmt->execute(['EXPENSE', 'DRAFT', 'Ignored draft', 900000, '2026-11-03']);
        $stmt->execute(['INCOME', 'POSTED', 'Ignored income', 100000, '2026-11-04']);
        self::assertEquals(800000, (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11'));
    }

    public function testServiceCreateListUpdateArchiveAndRejectDuplicate(): void
    {
        $created = $this->service->createBudget(1, 1, '2026-11', 1000000);
        self::assertSame(1000000.0, $created['limitAmount']);
        self::assertCount(1, $this->service->getBudgetsForMonth(1, '2026-11'));
        self::assertSame(2000000.0, $this->service->updateBudget($created['id'], 1, 2000000)['limitAmount']);
        try {
            $this->service->createBudget(1, 1, '2026-11', 1);
            self::fail('Duplicate budget must fail.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        self::assertTrue($this->service->archiveBudget($created['id'], 1));
    }

    public function testServiceValidationAndMissingBudgetPaths(): void
    {
        $invalidCalls = [
            fn() => $this->service->createBudget(1, 1, '11-2026', 1),
            fn() => $this->service->createBudget(1, 1, '2026-11', 0),
            fn() => $this->service->createBudget(1, 999, '2026-11', 1),
            fn() => $this->service->createBudget(1, 6, '2026-11', 1),
            fn() => $this->service->updateBudget(999, 1, 100),
            fn() => $this->service->archiveBudget(999, 1),
        ];
        foreach ($invalidCalls as $call) {
            try { $call(); self::fail('Expected validation exception.'); }
            catch (InvalidArgumentException) { self::addToAssertionCount(1); }
        }
        self::assertNull($this->service->checkBudgetAndNotify(1, 1, '2026-11-01'));
    }

    public function testServiceTriggersYellowThenRedAlertWithoutDuplicates(): void
    {
        $budget = $this->service->createBudget(1, 1, '2026-11', 1000);
        $insert = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Expense", "VND", ?, 1, "2026-11-05")');
        $insert->execute([850]);
        self::assertSame('YELLOW', $this->service->checkBudgetAndNotify(1, 1, '2026-11-05')['alertTriggered']);
        self::assertNull($this->service->checkBudgetAndNotify(1, 1, '2026-11-05')['alertTriggered']);
        $insert->execute([200]);
        self::assertSame('RED', $this->service->checkBudgetAndNotify(1, 1, '2026-11-05')['alertTriggered']);
        self::assertSame('RED', $this->service->getBudgetsForMonth(1, '2026-11')[0]['alertLevel']);
        self::assertSame($budget['id'], $this->service->getBudgetsForMonth(1, '2026-11')[0]['id']);
    }
}
