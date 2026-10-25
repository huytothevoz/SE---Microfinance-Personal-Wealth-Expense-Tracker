<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use WealthFlow\Core\AuthContext;

runAndSendJson(function () {
    $ctx = setupTestContext();

    // User 1 tries to reconcile Wallet 20 (belongs to User 2)
    AuthContext::setUserId(1);
    $reqCrossRecon = createTestRequest(['walletId' => '20']);

    ob_start();
    $ctx['controller']->reconcile($reqCrossRecon);
    $output = ob_get_clean();
    $crossReconJson = json_decode($output, true);

    // User 1 exports CSV: should NOT contain User 2's transaction (Tx 6: 'Giao dịch bí mật User 2')
    $reqExport = createTestRequest();
    ob_start();
    $ctx['controller']->exportCsv($reqExport);
    $csvOutput = ob_get_clean();

    $containsUser2Data = str_contains($csvOutput, 'Giao dịch bí mật User 2');

    return [
        'crossReconBlocked' => (($crossReconJson['error']['code'] ?? '') === 'WALLET_NOT_FOUND'),
        'csvExcludesOtherUsers' => !$containsUser2Data,
        'multiTenantIsolated' => (($crossReconJson['error']['code'] ?? '') === 'WALLET_NOT_FOUND' && !$containsUser2Data),
    ];
});
