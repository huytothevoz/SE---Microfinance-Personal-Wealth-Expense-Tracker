<?php
declare(strict_types=1);

/**
 * SMTP diagnostics for local administrators. No OTP, database changes or
 * passwords are printed. Run from CLI only:
 * php bin/check-mail.php --to=you@example.com
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use WealthFlow\Core\EmailService;

try {
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();
    $args = array_slice($argv, 1);
    if (in_array('--help', $args, true)) {
        echo "Usage: php bin/check-mail.php [--to=recipient@example.com]\n";
        echo "Without --to: validate configuration only. With --to: send a test email.\n";
        exit(0);
    }
    $recipient = null;
    foreach ($args as $argument) {
        if (!str_starts_with($argument, '--to=')) {
            throw new InvalidArgumentException('INVALID_ARGUMENT');
        }
        $recipient = substr($argument, 5);
    }
    if ($recipient !== null && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('INVALID_RECIPIENT');
    }

    $mailer = new EmailService($config);
    $mailer->assertConfigured();
    echo "[OK] Mail settings have all required fields.\n";
    if ($recipient === null) {
        echo "[INFO] SMTP delivery not tested. Add --to=your-email@example.com to send a test.\n";
        exit(0);
    }

    $mailer->send(
        $recipient,
        'WealthFlow test',
        'WealthFlow - Kiem tra email',
        '<p>Mail WealthFlow da gui thanh cong den hop thu nay.</p>',
        'Mail WealthFlow da gui thanh cong den hop thu nay.'
    );
    if ($config->get('MAIL_TRANSPORT') === 'log') {
        echo "[OK] Saved a local email preview in backend/storage/ (not sent via SMTP).\n";
    } else {
        echo "[OK] SMTP server accepted the test email. Check Inbox and Spam/Junk.\n";
    }
} catch (Throwable $error) {
    $message = strtolower($error->getMessage());
    $reason = match (true) {
        str_contains($message, 'mail_smtp_password_missing') => 'MAIL_PASSWORD is empty; configure the Gmail App Password in backend/config.local.php.',
        str_contains($message, 'mail_smtp_username_missing') => 'MAIL_USERNAME is empty.',
        str_contains($message, 'mail_preview_directory_not_writable') => 'backend/storage is not writable.',
        str_contains($message, 'mail_log_not_allowed') => 'Log transport only works with APP_ENV=local.',
        str_contains($message, 'mail_') => 'Invalid mail configuration. Check MAIL_* values.',
        str_contains($message, 'authenticate') => 'SMTP authentication failed. Use the Gmail App Password (not the Google sign-in password).',
        str_contains($message, 'connect') => 'Cannot connect to SMTP. Check network/firewall, host, port and TLS settings.',
        str_contains($message, 'certificate'), str_contains($message, 'tls') => 'TLS handshake failed. Check system date, CA certificates and OpenSSL.',
        str_contains($message, 'app_key') => 'APP_KEY not initialized. Run php bin/setup.php first.',
        default => 'Mail test failed. Check backend/config.local.php and PHP error logs.',
    };
    fwrite(STDERR, '[FAIL] ' . $reason . "\n");
    exit(1);
}
