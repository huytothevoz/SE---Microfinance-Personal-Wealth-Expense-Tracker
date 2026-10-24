<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $email = 'otp.lifecycle@wealthflow.local';
    $ctx['authService']->register([
        'fullName' => 'OTP User',
        'email' => $email,
        'password' => 'Pass@1234',
        'passwordConfirmation' => 'Pass@1234',
        'baseCurrency' => 'VND',
    ]);
    $user = $ctx['userDao']->findByEmail($email);
    $uid = (int) $user['id'];

    $ctx['authService']->requestPasswordReset(['email' => $email]);
    $tokenRow = $ctx['pdo']->query("SELECT * FROM password_reset_tokens WHERE user_id = {$uid} ORDER BY id DESC LIMIT 1")->fetch();
    $expiryDiff = strtotime($tokenRow['expires_at'] . ' UTC') - strtotime($tokenRow['created_at'] . ' UTC');

    // Find the OTP by matching sha256 HMAC of 6 digits (fast in-memory)
    $appKey = $ctx['config']->get('APP_KEY');
    $matchedOtp = null;
    for ($code = 100000; $code <= 999999; $code++) {
        if (hash_hmac('sha256', (string) $code, $appKey) === $tokenRow['token_hash']) {
            $matchedOtp = (string) $code;
            break;
        }
    }

    // Reset password using the matched OTP
    $resetFirst = $ctx['authService']->resetPassword([
        'email' => $email,
        'otp' => $matchedOtp,
        'newPassword' => 'NewPass@1234',
        'passwordConfirmation' => 'NewPass@1234',
    ]);

    // Second attempt with the SAME OTP must fail (single-use OTP)
    $secondAttemptCaught = null;
    try {
        $ctx['authService']->resetPassword([
            'email' => $email,
            'otp' => $matchedOtp,
            'newPassword' => 'Another@1234',
            'passwordConfirmation' => 'Another@1234',
        ]);
    } catch (\WealthFlow\Core\ApiException $e) {
        $secondAttemptCaught = [
            'status' => $e->status,
            'code' => $e->errorCode,
        ];
    }

    return [
        'otp_found' => $matchedOtp !== null,
        'is_6_digits' => (bool) preg_match('/^\d{6}$/', (string) $matchedOtp),
        'expiry_seconds' => $expiryDiff,
        'reset_succeeded' => !empty($resetFirst['message']),
        'second_attempt_rejected' => $secondAttemptCaught !== null && $secondAttemptCaught['status'] === 400,
        'rejection_code' => $secondAttemptCaught['code'] ?? null,
    ];
});
