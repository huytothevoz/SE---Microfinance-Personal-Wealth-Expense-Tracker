<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $hash = password_hash('User@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $uid = $ctx['userDao']->createUser('Session User', 'sess@wealthflow.local', $hash, 'VND');
    $tokenHash = hash('sha256', 'dummy_refresh_token');

    // Session 1: valid for 7 days
    $validExpires = gmdate('Y-m-d H:i:s', time() + 7 * 86400);
    $ctx['userDao']->createSession('sess_valid', $uid, $tokenHash, $validExpires);

    // Session 2: expired 1 hour ago
    $expiredTime = gmdate('Y-m-d H:i:s', time() - 3600);
    $ctx['userDao']->createSession('sess_expired', $uid, $tokenHash, $expiredTime);

    $foundValid = $ctx['userDao']->findActiveSession('sess_valid', $uid);
    $foundExpired = $ctx['userDao']->findActiveSession('sess_expired', $uid);

    return [
        'valid_found' => $foundValid !== null,
        'valid_session_id' => $foundValid['session_id'] ?? null,
        'expired_found' => $foundExpired !== null,
    ];
});
