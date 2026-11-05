<?php
declare(strict_types=1);
namespace WealthFlowTest\Reports;

use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Reports\ExportService;
use WealthFlow\Modules\Reports\ReportDAO;
use WealthFlowTest\Support\DatabaseTestCase;

final class ReportModuleTest extends DatabaseTestCase
{
    public function testCsvSanitizationCoversNullFormulaQuotesCommaAndNewline(): void
    {
        $service = new ExportService(new ReportDAO($this->pdo), new AuditLogDAO($this->pdo));
        self::assertSame('', $service->sanitizeCsvField(null));
        self::assertSame("'=SUM(A1:A2)", $service->sanitizeCsvField('=SUM(A1:A2)'));
        self::assertSame("'  +cmd", $service->sanitizeCsvField('  +cmd'));
        self::assertSame('"a,b"', $service->sanitizeCsvField('a,b'));
        self::assertSame('"a""b"', $service->sanitizeCsvField('a"b'));
        self::assertSame('"a' . "\n" . 'b"', $service->sanitizeCsvField("a\nb"));
    }

    public function testCsvExportHasBomHashMetadataAndAudit(): void
    {
        $service = new ExportService(new ReportDAO($this->pdo), new AuditLogDAO($this->pdo));
        $result = $service->exportTransactionsCsv(1, [], '127.0.0.1');
        self::assertStringStartsWith("\xEF\xBB\xBF", $result['content']);
        self::assertSame(hash('sha256', $result['content']), $result['fileHash']);
        self::assertSame('text/csv; charset=utf-8', $result['contentType']);
        self::assertMatchesRegularExpression('/wealthflow_transactions_\d{8}_\d{6}\.csv/', $result['filename']);
        self::assertCount(1, (new AuditLogDAO($this->pdo))->getExportLogsByUser(1));
    }

    public function testReportDaoReadFunctionsReturnTypedResults(): void
    {
        $dao = new ReportDAO($this->pdo);
        self::assertSame($this->pdo, $dao->getPdo());
        self::assertIsString($dao->getWalletLedgerSum(1, 1));
        self::assertIsArray($dao->getReconciliationSummary(1));
        self::assertIsArray($dao->getTrialBalanceRows(1));
        self::assertIsArray($dao->getTransactionsForExport(1));
        self::assertIsInt($dao->countPostedTransactions(1));
    }

}
