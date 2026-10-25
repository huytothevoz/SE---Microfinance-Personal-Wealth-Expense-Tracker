<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $req = createTestRequest(['walletId' => '10']);

    ob_start();
    $ctx['controller']->reconcile($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    return [
        'success' => $json['success'] ?? false,
        'http_status' => http_response_code(),
        'walletId' => $json['data']['reconciliation']['walletId'] ?? null,
        'status' => $json['data']['reconciliation']['status'] ?? '',
        'delta' => $json['data']['reconciliation']['delta'] ?? '',
        'isMatched' => ($json['data']['reconciliation']['status'] ?? '') === 'MATCHED',
    ];
});
