<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include DB connection
require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info if not already in session
if (!isset($_SESSION['username']) || !isset($_SESSION['language'])) {
    $stmt = $pdo->prepare("SELECT username, language FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['language'] = $user['language'] ?? 'en';
    } else {
        // If somehow user does not exist, log out
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Load selected language file
$lang_file = __DIR__ . "/../lang/" . ($_SESSION['language'] ?? 'en') . ".php";
if (file_exists($lang_file)) {
    $lang = include $lang_file;
} else {
    // fallback to English
    $lang = include __DIR__ . "/../lang/en.php";
}
