<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use WealthFlow\Modules\Admin\AuditLogDAO;

final class ExportService
{
    private const UTF8_BOM = "\xEF\xBB\xBF";
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@'];

    private ReportDAO $reportDao;
    private AuditLogDAO $auditLogDao;

    public function __construct(?ReportDAO $reportDao = null, ?AuditLogDAO $auditLogDao = null)
    {
        $this->reportDao = $reportDao ?? new ReportDAO();
        $this->auditLogDao = $auditLogDao ?? new AuditLogDAO();
    }

    /**
     * Sanitize a field against Formula / CSV Injection (OWASP) and RFC 4180 escaping.
     * If the string begins with =, +, -, @, \t, or \r, prepend a single quote (').
     */
    public function sanitizeCsvField(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // Check if value starts with dangerous character (even after leading whitespace or control chars)
        $trimmed = ltrim($str, " \t\n\r\0\x0B");
        if ($trimmed !== '' && in_array($trimmed[0], self::DANGEROUS_PREFIXES, true)) {
            $str = "'" . $str;
        }

        // RFC 4180 CSV escaping: quote field if it contains delimiter, double-quote, or newline
        if (strpbrk($str, ",\"\r\n") !== false) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }

        return $str;
    }

    /**
     * Export transactions as safe UTF-8 BOM CSV.
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     filename: string,
     *     content: string,
     *     rowCount: int,
     *     fileHash: string,
     *     contentType: string
     * }
     */
    public function exportTransactionsCsv(int $userId, array $filters = [], ?string $clientIp = null): array
    {
        $rows = $this->reportDao->getTransactionsForExport($userId, $filters);

        $headers = [
            'Mã GD',
            'Ngày giao dịch',
            'Loại giao dịch',
            'Mô tả',
            'Danh mục',
            'Ví giao dịch',
            'Ví đích',
            'Số tiền gốc',
            'Loại tiền',
            'Tỷ giá quy đổi',
            'Số tiền quy đổi (VND)',
            'Trạng thái',
            'Thời gian tạo',
        ];

        $lines = [];
        // Header line
        $lines[] = implode(',', array_map([$this, 'sanitizeCsvField'], $headers));

        // Data lines
        foreach ($rows as $tx) {
            $line = [
                $this->sanitizeCsvField($tx['id']),
                $this->sanitizeCsvField($tx['occurred_on']),
                $this->sanitizeCsvField($tx['transaction_type']),
                $this->sanitizeCsvField($tx['description']),
                $this->sanitizeCsvField($tx['category_name'] ?? 'Không phân loại'),
                $this->sanitizeCsvField($tx['wallet_name'] ?? 'N/A'),
                $this->sanitizeCsvField($tx['destination_wallet_name'] ?? ''),
                $this->sanitizeCsvField($tx['amount']),
                $this->sanitizeCsvField($tx['currency']),
                $this->sanitizeCsvField($tx['fx_rate_snapshot']),
                $this->sanitizeCsvField($tx['amount_base']),
                $this->sanitizeCsvField($tx['status']),
                $this->sanitizeCsvField($tx['created_at']),
            ];
            $lines[] = implode(',', $line);
        }

        // Prepend UTF-8 BOM for Microsoft Excel Vietnamese compatibility
        $csvContent = self::UTF8_BOM . implode("\r\n", $lines) . "\r\n";
        $rowCount = count($rows);
        $fileHash = hash('sha256', $csvContent);

        // Audit Trail: record download in export_audit_logs
        $this->auditLogDao->logExportAudit(
            $userId,
            'TRANSACTIONS_CSV',
            $rowCount,
            $fileHash,
            $clientIp
        );

        $timestamp = date('Ymd_His');
        $filename = "wealthflow_transactions_{$timestamp}.csv";

        return [
            'filename' => $filename,
            'content' => $csvContent,
            'rowCount' => $rowCount,
            'fileHash' => $fileHash,
            'contentType' => 'text/csv; charset=utf-8',
        ];
    }
}
