<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $ctx['authService']->register([
        'fullName' => 'Bcrypt Test',
        'email' => 'bcrypt.cost@wealthflow.local',
        'password' => 'SecurePass@2026',
        'passwordConfirmation' => 'SecurePass@2026',
        'baseCurrency' => 'VND',
    ]);
    $user = $ctx['userDao']->findByEmail('bcrypt.cost@wealthflow.local');
    $hash = (string) ($user['password_hash'] ?? '');

    return [
        'hash_prefix' => substr($hash, 0, 7),
        'hash_length' => strlen($hash),
        'verify_correct' => password_verify('SecurePass@2026', $hash),
        'verify_wrong' => password_verify('WrongPass@2026', $hash),
    ];
});
