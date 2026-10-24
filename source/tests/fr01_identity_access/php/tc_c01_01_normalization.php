<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $ctx['authService']->register([
        'fullName' => '   Võ   Văn   Hoàng   ',
        'email' => '   Hoang.Vo_Test@GMAIL.Com   ',
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ]);
    $user = $ctx['userDao']->findByEmail('hoang.vo_test@gmail.com');

    return [
        'registered' => true,
        'saved_name' => $user['full_name'] ?? null,
        'saved_email' => $user['email'] ?? null,
    ];
});
