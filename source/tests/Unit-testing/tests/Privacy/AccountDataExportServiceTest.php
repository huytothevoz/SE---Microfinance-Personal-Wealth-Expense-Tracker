<?php
declare(strict_types=1);
namespace WealthFlowTest\Privacy;

use RuntimeException;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Privacy\AccountDataExportService;
use WealthFlowTest\Support\DatabaseTestCase;

final class AccountDataExportServiceTest extends DatabaseTestCase
{
    public function testExportContainsSafeProfileAllSectionsAndAudit(): void
    {
        $service = new AccountDataExportService($this->pdo, new AuditLogDAO($this->pdo));
        $result = $service->export(1, '127.0.0.1');
        $payload = json_decode($result['content'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('wealthflow-account-export', $payload['format']);
        self::assertSame(1, $payload['version']);
        self::assertArrayNotHasKey('password_hash', $payload['account']);
        foreach (['wallets','categories','transactions','budgets','loans','goldAssets','goals','notifications'] as $section) {
            self::assertArrayHasKey($section, $payload['data']);
        }
        self::assertGreaterThan(0, $result['rowCount']);
        self::assertMatchesRegularExpression('/wealthflow_account_\d{8}_\d{6}\.json/', $result['filename']);
        self::assertCount(1, (new AuditLogDAO($this->pdo))->getExportLogsByUser(1));
    }

    public function testExportRejectsMissingUser(): void
    {
        $this->expectException(RuntimeException::class);
        (new AccountDataExportService($this->pdo, new AuditLogDAO($this->pdo)))->export(999);
    }
}
