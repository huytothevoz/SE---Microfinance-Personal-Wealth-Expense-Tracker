<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $body = [
        'fullName' => 'API Controller User',
        'email' => 'api.ctrl@wealthflow.vn',
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ];
    $req = createTestRequest($body);
    ob_start();
    $ctx['authController']->register($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    return [
        'output_json' => $json,
        'success' => $json['success'] ?? false,
        'http_response_code' => http_response_code(),
    ];
});
