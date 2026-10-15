<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
try {
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();
    $db = WealthFlow\Database::connect($config);
    $queue = new WealthFlow\Mail\WelcomeEmailQueue($db, new WealthFlow\Mail\EmailService($config));
    $ids = $db->query("SELECT user_id FROM registration_emails WHERE status = 'PENDING' ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
    $failed = 0;
    foreach ($ids as $id) {
        $sent = $queue->deliver((int) $id);
        echo 'User ' . $id . ': ' . ($sent ? 'sent (or local preview)' : 'pending; check SMTP settings') . PHP_EOL;
        if (!$sent) $failed++;
    }
    echo 'Processed: ' . count($ids) . '; pending: ' . $failed . PHP_EOL;
    exit($failed ? 1 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, 'Email worker failed (' . get_class($error) . '). Check server configuration.' . PHP_EOL);
    exit(1);
}
