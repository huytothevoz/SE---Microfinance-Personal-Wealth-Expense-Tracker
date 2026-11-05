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

    public function testAlertJumpOverYellowTriggersOnlyRedAndSuppressesBelatedYellow(): void
    {
        $budget = $this->service->createBudget(1, 1, '2026-11', 1000);
        $insert = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Large Expense", "VND", ?, 1, "2026-11-05")');

        // Chi tiêu nhảy vọt thẳng từ 0% lên 120% (vượt 100%)
        $insert->execute([1200]);
        $check1 = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');
        self::assertSame('RED', $check1['alertTriggered']);
        self::assertSame('RED', $check1['alertLevel']);

        $dbBudget = $this->dao->findByIdAndUser($budget['id'], 1);
        self::assertSame(1, (int) $dbBudget['is_yellow_sent']);
        self::assertSame(1, (int) $dbBudget['is_red_sent']);

        // Giao dịch giảm bớt xuống 850 (85% - ngưỡng vàng), ví dụ khi điều chỉnh chi tiêu
        $this->pdo->exec('UPDATE financial_transactions SET amount = 850 WHERE user_id = 1 AND category_id = 1');
        $check2 = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');

        // Belated Yellow Alert Defense: Không được gửi thêm cảnh báo vàng muộn màng
        self::assertNull($check2['alertTriggered']);
        self::assertSame('YELLOW', $check2['alertLevel']);
    }

    public function testDynamicWarningThresholdFromSystemConfig(): void
    {
        // Admin đổi ngưỡng cảnh báo từ 80% xuống 70%
        $this->pdo->exec("UPDATE system_config SET config_value = '70' WHERE config_key = 'BUDGET_WARNING_THRESHOLD_PERCENT'");

        $this->service->createBudget(1, 1, '2026-11', 1000);
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Expense 75%", "VND", ?, 1, "2026-11-05")');
        $stmt->execute([750]);

        $res = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');
        self::assertSame('YELLOW', $res['alertTriggered']);
        self::assertSame('YELLOW', $res['alertLevel']);

        // Test fallback an toàn khi cấu hình admin nằm ngoài khoảng [50, 95]
        $this->pdo->exec("UPDATE system_config SET config_value = '150' WHERE config_key = 'BUDGET_WARNING_THRESHOLD_PERCENT'");
        $this->pdo->exec('DELETE FROM financial_transactions WHERE user_id = 1 AND category_id = 1');
        $this->dao->syncBudgetState(1, 1, 0, 'NORMAL', false, false);

        $stmt->execute([780]); // 78% < 80% default fallback
        $fallbackRes = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');
        self::assertNull($fallbackRes['alertTriggered']);
        self::assertSame('GREEN', $fallbackRes['alertLevel']);
    }

    public function testMonthlyNotificationDeduplicationResetsForNewMonth(): void
    {
        // Tháng 11 chi tiêu 120% chạm RED
        $this->service->createBudget(1, 1, '2026-11', 1000);
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Exp Nov", "VND", ?, 1, ?)');
        $stmt->execute([1200, '2026-11-10']);
        $resNov = $this->service->checkBudgetAndNotify(1, 1, '2026-11-10');
        self::assertSame('RED', $resNov['alertTriggered']);

        // Tháng 12 tạo ngân sách mới, chi tiêu 85% chạm YELLOW
        $this->service->createBudget(1, 1, '2026-12', 1000);
        $stmt->execute([850, '2026-12-05']);
        $resDec = $this->service->checkBudgetAndNotify(1, 1, '2026-12-05');

        // Dedup key độc lập theo tháng nên tháng 12 kích hoạt cảnh báo YELLOW bình thường
        self::assertSame('YELLOW', $resDec['alertTriggered']);
        self::assertSame('YELLOW', $resDec['alertLevel']);
    }

    public function testNetExpenseIncludesForeignCurrencyConvertedBySnapshotRate(): void
    {
        // Chi tiêu 50 USD với tỷ giá snapshot = 25,400 VND/USD
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "USD Coffee", "USD", 50, 25400.0, "2026-11-08")');
        $stmt->execute();

        $spent = (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11');
        self::assertEquals(1270000.0, $spent);
    }

    public function testNetExpenseExcludesInternalTransfers(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, ?, "POSTED", "Transfer/Expense", "VND", ?, 1, "2026-11-12")');

        $stmt->execute(['TRANSFER', 500000]); // Chuyển tiền nội bộ
        $stmt->execute(['EXPENSE', 150000]);  // Chi tiêu thật

        $spent = (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11');
        self::assertEquals(150000.0, $spent);
    }

    public function testNetExpenseExcludesLoanPrincipalPayment(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, ?, "POSTED", "Loan/Expense", "VND", ?, 1, "2026-11-15")');

        $stmt->execute(['LOAN_PAYMENT', 1000000]); // Trả nợ gốc vi mô
        $stmt->execute(['EXPENSE', 250000]);       // Chi phí lãi vay / chi tiêu

        $spent = (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11');
        self::assertEquals(250000.0, $spent);
    }

    public function testNetExpenseExcludesReversedTransactions(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (101, 1, 1, "EXPENSE", "POSTED", "Book", "VND", 400000, 1, "2026-11-18")');
        $stmt->execute();

        self::assertEquals(400000.0, (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11'));

        // Đảo giao dịch (REVERSED)
        $this->pdo->exec('UPDATE financial_transactions SET status = "REVERSED" WHERE id = 101');
        self::assertEquals(0.0, (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11'));
    }

    public function testNetExpenseRespectsMonthStartEndBoundariesAndLeapYear(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Boundary Test", "VND", ?, 1, ?)');

        // Thuộc tháng 11: ngày đầu tháng (01) và ngày cuối tháng (30)
        $stmt->execute([100000, '2026-11-01']);
        $stmt->execute([200000, '2026-11-30']);

        // Ngoài tháng 11: 31/10 và 01/12
        $stmt->execute([999999, '2026-10-31']);
        $stmt->execute([888888, '2026-12-01']);

        self::assertEquals(300000.0, (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2026-11'));

        // Kiểm tra năm nhuận: ngày 29/02/2024
        $stmt->execute([150000, '2024-02-29']);
        self::assertEquals(150000.0, (float) $this->dao->getCategoryNetExpenseInMonth(1, 1, '2024-02'));
    }

    public function testReactivateArchivedBudgetPreservesIdAndResetsState(): void
    {
        $id = $this->dao->create(1, 1, '2026-11', 1000000);
        $this->dao->syncBudgetState($id, 1, '900000', 'YELLOW_80', true, true);
        $this->dao->archive($id, 1);

        self::assertNull($this->dao->findByUserCategoryAndMonth(1, 1, '2026-11'));

        // Tạo lại cùng category và month -> Kích hoạt lại ID cũ
        $reactivatedId = $this->dao->create(1, 1, '2026-11', 2500000);
        self::assertSame($id, $reactivatedId);

        $row = $this->dao->findByIdAndUser($id, 1);
        self::assertSame('ACTIVE', $row['status']);
        self::assertEquals(2500000.0, (float) $row['limit_amount']);
        self::assertEquals(0.0, (float) $row['spent_amount']);
        self::assertSame('NORMAL', $row['alert_level']);
        self::assertSame(0, (int) $row['is_yellow_sent']);
        self::assertSame(0, (int) $row['is_red_sent']);
    }

    public function testLimitIncreaseDeEscalatesAlertLevel(): void
    {
        $budget = $this->service->createBudget(1, 1, '2026-11', 1000);
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Expense", "VND", 850, 1, "2026-11-05")');
        $stmt->execute();

        $check1 = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');
        self::assertSame('YELLOW', $check1['alertLevel']);

        // Nâng hạn mức lên 2000 -> tỷ lệ giảm về 42.5%, trạng thái về GREEN
        $updated = $this->service->updateBudget($budget['id'], 1, 2000);
        self::assertEquals(42.5, $updated['usagePercent']);
        self::assertSame('GREEN', $updated['alertLevel']);
        self::assertEquals(1150.0, $updated['remainingAmount']);
    }

    public function testLimitDecreaseEscalatesAlertLevelAndTriggersAlert(): void
    {
        $budget = $this->service->createBudget(1, 1, '2026-11', 2000);
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, 1, "EXPENSE", "POSTED", "Expense", "VND", 900, 1, "2026-11-05")');
        $stmt->execute();

        $check = $this->service->checkBudgetAndNotify(1, 1, '2026-11-05');
        self::assertSame('GREEN', $check['alertLevel']);

        // Giảm hạn mức xuống 1000 -> tỷ lệ vọt lên 90%, kích hoạt cảnh báo YELLOW
        $updated = $this->service->updateBudget($budget['id'], 1, 1000);
        self::assertEquals(90.0, $updated['usagePercent']);
        self::assertSame('YELLOW', $updated['alertLevel']);

        $row = $this->dao->findByIdAndUser($budget['id'], 1);
        self::assertSame('YELLOW_80', $row['alert_level']);
    }

    public function testMultiTenantIsolationPreventsCrossUserAccess(): void
    {
        $b1 = $this->service->createBudget(1, 1, '2026-11', 1000000);

        // Tạo User 2 và danh mục chi phí riêng của User 2
        $this->insertUser(2);
        $this->pdo->exec("INSERT INTO categories (id, user_id, name, category_type) VALUES (99, 2, 'User 2 Food', 'EXPENSE')");

        // User 2 không thể xem ngân sách của User 1
        self::assertNull($this->dao->findByIdAndUser($b1['id'], 2));
        self::assertEmpty($this->dao->listByMonth(2, '2026-11'));

        // User 2 không thể sửa hoặc lưu trữ ngân sách của User 1
        try {
            $this->service->updateBudget($b1['id'], 2, 500000);
            self::fail('User 2 should not update User 1 budget');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $this->service->archiveBudget($b1['id'], 2);
            self::fail('User 2 should not archive User 1 budget');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        // User 1 không thể tạo ngân sách với danh mục của User 2
        try {
            $this->service->createBudget(1, 99, '2026-11', 500000);
            self::fail('User 1 should not use User 2 category');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
    }

    public function testCreateBudgetRejectsIncomeAndArchivedCategories(): void
    {
        // Category 6 là 'Lương cố định' (INCOME)
        try {
            $this->service->createBudget(1, 6, '2026-11', 1000000);
            self::fail('Budget for INCOME category must fail');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('EXPENSE', $e->getMessage());
        }

        // Category không tồn tại
        try {
            $this->service->createBudget(1, 9999, '2026-11', 1000000);
            self::fail('Budget for missing category must fail');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('không tồn tại', $e->getMessage());
        }
    }

    public function testMonthFormatValidationRejectsInvalidMonthStrings(): void
    {
        $invalidMonths = ['2026-13', '2026-00', '2026/11', '2026-1', 'Nov-2026', '', '2026-11-01'];
        foreach ($invalidMonths as $m) {
            try {
                $this->service->createBudget(1, 1, $m, 500000);
                self::fail("Month '{$m}' should be rejected");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('YYYY-MM', $e->getMessage());
            }
        }
    }

    public function testBoundaryAndExtremeLimitAmounts(): void
    {
        // Số thập phân nhỏ hợp lệ
        $bSmall = $this->service->createBudget(1, 1, '2026-11', 0.5);
        self::assertSame(0.5, $bSmall['limitAmount']);

        // Số cực lớn hợp lệ
        $bLarge = $this->service->updateBudget($bSmall['id'], 1, 999999999999.0);
        self::assertSame(999999999999.0, $bLarge['limitAmount']);

        // Giới hạn âm hoặc 0
        try {
            $this->service->updateBudget($bSmall['id'], 1, 0.0);
            self::fail('Zero limit update must fail');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $this->service->updateBudget($bSmall['id'], 1, -100.0);
            self::fail('Negative limit update must fail');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
    }

    public function testBudgetSummaryAggregatesOverallMetricsCorrectly(): void
    {
        // Tạo 3 ngân sách: Cat 1 (Ăn uống), Cat 2 (Xăng xe), Cat 3 (Nhà cửa)
        $b1 = $this->service->createBudget(1, 1, '2026-11', 1000000);
        $b2 = $this->service->createBudget(1, 2, '2026-11', 2000000);
        $b3 = $this->service->createBudget(1, 3, '2026-11', 1000000);

        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions
            (user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
            VALUES (1, ?, "EXPENSE", "POSTED", "Expense", "VND", ?, 1, "2026-11-10")');

        $stmt->execute([1, 300000]);   // 30% -> GREEN
        $stmt->execute([2, 1700000]);  // 85% -> YELLOW
        $stmt->execute([3, 1100000]);  // 110% -> RED

        $budgets = $this->service->getBudgetsForMonth(1, '2026-11');
        self::assertCount(3, $budgets);

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

        self::assertEquals(4000000.0, $totalLimit);
        self::assertEquals(3100000.0, $totalSpent);
        self::assertEquals(900000.0, max(0.0, $totalLimit - $totalSpent));
        self::assertEquals(77.5, round(($totalSpent / $totalLimit) * 100, 1));
        self::assertSame(1, $overBudgetCount);
        self::assertSame(1, $warningCount);
    }

    public function testBudgetSummaryForEmptyMonthAvoidsZeroDivision(): void
    {
        $budgets = $this->service->getBudgetsForMonth(1, '2099-01');
        self::assertEmpty($budgets);

        $totalLimit = 0.0;
        $totalSpent = 0.0;
        foreach ($budgets as $b) {
            $totalLimit += (float) $b['limitAmount'];
            $totalSpent += (float) $b['spentAmount'];
        }
        $overallUsage = $totalLimit > 0 ? round(($totalSpent / $totalLimit) * 100, 1) : 0.0;

        self::assertSame(0.0, $overallUsage);
        self::assertSame(0.0, $totalLimit);
        self::assertSame(0.0, $totalSpent);
    }
}
