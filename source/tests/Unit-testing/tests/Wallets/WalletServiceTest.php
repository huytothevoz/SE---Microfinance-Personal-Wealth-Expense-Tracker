<?php
declare(strict_types=1);
namespace WealthFlowTest\Wallets;

use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlow\Modules\Wallets\WalletService;
use WealthFlowTest\Support\DatabaseTestCase;

final class WalletServiceTest extends DatabaseTestCase
{
    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService(
            new WalletDAO($this->pdo),
            new LedgerDAO($this->pdo),
            new ExchangeRateService($this->pdo)
        );
    }

    public function testCreateGetListUpdateHistoryArchiveDeleteLifecycle(): void
    {
        $wallet = $this->service->createWallet(1, ['name'=>' Test Zero ','walletType'=>'cash','currency'=>'VND','initialBalance'=>'0']);
        $id=(int)$wallet['id'];
        self::assertSame($id,(int)$this->service->getWallet($id,1)['id']);
        self::assertNotEmpty($this->service->getWallets(1));
        self::assertIsArray($this->service->getWalletHistory($id,1));
        self::assertSame('Renamed',$this->service->updateWallet($id,1,['name'=>'Renamed'])['name']);
        self::assertTrue($this->service->archiveWallet($id,1));
        self::assertTrue($this->service->deleteWallet($id,1));
    }

    public function testCreateWalletOpeningBalanceAndIdempotency(): void
    {
        $key='550e8400-e29b-41d4-a716-446655440000';
        $dto=['name'=>'Opening','walletType'=>'BANK','currency'=>'VND','initialBalance'=>'1000'];
        $first=$this->service->createWallet(1,$dto,$key);
        $second=$this->service->createWallet(1,$dto,$key);
        self::assertSame($first['id'],$second['id']);
        self::assertEquals(1000,(float)$first['cached_balance']);
        self::assertCount(1,(new LedgerDAO($this->pdo))->listTransactions(1,['type'=>'OTHER']));
    }

    public function testAdjustWalletIncreaseDecreaseAndReplay(): void
    {
        $wallet=$this->service->createWallet(1,['name'=>'Adjust','walletType'=>'CASH','initialBalance'=>'1000']);
        $key='550e8400-e29b-41d4-a716-446655440001';
        $first=$this->service->adjustWallet((int)$wallet['id'],1,['direction'=>'INCREASE','amount'=>'500','note'=>'Correction'],$key);
        $replay=$this->service->adjustWallet((int)$wallet['id'],1,['direction'=>'INCREASE','amount'=>'500','note'=>'Correction'],$key);
        self::assertSame($first['transactionId'],$replay['transactionId']);
        self::assertEquals(1500,(float)$first['wallet']['cached_balance']);
        $decrease=$this->service->adjustWallet((int)$wallet['id'],1,['direction'=>'DECREASE','amount'=>'200']);
        self::assertEquals(1300,(float)$decrease['wallet']['cached_balance']);
    }

    public function testValidationOwnershipDuplicateAndArchiveBalanceRules(): void
    {
        $calls=[
            fn()=>$this->service->createWallet(1,['name'=>'','walletType'=>'CASH']),
            fn()=>$this->service->createWallet(1,['name'=>'Bad','walletType'=>'OTHER']),
            fn()=>$this->service->createWallet(1,['name'=>'Bad FX','walletType'=>'CASH','currency'=>'GBP']),
            fn()=>$this->service->getWallet(999,1),
        ];
        foreach($calls as $call){try{$call();self::fail('Expected validation error.');}catch(\Throwable){self::addToAssertionCount(1);}}
        $wallet=$this->service->createWallet(1,['name'=>'Duplicate','walletType'=>'CASH']);
        try{$this->service->createWallet(1,['name'=>'Duplicate','walletType'=>'CASH']);self::fail('Expected duplicate.');}
        catch(ApiException $e){self::assertSame('DUPLICATE_WALLET_NAME',$e->errorCode);}
        $funded=$this->service->createWallet(1,['name'=>'Funded','walletType'=>'CASH','initialBalance'=>'1']);
        try{$this->service->archiveWallet((int)$funded['id'],1);self::fail('Expected nonzero rejection.');}
        catch(\InvalidArgumentException){self::addToAssertionCount(1);}
        self::assertIsArray($wallet);
    }
}
