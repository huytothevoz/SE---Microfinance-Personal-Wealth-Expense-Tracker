<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $req = createTestRequest(); // No walletId param

    ob_start();
    $ctx['controller']->reconcile($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    return [
        'success' => $json['success'] ?? false,
        'http_status' => http_response_code(),
        'totalWallets' => $json['data']['totalWallets'] ?? 0,
        'matchedWallets' => $json['data']['matchedWallets'] ?? 0,
        'mismatchedWallets' => $json['data']['mismatchedWallets'] ?? 0,
        'hasWallets' => is_array($json['data']['wallets'] ?? null),
    ];
});
