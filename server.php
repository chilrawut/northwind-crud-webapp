<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($path) && preg_match('#^/api(?:/|$)#', $path)) {
    require __DIR__ . '/api/index.php';
    return true;
}

if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
