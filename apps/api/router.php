<?php
declare(strict_types=1);

// Solo para el servidor de desarrollo: php -S 127.0.0.1:8000 -t apps/api/public apps/api/router.php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/public' . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/public/index.php';
