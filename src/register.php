<?php
session_start();
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'rootpassword';

// Connect to main DB
$pdo = new PDO("mysql:host=$dbHost;dbname=main_db", $dbUser, $dbPass);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'] ?? null;

    // Check if username exists
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "Username already exists";
        exit;
    }

    // Insert into main DB
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $passwordHash, $email]);

    $userId = $pdo->lastInsertId();

    // Create per-user DB
    $userDbName = "user_$userId";
    $pdo->exec("CREATE DATABASE `$userDbName`");

    // Create driver_data table
    $pdo->exec("
        CREATE TABLE `$userDbName`.driver_data (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            start DATETIME NOT NULL,
            end DATETIME NOT NULL,
            type VARCHAR(50) NOT NULL,
            vehicle_id VARCHAR(50),
            distance DECIMAL(8,2),
            duration INT,
            hash CHAR(32) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "User registered! You can now <a href='login.php'>login</a>.";
}
?>

<form method="POST">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="email" type="email" placeholder="Email">
    <button type="submit">Register</button>
</form>
