<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $weakPasswords = [
        'short' => ['pass' => '12345', 'confirm' => '12345'],
        'no_upper' => ['pass' => 'user12345', 'confirm' => 'user12345'],
        'no_digit' => ['pass' => 'UserOnlyPass', 'confirm' => 'UserOnlyPass'],
        'mismatch' => ['pass' => 'User@1234', 'confirm' => 'User@1235'],
    ];
    $results = [];

    foreach ($weakPasswords as $case => $data) {
        try {
            $ctx['authService']->register([
                'fullName' => 'Weak Pass User',
                'email' => "weak_{$case}@test.local",
                'password' => $data['pass'],
                'passwordConfirmation' => $data['confirm'],
                'baseCurrency' => 'VND',
            ]);
            $results[$case] = 'ACCEPTED_UNEXPECTEDLY';
        } catch (\WealthFlow\Core\ApiException $e) {
            $results[$case] = [
                'status' => $e->status,
                'error_code' => $e->errorCode,
                'fields' => $e->fields,
            ];
        }
    }

    return $results;
});
