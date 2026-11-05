<?php
declare(strict_types=1);
namespace WealthFlowTest\Reports;

use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Reports\ReconciliationService;
use WealthFlow\Modules\Reports\ReportDAO;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class ReconciliationServiceTest extends DatabaseTestCase
{
    public function testReconcileOneAllAndTrialBalance(): void
    {
        $service=new ReconciliationService(
            new ReportDAO($this->pdo),new WalletDAO($this->pdo),
            new AuditLogDAO($this->pdo)
        );
        $one=$service->reconcileWallet(1,1);
        self::assertSame(1,$one['walletId']);
        self::assertArrayHasKey('status',$one);
        self::assertNotEmpty($service->reconcileAllWallets(1));
        $trial=$service->getTrialBalance(1);
        self::assertArrayHasKey('accounts',$trial);
        self::assertArrayHasKey('isBalanced',$trial);
    }

    public function testReconcileRejectsForeignWallet(): void
    {
        $service=new ReconciliationService(new ReportDAO($this->pdo),new WalletDAO($this->pdo),new AuditLogDAO($this->pdo));
        $this->expectException(\WealthFlow\Core\ApiException::class);
        $service->reconcileWallet(2,1);
    }
}
