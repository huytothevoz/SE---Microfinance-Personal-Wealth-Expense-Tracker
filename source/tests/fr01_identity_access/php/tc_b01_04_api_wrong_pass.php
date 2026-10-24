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
        'password' => 'WrongPass999',
    ]);
    $caught = null;
    try {
        $ctx['authController']->login($req);
    } catch (\WealthFlow\Core\ApiException $e) {
        $caught = [
            'status' => $e->status,
            'code' => $e->errorCode,
            'message' => $e->getMessage(),
        ];
    }

    return ['login_failure' => $caught];
});
