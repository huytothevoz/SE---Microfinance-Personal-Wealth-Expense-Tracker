<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $reportDao = $ctx['reportDao'];

    $rows = $reportDao->getTrialBalanceRows(1);

    $hasRows = count($rows) > 0;
    $allAccountsBelongToUser1 = true;
    foreach ($rows as $r) {
        if (!isset($r['code'], $r['account_type'], $r['total_debit'], $r['total_credit'])) {
            $allAccountsBelongToUser1 = false;
        }
    }

    return [
        'count' => count($rows),
        'hasRows' => $hasRows,
        'structureValid' => $allAccountsBelongToUser1,
    ];
});
