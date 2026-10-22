<?php
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$path = __DIR__ . $uri;
if ($uri !== '/' && is_file($path)) {
    return false;
}

if (preg_match('#^/admin(?:/(.*))?$#', $uri, $m)) {
    $_GET['r'] = $m[1] ?? '';
    require __DIR__ . '/admin/index.php';
    return true;
}

if (preg_match('#^/api(?:/.*)?$#', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

if ($uri === '/install.php' || $uri === '/install') {
    require __DIR__ . '/install.php';
    return true;
}

$_GET['r'] = ltrim($uri, '/');
require __DIR__ . '/index.php';
