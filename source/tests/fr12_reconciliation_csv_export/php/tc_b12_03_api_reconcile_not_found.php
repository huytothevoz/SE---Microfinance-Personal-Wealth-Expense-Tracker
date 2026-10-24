<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $req = createTestRequest(['walletId' => '99999']);

    ob_start();
    $ctx['controller']->reconcile($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    return [
        'success' => $json['success'] ?? false,
        'http_status' => http_response_code(),
        'errorCode' => $json['error']['code'] ?? '',
        'isNotFound' => (($json['error']['code'] ?? '') === 'WALLET_NOT_FOUND' && http_response_code() === 404),
    ];
});
