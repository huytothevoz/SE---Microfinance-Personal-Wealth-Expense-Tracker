<?php

declare(strict_types=1);

namespace WealthFlowTest\Transactions;

use InvalidArgumentException;
use RuntimeException;
use WealthFlow\Modules\Transactions\CategoryDAO;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlowTest\Support\DatabaseTestCase;

final class TransactionSupportTest extends DatabaseTestCase
{
    public function testCategoryDaoCompleteLifecycleAndOwnership(): void
    {
        $dao = new CategoryDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        $id = $dao->create(1, '  Học tập ', 'expense', '  Sách ');
        self::assertSame('Học tập', $dao->findByIdAndUser($id, 1)['name']);
        self::assertNull($dao->findByIdAndUser($id, 2));
        self::assertNotNull($dao->findActiveByIdAndUserAndType($id, 1, 'expense'));
        self::assertNull($dao->findActiveByIdAndUserAndType($id, 1, 'income'));
        self::assertContains($id, array_map(static fn(array $r): int => (int) $r['id'], $dao->findAllByUser(1, 'EXPENSE')));
        self::assertTrue($dao->update($id, 1, 'Khóa học', 'Online'));
        self::assertSame('Khóa học', $dao->findByIdAndUser($id, 1)['name']);
        self::assertTrue($dao->archive($id, 1));
        self::assertNull($dao->findActiveByIdAndUserAndType($id, 1, 'EXPENSE'));
    }

    public function testCategoryLedgerAccountIsCreatedOnce(): void
    {
        $dao = new CategoryDAO($this->pdo);
        $first = $dao->getOrCreateCategoryLedgerAccountId(1, 1, 'EXPENSE');
        $second = $dao->getOrCreateCategoryLedgerAccountId(1, 1, 'EXPENSE');
        self::assertSame($first, $second);
    }

    public function testExchangeRateReadConvertRecordUpdateAndList(): void
    {
        $service = new ExchangeRateService($this->pdo);
        self::assertSame('1.00000000', $service->getSnapshotRate(' vnd ', 'VND'));
        self::assertSame('25400.0000000000', $service->getSnapshotRate('usd'));
        self::assertSame('2540000.0000', $service->convertToBase('100', '25400'));
        self::assertNull($service->findLatestRate('GBP', 'VND'));

        $id = $service->recordRate(' gbp ', ' vnd ', '32000', 'TEST', '2026-01-01 00:00:00');
        self::assertSame($id, $service->recordRate('GBP', 'VND', '32100', 'TEST', '2026-01-01 00:00:00'));
        self::assertSame('32100', $service->findLatestRate('GBP', 'VND')['rate']);
        self::assertNotEmpty($service->getAllLatestRates());
        self::assertNull($service->getLastSyncAt());
    }

    public function testExchangeRateRejectsMissingAndInvalidRates(): void
    {
        $service = new ExchangeRateService($this->pdo);
        try {
            $service->getSnapshotRate('GBP');
            self::fail('Missing rate must fail.');
        } catch (RuntimeException $error) {
            self::assertSame(422, $error->getCode());
        }

        foreach (['0', '-1', 'abc'] as $invalid) {
            try {
                $service->recordRate('USD', 'VND', $invalid);
                self::fail('Invalid rate must fail.');
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
