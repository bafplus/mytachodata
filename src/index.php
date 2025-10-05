<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// DB connection details
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Connect to main DB first to check if user DB exists
try {
    $adminPdo = new PDO("mysql:host={$dbHost};dbname=" . getenv('DB_NAME') ?: 'mytacho' . ";charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);

    $stmt = $adminPdo->prepare("SHOW DATABASES LIKE ?");
    $stmt->execute([$userDbName]);
    $dbExists = $stmt->fetchColumn();
    
    if (!$dbExists) {
        die("No data imported yet. Please upload a DDD file first.");
    }
    
    // Connect to per-user DB
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);

} catch (PDOException $e) {
    die("Could not connect: " . htmlspecialchars($e->getMessage()));
}
