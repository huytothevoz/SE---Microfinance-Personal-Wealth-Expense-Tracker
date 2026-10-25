<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $summary = $ctx['reconService']->reconcileAllWallets(1);

    return [
        'totalWallets' => $summary['totalWallets'],
        'matchedWallets' => $summary['matchedWallets'],
        'mismatchedWallets' => $summary['mismatchedWallets'],
        'postedCount' => $summary['postedCount'],
        'isAllMatched' => $summary['isAllMatched'],
        'walletsCount' => count($summary['wallets']),
        'validCounts' => ($summary['totalWallets'] >= 3 && $summary['matchedWallets'] >= 2 && $summary['mismatchedWallets'] >= 1),
    ];
});
