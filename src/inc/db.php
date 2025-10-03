<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';  // force TCP
$db   = getenv('DB_NAME') ?: 'mytacho';
$user = getenv('DB_USER') ?: 'mytacho_user';
$pass = getenv('DB_PASS') ?: 'mytacho_pass';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

