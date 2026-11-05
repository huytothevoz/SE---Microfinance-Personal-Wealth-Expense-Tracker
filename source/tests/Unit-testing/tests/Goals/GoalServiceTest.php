<?php
declare(strict_types=1);
namespace WealthFlowTest\Goals;

use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Goals\FinancialGoalDAO;
use WealthFlow\Modules\Goals\GoalService;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class GoalServiceTest extends DatabaseTestCase
{
    private GoalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service=new GoalService(new FinancialGoalDAO($this->pdo),new WalletDAO($this->pdo),new LedgerDAO($this->pdo));
    }

    public function testCreateListGetUpdateCompleteAndCancelLifecycle(): void
    {
        $goal=$this->service->createGoal(1,['name'=>'Emergency','targetAmount'=>'1000','deadline'=>'2030-01-01','priority'=>'high']);
        $id=(int)$goal['id'];
        self::assertCount(1,$this->service->listGoals(1,'active'));
        self::assertSame($id,(int)$this->service->getGoal($id,1)['id']);
        self::assertSame('Updated',$this->service->updateGoal($id,1,['name'=>'Updated'])['name']);
        $deposit=$this->service->allocate($id,1,'DEPOSIT',['amount'=>'1000','walletId'=>1,'allocatedOn'=>'2026-11-03'],'550e8400-e29b-41d4-a716-446655440010');
        self::assertSame('COMPLETED',$deposit['status']);
        self::assertSame('COMPLETED',$this->service->completeGoal($id,1)['status']);
        $this->service->allocate($id,1,'WITHDRAWAL',['amount'=>'1000','walletId'=>1,'allocatedOn'=>'2026-11-03'],'550e8400-e29b-41d4-a716-446655440011');
        $this->service->cancelGoal($id,1);
        self::assertSame('CANCELLED',$this->service->getGoal($id,1)['status']);
    }

    public function testValidationNotFoundAndInvalidIdempotency(): void
    {
        $calls=[
            fn()=>$this->service->listGoals(1,'BAD'),
            fn()=>$this->service->getGoal(999,1),
            fn()=>$this->service->createGoal(1,['name'=>'','targetAmount'=>'0','deadline'=>'bad']),
        ];
        foreach($calls as $call){try{$call();self::fail('Expected error.');}catch(\Throwable){self::addToAssertionCount(1);}}
        $goal=$this->service->createGoal(1,['name'=>'Valid','targetAmount'=>'1000','deadline'=>'2030-01-01']);
        try{$this->service->allocate((int)$goal['id'],1,'DEPOSIT',['amount'=>'1','walletId'=>1],null);self::fail('Expected key error.');}
        catch(ApiException $e){self::assertSame('IDEMPOTENCY_KEY_REQUIRED',$e->errorCode);}
    }
}
