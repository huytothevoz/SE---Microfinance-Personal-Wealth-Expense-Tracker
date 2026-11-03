<?php

declare(strict_types=1);

$backendDirectory = dirname(__DIR__, 3) . '/backend';
$backendAutoload = $backendDirectory . '/vendor/autoload.php';
$phpunitAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($phpunitAutoload)) {
    throw new RuntimeException('Run composer install in source/tests/Unit-testing first.');
}
if (!is_file($backendAutoload)) {
    throw new RuntimeException('Run composer install in source/backend first.');
}

require_once $phpunitAutoload;
require_once $backendAutoload;
require_once __DIR__ . '/Support/DatabaseTestCase.php';
require_once __DIR__ . '/Support/AdminTestCase.php';

date_default_timezone_set('UTC');
