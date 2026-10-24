<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $reportDao = $ctx['reportDao'];

    // Wallet 10: 15,050,000 debit (Tx 1) - 50,000 credit (Tx 4) = 15,000,000.00000000
    $sum10 = $reportDao->getWalletLedgerSum(10, 1);

    // Wallet 11: 1,000 debit (Tx 2) = 1,000.00000000 USD
    $sum11 = $reportDao->getWalletLedgerSum(11, 1);

    // Non-existent wallet returns '0.00000000'
    $sumNone = $reportDao->getWalletLedgerSum(999, 1);

    return [
        'sum10' => $sum10,
        'sum11' => $sum11,
        'sumNone' => $sumNone,
        'isSum10Valid' => ($sum10 === '15000000.00000000'),
        'isSum11Valid' => ($sum11 === '1000.00000000'),
        'isSumNoneValid' => ($sumNone === '0.00000000'),
    ];
});
