<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    // Wallet 12 has cached 9,999,999 but ledger 5,000,000
    $recon = $ctx['reconService']->reconcileWallet(1, 12);

    // Verify security audit log was triggered
    $logs = $ctx['auditLogDao']->getSecurityLogsByUser(1, 5);
    $hasMismatchLog = false;
    foreach ($logs as $l) {
        if ($l['event'] === 'LEDGER_MISMATCH_WALLET_12') {
            $hasMismatchLog = true;
            break;
        }
    }

    return [
        'walletId' => $recon['walletId'],
        'cachedBalance' => $recon['cachedBalance'],
        'ledgerSum' => $recon['ledgerSum'],
        'delta' => $recon['delta'],
        'status' => $recon['status'],
        'isAudited' => $recon['isAudited'],
        'isMismatch' => ($recon['status'] === 'MISMATCH' && $recon['isAudited'] === false),
        'hasMismatchLog' => $hasMismatchLog,
    ];
});
