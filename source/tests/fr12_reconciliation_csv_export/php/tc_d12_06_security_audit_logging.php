<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $auditLogDao = $ctx['auditLogDao'];

    $logId = $auditLogDao->logSecurityEvent(1, 'LEDGER_MISMATCH_WALLET_12');
    $logs = $auditLogDao->getSecurityLogsByUser(1, 10);

    return [
        'logId' => $logId,
        'hasLogs' => count($logs) > 0,
        'event' => $logs[0]['event'] ?? '',
        'eventMatches' => ($logs[0]['event'] ?? '') === 'LEDGER_MISMATCH_WALLET_12',
    ];
});
