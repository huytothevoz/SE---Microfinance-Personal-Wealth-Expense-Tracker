<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $ctx['pdo']->beginTransaction();

    $hash = password_hash('User@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $id = $ctx['userDao']->createUser('Rollback User', 'atomic.rollback@wealthflow.local', $hash, 'VND');
    $ctx['pdo']->rollBack();

    $user = $ctx['userDao']->findByEmail('atomic.rollback@wealthflow.local');

    return [
        'created_id' => $id,
        'exists_after_rollback' => $user !== null,
    ];
});
