<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $email = 'logout.test@wealthflow.vn';
    $ctx['authService']->register([
        'fullName' => 'Logout User',
        'email' => $email,
        'password' => 'User@1234',
        'passwordConfirmation' => 'User@1234',
        'baseCurrency' => 'VND',
    ]);
    $ctx['authService']->login(['email' => $email, 'password' => 'User@1234']);
    $user = $ctx['userDao']->findByEmail($email);
    $uid = (int) $user['id'];
    $sessionsBefore = (int) $ctx['pdo']->query("SELECT COUNT(*) FROM auth_sessions WHERE user_id = {$uid}")->fetchColumn();

    // Mock cookie token to allow logout
    $sessionRow = $ctx['pdo']->query("SELECT * FROM auth_sessions WHERE user_id = {$uid}")->fetch();
    $refreshToken = $ctx['tokenService']->issue($uid, (string) $sessionRow['id'], 'refresh', time() + 86400);
    $_COOKIE['wf_refresh'] = $refreshToken;
    $ctx['userDao']->updateSessionRefresh((string) $sessionRow['id'], hash('sha256', $refreshToken));

    $req = createTestRequest([]);
    ob_start();
    $ctx['authController']->logout($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    $sessionsAfter = (int) $ctx['pdo']->query("SELECT COUNT(*) FROM auth_sessions WHERE user_id = {$uid}")->fetchColumn();

    return [
        'success' => $json['success'] ?? false,
        'sessions_before' => $sessionsBefore,
        'sessions_after' => $sessionsAfter,
        'cleaned_up' => $sessionsBefore > 0 && $sessionsAfter === 0,
    ];
});
