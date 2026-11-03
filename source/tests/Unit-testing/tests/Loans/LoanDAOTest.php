<?php
declare(strict_types=1);
namespace WealthFlowTest\Loans;

use WealthFlow\Modules\Loans\LoanDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class LoanDAOTest extends DatabaseTestCase
{
    public function testLoanCreateFindListSchedulePaymentAndAccounts(): void
    {
        $dao = new LoanDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        $id = $dao->create([
            'userId'=>1,'walletId'=>1,'title'=>'Vay học phí','partnerName'=>'Ngân hàng',
            'direction'=>'BORROWED','currency'=>'VND','principal'=>'12000000','interestRate'=>'12',
            'termMonths'=>12,'startDate'=>'2026-01-01','nextDueDate'=>'2026-02-01'
        ]);
        self::assertSame($id, (int) $dao->findByIdAndUser($id, 1)['id']);
        self::assertNull($dao->findByIdAndUser($id, 2));
        self::assertNotNull($dao->findByIdAndUserForUpdate($id, 1));
        self::assertCount(1, $dao->listByUser(1, 'BORROWED', 'ACTIVE'));
        $dao->addSchedule($id, 1, '2026-02-01', '1000000', '120000');
        self::assertCount(1, $dao->listSchedules($id, 1));
        self::assertSame('2026-02-01', $dao->nextUnpaidDueDate($id));
        $dao->addPayment($id, null, '500000', '60000', '2026-02-01');
        self::assertCount(1, $dao->listPayments($id, 1));
        $dao->applyPaymentToSchedules($id, '500000', '60000');
        self::assertTrue($dao->updateAfterPayment($id, 1, '11500000', 'ACTIVE', '2026-02-01'));
        self::assertSame($dao->getOrCreateLoanAccount(1, $id, 'BORROWED'), $dao->getOrCreateLoanAccount(1, $id, 'BORROWED'));
        self::assertSame($dao->getOrCreateAccount(1, 'TEST_LOAN', 'LIABILITY'), $dao->getOrCreateAccount(1, 'TEST_LOAN', 'LIABILITY'));
    }
}
