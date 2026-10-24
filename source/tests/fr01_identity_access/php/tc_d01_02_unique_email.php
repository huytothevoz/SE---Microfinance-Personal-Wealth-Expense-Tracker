<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $hash = password_hash('User@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $ctx['userDao']->createUser('User One', 'unique.test@wealthflow.local', $hash, 'VND');
    try {
        $ctx['userDao']->createUser('User Two', 'unique.test@wealthflow.local', $hash, 'USD');
        return ['duplicate_rejected' => false];
    } catch (\PDOException $e) {
        return [
            'duplicate_rejected' => true,
            'sqlstate' => $e->getCode(),
            'error_info' => $e->errorInfo,
        ];
    }
});
