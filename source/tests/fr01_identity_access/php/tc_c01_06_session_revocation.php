<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $email = 'revoke.test@wealthflow.local';
    $ctx['authService']->register([
        'fullName' => 'Revoke User',
        'email' => $email,
        'password' => 'OldPass@123',
        'passwordConfirmation' => 'OldPass@123',
        'baseCurrency' => 'VND',
    ]);
    $user = $ctx['userDao']->findByEmail($email);
    $uid = (int) $user['id'];

    // Login to create Device 1 session
    $ctx['authService']->login(['email' => $email, 'password' => 'OldPass@123']);
    $sessionBefore = $ctx['pdo']->query("SELECT * FROM auth_sessions WHERE user_id = {$uid}")->fetch();
    $oldSessionId = (string) $sessionBefore['id'];

    // Issue token for Device 1 session and simulate cookie
    $oldToken = $ctx['tokenService']->issue($uid, $oldSessionId, 'access', time() + 900);
    $_COOKIE['wf_access'] = $oldToken;

    // Change password
    $changeResult = $ctx['authService']->changePassword([
        'currentPassword' => 'OldPass@123',
        'newPassword' => 'NewPass@1234',
        'passwordConfirmation' => 'NewPass@1234',
    ]);

    // Verify old session was revoked
    $oldSessionActive = $ctx['userDao']->findActiveSession($oldSessionId, $uid);

    // Verify updated password works for new login
    $newLogin = $ctx['authService']->login(['email' => $email, 'password' => 'NewPass@1234']);

    return [
        'change_result' => $changeResult,
        'old_session_revoked' => $oldSessionActive === null,
        'new_login_succeeded' => isset($newLogin['user']['id']),
    ];
});
