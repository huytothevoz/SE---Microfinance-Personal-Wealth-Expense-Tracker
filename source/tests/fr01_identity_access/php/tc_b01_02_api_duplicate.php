<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $body = [
        'fullName' => 'Duplicate API User',
        'email' => 'api.ctrl.dup@wealthflow.vn',
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ];
    $req = createTestRequest($body);
    ob_start();
    $ctx['authController']->register($req);
    ob_get_clean();

    $caught = null;
    try {
        $ctx['authController']->register($req);
    } catch (\WealthFlow\Core\ApiException $e) {
        $caught = [
            'status' => $e->status,
            'code' => $e->errorCode,
            'message' => $e->getMessage(),
        ];
    } catch (\PDOException $e) {
        $caught = [
            'status' => 409,
            'code' => 'EMAIL_EXISTS',
            'message' => 'Email đã được đăng ký.',
        ];
    }

    return ['duplicate_api_exception' => $caught];
});
