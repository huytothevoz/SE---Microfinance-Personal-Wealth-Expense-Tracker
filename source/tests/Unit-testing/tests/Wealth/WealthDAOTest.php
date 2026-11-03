<?php
declare(strict_types=1);
namespace WealthFlowTest\Wealth;

use WealthFlow\Modules\Wealth\GoldHoldingDAO;
use WealthFlow\Modules\Wealth\NetWorthSnapshotDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class WealthDAOTest extends DatabaseTestCase
{
    public function testGoldHoldingLifecycleTransactionsAndAccounts(): void
    {
        $dao = new GoldHoldingDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        $id = $dao->create(1, 'SJC', '2', '15000000', '7500000', '8000000', '2026-11-03');
        self::assertSame($id, (int) $dao->findByIdAndUser($id, 1)['id']);
        self::assertNotNull($dao->findHeldByType(1, 'SJC'));
        self::assertCount(1, $dao->listByUser(1));
        self::assertTrue($dao->updateHolding($id, 1, '3', '23000000', '7666666.67', '8100000', '2026-11-04'));
        self::assertTrue($dao->revalue($id, 1, '8200000', '2026-11-05'));
        $tx = $dao->addTransaction($id, 1, 'BUY', '1', '8000000', '8000000', 1, '2026-11-03');
        self::assertGreaterThan(0, $tx);
        self::assertCount(1, $dao->listTransactions($id, 1));
        self::assertSame($dao->getOrCreateLedgerAccountId(1, $id), $dao->getOrCreateLedgerAccountId(1, $id));
        self::assertSame($dao->getOrCreateAccount(1, 'GOLD_TEST', 'ASSET'), $dao->getOrCreateAccount(1, 'GOLD_TEST', 'ASSET'));
        self::assertTrue($dao->liquidate($id, 1, '8300000', '2026-11-06'));
        self::assertCount(1, $dao->listByUser(1, true));
        self::assertCount(0, $dao->listByUser(1, false));
    }

    public function testSnapshotAnalyticsQueriesReturnArrays(): void
    {
        $dao = new NetWorthSnapshotDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        $this->pdo->exec("INSERT INTO net_worth_snapshots
            (user_id,snapshot_date,total_assets,total_liabilities,net_worth) VALUES
            (1,'2026-11-01',1000,200,800)");
        self::assertCount(1, $dao->history(1, 30));
        self::assertIsArray($dao->cashFlowForMonth(1, '2026-11'));
        self::assertIsArray($dao->expenseByCategory(1, '2026-11'));
        self::assertIsArray($dao->cashFlowTrend(1, '2026-11', 6));
    }
}
