<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $reportDao = $ctx['reportDao'];

    // All transactions for user 1
    $all = $reportDao->getTransactionsForExport(1);

    // Filter by wallet 10
    $wallet10Txs = $reportDao->getTransactionsForExport(1, ['walletId' => 10]);

    // Filter by date
    $oct15 = $reportDao->getTransactionsForExport(1, [
        'startDate' => '2026-10-15',
        'endDate' => '2026-10-15',
    ]);

    return [
        'allCount' => count($all),
        'wallet10Count' => count($wallet10Txs),
        'oct15Count' => count($oct15),
        'hasWallet10' => count($wallet10Txs) > 0,
        'hasOct15' => count($oct15) === 1,
    ];
});
