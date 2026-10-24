<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $email = 'brute.target@wealthflow.local';
    $ctx['authService']->register([
        'fullName' => 'Brute Target',
        'email' => $email,
        'password' => 'TargetPass@123',
        'passwordConfirmation' => 'TargetPass@123',
        'baseCurrency' => 'VND',
    ]);

    $attempts = [];
    for ($i = 1; $i <= 12; $i++) {
        try {
            $ctx['authService']->login([
                'email' => $email,
                'password' => 'WrongPass',
            ]);
            $attempts[] = 200;
        } catch (\WealthFlow\Core\ApiException $e) {
            $attempts[] = $e->status;
        }
    }

    return [
        'attempts' => $attempts,
        'last_status' => end($attempts),
        'rate_limit_triggered' => in_array(429, $attempts, true),
    ];
});
