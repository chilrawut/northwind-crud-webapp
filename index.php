<?php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../controllers/ProductController.php';

try {
    $controller = new ProductController($pdo);
    $router = new Router();
    $router->get('/products', [$controller, 'index']);
    $router->get('/products/{id}', [$controller, 'show']);
    $router->post('/products', [$controller, 'store']);
    $router->put('/products/{id}', [$controller, 'update']);
    $router->delete('/products/{id}', [$controller, 'destroy']);
    $router->get('/categories', [$controller, 'categories']);
    $router->get('/suppliers', [$controller, 'suppliers']);

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // รองรับทั้ง root domain (/api/products) และ MAMP subfolder (/webapp/api/products)
    $path = preg_replace('#^.*?/api(?:/index\.php)?#', '', $path, 1);
    if ($path === '' || $path === false) $path = '/';
    $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $e) {
    error_log($e->__toString());
    Response::error('ไม่สามารถเชื่อมต่อหรือประมวลผลฐานข้อมูลได้', 500);
}
