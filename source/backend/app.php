<?php
declare(strict_types=1);

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
try {
    $config = require __DIR__ . '/bootstrap.php';
    $config->validate();
    $auth = new WealthFlow\Modules\Auth\AuthService(WealthFlow\Core\Database::connect($config), $config);
    try {
        $auth->authenticate();
    } catch (WealthFlow\Core\ApiException) {
        $auth->authenticate(true);
    }
    header('Content-Type: text/html; charset=utf-8');
    readfile(dirname(__DIR__) . '/app.html');
} catch (WealthFlow\Core\ApiException) {
    header('Location: index.html?auth=login', true, 302);
} catch (Throwable $error) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>Dịch vụ chưa sẵn sàng. Vui lòng kiểm tra cài đặt máy chủ và MySQL.</p><a href="index.html">Về trang chủ</a>';
}
