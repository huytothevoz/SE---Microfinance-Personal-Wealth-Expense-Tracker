<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $auditLogDao = $ctx['auditLogDao'];

    $hash = hash('sha256', 'mock_csv_content');
    $logId = $auditLogDao->logExportAudit(1, 'TRANSACTIONS_CSV', 4, $hash, '192.168.1.10');

    $logs = $auditLogDao->getExportLogsByUser(1, 10);

    return [
        'logId' => $logId,
        'hasLogs' => count($logs) > 0,
        'matchHash' => ($logs[0]['file_hash'] ?? '') === $hash,
        'matchRowCount' => (int) ($logs[0]['row_count'] ?? 0) === 4,
        'matchType' => ($logs[0]['export_type'] ?? '') === 'TRANSACTIONS_CSV',
    ];
});
