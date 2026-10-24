<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $ctx['authService']->register([
        'fullName' => 'Login API User',
        'email' => 'api.login@wealthflow.vn',
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ]);
    $req = createTestRequest([
        'email' => 'api.login@wealthflow.vn',
        'password' => 'User@1234',
    ]);
    ob_start();
    $ctx['authController']->login($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    return [
        'success' => $json['success'] ?? false,
        'user_profile' => $json['data']['user'] ?? null,
        'has_password_hash' => isset($json['data']['user']['password_hash']),
    ];
});
