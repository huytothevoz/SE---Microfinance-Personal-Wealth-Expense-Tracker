<?php
declare(strict_types=1);

/** Standalone mail check; requires vendor but neither a live DB nor Gmail. */
require dirname(__DIR__) . '/vendor/autoload.php';

use WealthFlow\Core\Config;
use WealthFlow\Core\EmailService;

$tmp = sys_get_temp_dir() . '/wealthflow_mail_test_' . bin2hex(random_bytes(6));
mkdir($tmp, 0700);
mkdir($tmp . '/storage', 0700);
$configFile = $tmp . '/config.example.php';
$values = [
    'APP_ENV' => 'local',
    'MAIL_TRANSPORT' => 'smtp',
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
    'MAIL_ENCRYPTION' => 'tls',
    'MAIL_USERNAME' => 'sender@example.org',
    'MAIL_PASSWORD' => '',
    'MAIL_FROM_ADDRESS' => 'sender@example.org',
    'MAIL_FROM_NAME' => 'WealthFlow',
];
$assertCount = 0;
$assert = static function (bool $value, string $description) use (&$assertCount): void {
    if (!$value) {
        throw new RuntimeException('FAILED: ' . $description);
    }
    $assertCount++;
};
$writeConfig = static function () use ($configFile, &$values): Config {
    file_put_contents($configFile, "<?php\nreturn " . var_export($values, true) . ";\n");
    return new Config(dirname($configFile));
};
try {
    $config = $writeConfig();
    try {
        (new EmailService($config))->assertConfigured();
        throw new RuntimeException('Expected missing password to be detected.');
    } catch (RuntimeException $e) {
        $assert($e->getMessage() === 'MAIL_SMTP_PASSWORD_MISSING', 'missing SMTP password detected');
    }
    $values['MAIL_PASSWORD'] = 'non-empty-placeholder';
    $config = $writeConfig();
    (new EmailService($config))->assertConfigured();
    $assert(true, 'SMTP config with populated credentials passes preflight');

    $values['MAIL_TRANSPORT'] = 'log';
    $values['MAIL_PASSWORD'] = '';
    $config = $writeConfig();
    $mailer = new EmailService($config);
    $mailer->sendPasswordResetOtp('recipient@example.org', 'Nguyễn Văn A', '123456');
    $messages = glob($tmp . '/storage/mail-*.eml');
    $assert(count($messages) === 1, 'log mode saved exactly one message');
    $message = file_get_contents($messages[0]);
    $assert(str_contains($message, 'recipient@example.org'), 'expected recipient in mail');
    $assert(str_contains($message, '123456'), 'OTP is present only in private mail preview');
    $assert(str_contains($message, 'text/html'), 'multipart mail contains HTML body');

    $values['APP_ENV'] = 'production';
    $config = $writeConfig();
    try {
        (new EmailService($config))->assertConfigured();
        throw new RuntimeException('Log mode should be rejected in production.');
    } catch (RuntimeException $e) {
        $assert($e->getMessage() === 'MAIL_LOG_NOT_ALLOWED', 'production cannot write OTP to logs');
    }
    echo "PASS: {$assertCount} email service checks.\n";
} finally {
    foreach (glob($tmp . '/storage/*') as $file) unlink($file);
    rmdir($tmp . '/storage');
    if (is_file($configFile)) unlink($configFile);
    rmdir($tmp);
}
