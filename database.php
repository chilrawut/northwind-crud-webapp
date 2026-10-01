<?php

function envValue(string $key, ?string $fallback = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $fallback : $value;
}

$host = envValue('DB_HOST', envValue('MYSQLHOST', 'localhost'));
$port = envValue('DB_PORT', envValue('MYSQLPORT', '3306'));
$database = envValue('DB_DATABASE', envValue('MYSQLDATABASE', 'db_northwind'));
$username = envValue('DB_USERNAME', envValue('MYSQLUSER', 'root'));
$password = envValue('DB_PASSWORD', envValue('MYSQLPASSWORD', 'root'));

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    Response::error('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาตรวจสอบค่าการเชื่อมต่อ', 500);
}
