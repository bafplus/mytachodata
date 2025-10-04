<?php
// lang.php
// Manage language selection for the user

// Determine selected language
$availableLanguages = [];
$langDir = __DIR__ . '/../lang';
if (is_dir($langDir)) {
    foreach (glob($langDir . '/*.php') as $file) {
        $availableLanguages[] = basename($file, '.php');
    }
}

// Default language
$lang = 'en';

// If user has a language set in session, use that
if (isset($_SESSION['user_lang']) && in_array($_SESSION['user_lang'], $availableLanguages)) {
    $lang = $_SESSION['user_lang'];
}

// If user is logged in, override with stored user language
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $dbLang = $stmt->fetchColumn();
    if ($dbLang && in_array($dbLang, $availableLanguages)) {
        $lang = $dbLang;
    }
}

// Load language file
$langFile = $langDir . '/' . $lang . '.php';
if (file_exists($langFile)) {
    include $langFile;
}

// $translations array should now be available for usage
// Example: echo $translations['welcome'];

