<?php
declare(strict_types=1);
namespace WealthFlowTest\Goals;

use WealthFlow\Modules\Goals\FinancialGoalDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class GoalModuleTest extends DatabaseTestCase
{
    public function testGoalDaoCompleteLifecycleAllocationAndWalletEarmark(): void
    {
        $dao = new FinancialGoalDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        $id = $dao->create(1, 1, 'Quỹ khẩn cấp', '10000000', '2027-12-31', 'HIGH');
        self::assertSame($id, (int) $dao->findByIdAndUser($id, 1)['id']);
        self::assertNull($dao->findByIdAndUser($id, 2));
        self::assertCount(1, $dao->listByUser(1, 'ACTIVE'));
        self::assertTrue($dao->updateInfo($id, 1, 'Quỹ mới', '12000000', '2028-01-01', 'MEDIUM', 'ACTIVE'));
        self::assertTrue($dao->increaseWalletEarmark(1, 1, '1000'));
        $dao->addAllocation($id, 1, 'DEPOSIT', '1000', '2026-11-03', 'Gửi');
        self::assertCount(1, $dao->listAllocations($id, 1));
        self::assertEquals(1000, (float) $dao->allocatedAmountForWallet($id, 1, 1));
        self::assertCount(1, $dao->allocatedWalletBalances($id, 1));
        self::assertTrue($dao->decreaseWalletEarmark(1, 1, '500'));
        self::assertTrue($dao->updateAmountAndStatus($id, 1, '1000', 'ACTIVE'));
        self::assertTrue($dao->setStatus($id, 1, 'COMPLETED'));
        self::assertSame('COMPLETED', $dao->findByIdAndUser($id, 1)['status']);
    }
}
