<?php
declare(strict_types=1);

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    throw new RuntimeException('Run composer install in source/backend.');
}
require_once __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('UTC');

return new WealthFlow\Core\Config(__DIR__);
