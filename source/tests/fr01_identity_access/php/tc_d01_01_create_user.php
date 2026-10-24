<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $hash = password_hash('User@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $id = $ctx['userDao']->createUser('Nguyen Van D', 'test.d01.new@wealthflow.local', $hash, 'VND');
    $user = $ctx['userDao']->findById($id);

    return [
        'id' => $id,
        'user' => $user,
        'is_active' => ($user['status'] ?? '') === 'ACTIVE',
        'role' => $user['role'] ?? '',
    ];
});
