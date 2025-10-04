<?php
// Include DB connection if session is active
require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
$defaultLang = 'en';
$lang = $defaultLang;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['language'])) {
        $lang = $user['language'];
    }
}

// Path to language files
$langFile = __DIR__ . "/lang/{$lang}.php";

// Fallback if file doesn't exist
if (!file_exists($langFile)) {
    $lang = $defaultLang;
    $langFile = __DIR__ . "/lang/{$defaultLang}.php";
}

// Load language strings
$L = [];
if (file_exists($langFile)) {
    $L = include $langFile; // Each file returns an associative array
}

// Example: lang/en.php
// <?php
// return [
//     'welcome' => 'Welcome',
//     'login' => 'Login',
//     'logout' => 'Logout',
//     'dashboard' => 'Dashboard',
// ];


