<?php
/**
 * Router dành RIÊNG cho PHP built-in development server.
 * Chỉ công khai 2 trang HTML, CSS/JS/SVG và 2 route API demo.
 * KHÔNG dùng làm router production.
 */
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
if (str_starts_with($path, '/api')) {
    require __DIR__ . '/backend/public/index.php';
    return true;
}
if ($path === '/') {
    $path = '/index.html';
}
if (!preg_match('~^/(?:index\.html|app\.html|frontend/(?:css|js|assets)/[A-Za-z0-9_./-]+\.(?:css|js|svg))$~', $path) || str_contains($path, '..')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    return true;
}
$file = realpath(__DIR__ . $path);
if ($file === false || !is_file($file) || !str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}
return false;
