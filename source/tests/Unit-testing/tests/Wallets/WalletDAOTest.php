<?php
declare(strict_types=1);
namespace WealthFlowTest\Wallets;

use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class WalletDAOTest extends DatabaseTestCase
{
    public function testWalletCrudLookupBalanceAccountsAndOwnership(): void
    {
        $dao = new WalletDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        self::assertSame('VND', $dao->getUserBaseCurrency(1));
        self::assertTrue($dao->nameExists(1, 'Ví tiền mặt'));
        $id = $dao->create(1, ' Ví thử ', 'CASH', 'VND', '1000');
        self::assertSame($id, (int) $dao->findByIdAndUser($id, 1)['id']);
        self::assertNull($dao->findByIdAndUser($id, 2));
        self::assertNotEmpty($dao->findAllByUser(1));
        self::assertTrue($dao->update($id, 1, 'Ví đổi tên'));
        self::assertTrue($dao->setBalance($id, 1, '2000'));
        self::assertEquals(2500, (float) $dao->adjustBalance($id, '500'));
        self::assertSame($dao->getOrCreateWalletLedgerAccountId(1, $id), $dao->getOrCreateWalletLedgerAccountId(1, $id));
        self::assertSame($dao->getOrCreateOpeningEquityAccountId(1), $dao->getOrCreateOpeningEquityAccountId(1));
        self::assertSame($dao->getOrCreateBalanceAdjustmentAccountId(1), $dao->getOrCreateBalanceAdjustmentAccountId(1));
        self::assertIsArray($dao->getReferenceCounts($id));
        self::assertIsArray($dao->listHistory($id, 1, 10));
        self::assertTrue($dao->archive($id, 1));
        self::assertNotNull($dao->findByIdAndUser($id, 1));
        self::assertTrue($dao->delete($id, 1));
    }

    public function testAdjustBalanceRejectsNegativeResultUnlessAllowed(): void
    {
        $dao = new WalletDAO($this->pdo);
        try { $dao->adjustBalance(1, '-999999999'); self::fail('Must reject negative.'); }
        catch (\RuntimeException) { self::addToAssertionCount(1); }
        self::assertLessThan(0, (float) $dao->adjustBalance(1, '-999999999', true));
    }
}
