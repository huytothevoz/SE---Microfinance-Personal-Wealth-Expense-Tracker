<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $email = 'clean.contract@wealthflow.vn';
    $ctx['authService']->register([
        'fullName' => 'Clean Contract User',
        'email' => $email,
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ]);
    $login = $ctx['authService']->login(['email' => $email, 'password' => 'User@1234']);
    $user = $login['user'];

    return [
        'user_fields' => array_keys($user),
        'leaks_password_hash' => array_key_exists('password_hash', $user),
        'leaks_refresh_hash' => array_key_exists('refresh_hash', $user),
        'leaks_token_version' => array_key_exists('token_version', $user),
    ];
});
