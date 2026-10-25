<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $recon = $ctx['reconService']->reconcileWallet(1, 10);

    return [
        'walletId' => $recon['walletId'],
        'walletName' => $recon['walletName'],
        'cachedBalance' => $recon['cachedBalance'],
        'ledgerSum' => $recon['ledgerSum'],
        'delta' => $recon['delta'],
        'status' => $recon['status'],
        'isAudited' => $recon['isAudited'],
        'isMatched' => ($recon['status'] === 'MATCHED' && $recon['delta'] === '0.00000000' && $recon['isAudited'] === true),
    ];
});
