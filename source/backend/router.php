<?php
/**
 * Router dành RIÊNG cho PHP built-in development server.
 * Công khai landing, assets và API; app.html đi qua kiểm tra phiên máy chủ.
 * KHÔNG dùng làm router production.
 */
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
if ($path === '/api' || str_starts_with($path, '/api/')) {
    $_GET['route'] = trim(substr($path, 4), '/');
    require __DIR__ . '/public/index.php';
    return true;
}
if ($path === '/app.html' || $path === '/app.php') {
    require __DIR__ . '/app.php';
    return true;
}
if ($path === '/') {
    $path = '/index.html';
}
if (!preg_match('~^/(?:index\.html|frontend/(?:css|js|assets|views)/[A-Za-z0-9_./-]+\.(?:css|js|svg|png|jpe?g|html))$~', $path) || str_contains($path, '..')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    return true;
}
$sourceRoot = dirname(__DIR__);
$file = realpath($sourceRoot . $path);
if ($file === false || !is_file($file) || !str_starts_with($file, $sourceRoot . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}
// Local development must always serve the current frontend source. Without
// explicit directives browsers can reuse stale ES modules after a refactor,
// leaving the static application shell visible while JavaScript never starts.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contentTypes = [
    'css' => 'text/css; charset=utf-8',
    'html' => 'text/html; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'svg' => 'image/svg+xml; charset=utf-8',
];
header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($file));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
    readfile($file);
}
return true;
