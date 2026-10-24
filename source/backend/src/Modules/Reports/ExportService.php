<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use RuntimeException;

final class ExportService
{
    public const UTF8_BOM = "\xEF\xBB\xBF";
    public const MAX_EXPORT_ROWS = 10000;

    /** @var array<int, string> */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    private ReportDAO $reportDao;

    public function __construct(?ReportDAO $reportDao = null)
    {
        $this->reportDao = $reportDao ?? new ReportDAO();
    }

    /**
     * Sanitize a string field against CSV Formula Injection (FR12.04, OWASP CSV Injection).
     * If the value starts with =, +, -, @, \t, \r (or after leading whitespace starts with =, +, -, @),
     * prepend a single quote (') so spreadsheet applications treat it strictly as plain text.
     */
    public function sanitizeCsvField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, self::FORMULA_TRIGGERS, true)) {
            return "'" . $value;
        }

        $trimmed = ltrim($value, " \t\r\n");
        if ($trimmed !== '') {
            $trimmedFirst = substr($trimmed, 0, 1);
            if (in_array($trimmedFirst, ['=', '+', '-', '@'], true)) {
                return "'" . $value;
            }
        }

        return $value;
    }

    /**
     * Export filtered transactions to UTF-8 BOM CSV and record SHA-256 hash in export_audit_logs (FR12.03, FR12.04).
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     csv: string,
     *     filename: string,
     *     rowCount: int,
     *     maxRows: int,
     *     fileHash: string,
     *     auditId: int
     * }
     */
    public function exportTransactionsCsv(int $userId, array $filters = [], ?string $ipAddress = null): array
    {
        $configuredMax = $this->reportDao->getSystemConfigInt('CSV_EXPORT_MAX_ROWS', self::MAX_EXPORT_ROWS);
        $maxRows = max(1, min(self::MAX_EXPORT_ROWS, $configuredMax));

        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $maxRows = max(1, min($maxRows, (int) $filters['limit']));
        }

        $rows = $this->reportDao->findTransactionsForExport($userId, $filters, $maxRows);

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Không thể khởi tạo bộ nhớ đệm xuất tệp CSV.');
        }

        // Prepend UTF-8 BOM so Microsoft Excel on Windows renders Vietnamese diacritics properly (FR12.03)
        fwrite($stream, self::UTF8_BOM);

        // Header row
        fputcsv($stream, [
            'Mã giao dịch',
            'Ngày giao dịch',
            'Loại giao dịch',
            'Danh mục',
            'Ví',
            'Mô tả',
            'Số tiền nguyên tệ',
            'Tiền tệ',
            'Tỷ giá chốt',
            'Số tiền quy đổi (VND)',
            'Trạng thái',
            'Mã giao dịch gốc',
        ]);

        foreach ($rows as $row) {
            $txType = strtoupper((string) ($row['transaction_type'] ?? ''));
            $walletDisplay = (string) ($row['wallet_name'] ?? '');
            if ($txType === 'TRANSFER' && !empty($row['source_wallet_name']) && !empty($row['destination_wallet_name'])) {
                $walletDisplay = $row['source_wallet_name'] . ' -> ' . $row['destination_wallet_name'];
            }

            $amountOrig = (float) ($row['amount'] ?? 0);
            $fxRate = (float) ($row['fx_rate_snapshot'] ?? 1);
            $amountBase = isset($row['amount_base']) && $row['amount_base'] !== null
                ? (float) $row['amount_base']
                : round($amountOrig * $fxRate, 2);

            fputcsv($stream, [
                (string) ($row['id'] ?? ''),
                $this->sanitizeCsvField((string) ($row['occurred_on'] ?? '')),
                $this->sanitizeCsvField($txType),
                $this->sanitizeCsvField((string) ($row['category_name'] ?? 'Chưa phân loại')),
                $this->sanitizeCsvField($walletDisplay),
                $this->sanitizeCsvField((string) ($row['description'] ?? '')),
                number_format($amountOrig, 2, '.', ''),
                $this->sanitizeCsvField((string) ($row['currency'] ?? 'VND')),
                number_format($fxRate, 4, '.', ''),
                number_format($amountBase, 2, '.', ''),
                $this->sanitizeCsvField((string) ($row['status'] ?? 'POSTED')),
                $row['original_transaction_id'] !== null ? (string) $row['original_transaction_id'] : '',
            ]);
        }

        rewind($stream);
        $csvContent = (string) stream_get_contents($stream);
        fclose($stream);

        $fileHash = hash('sha256', $csvContent);
        $rowCount = count($rows);
        $auditId = $this->reportDao->logExportAudit(
            $userId,
            'TRANSACTIONS_CSV',
            $rowCount,
            $fileHash,
            $ipAddress
        );

        return [
            'csv' => $csvContent,
            'filename' => 'wealthflow_transactions_' . date('Ymd_His') . '.csv',
            'rowCount' => $rowCount,
            'maxRows' => $maxRows,
            'fileHash' => $fileHash,
            'auditId' => $auditId,
        ];
    }
}
