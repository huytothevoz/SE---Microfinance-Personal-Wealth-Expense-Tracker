<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    require_once dirname(__DIR__) . '/src/bootstrap.php';
    /** @var \WealthFlow\Config $config */
    $config = require dirname(__DIR__) . '/bootstrap.php';
    $config->validate();

    $result = (new \WealthFlow\Modules\Recurring\RecurringWorker())->processDueSchedules();
    fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL);
} catch (Throwable $error) {
    fwrite(STDERR, 'Recurring worker failed (' . get_class($error) . ').' . PHP_EOL);
    exit(1);
}
