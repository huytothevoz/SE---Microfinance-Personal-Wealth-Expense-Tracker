<?php

declare(strict_types=1);

use WealthFlow\Modules\Transactions\ExchangeRateService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    require dirname(__DIR__) . '/bootstrap.php';
    $snapshot = (new ExchangeRateService())->syncPublicRates();
    echo 'Vietcombank rates synchronized at ' . $snapshot['effectiveAt'] . PHP_EOL;
    foreach ($snapshot['rates'] as $currency => $rate) {
        echo $currency . '/VND: ' . $rate . PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Exchange-rate synchronization failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
