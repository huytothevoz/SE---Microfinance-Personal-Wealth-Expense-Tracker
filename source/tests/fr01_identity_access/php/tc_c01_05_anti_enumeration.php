<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();

    $t1 = microtime(true);
    $respUnknown = null;
    try {
        $ctx['authService']->login(['email' => 'unknown.ghost@domain.local', 'password' => 'RandomPass@123']);
    } catch (\WealthFlow\Core\ApiException $e) {
        $respUnknown = ['status' => $e->status, 'code' => $e->errorCode, 'msg' => $e->getMessage()];
    }
    $timeUnknown = microtime(true) - $t1;

    $ctx['authService']->register([
        'fullName' => 'Known User',
        'email' => 'known.real@wealthflow.local',
        'password' => 'RealPass@123',
        'passwordConfirmation' => 'RealPass@123',
        'baseCurrency' => 'VND',
    ]);

    $t2 = microtime(true);
    $respKnownWrongPass = null;
    try {
        $ctx['authService']->login(['email' => 'known.real@wealthflow.local', 'password' => 'RandomPass@123']);
    } catch (\WealthFlow\Core\ApiException $e) {
        $respKnownWrongPass = ['status' => $e->status, 'code' => $e->errorCode, 'msg' => $e->getMessage()];
    }
    $timeKnown = microtime(true) - $t2;

    return [
        'unknown_response' => $respUnknown,
        'known_wrong_pass_response' => $respKnownWrongPass,
        'messages_identical' => $respUnknown['msg'] === $respKnownWrongPass['msg'],
        'codes_identical' => $respUnknown['code'] === $respKnownWrongPass['code'],
        'time_diff_seconds' => abs($timeUnknown - $timeKnown),
    ];
});
