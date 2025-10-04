<?php
session_start();

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Switch language if requested
if (isset($_GET['lang'])) {
    $newLang = basename($_GET['lang']); // prevent path traversal
    if (file_exists(__DIR__ . "/../lang/$newLang.php")) {
        $_SESSION['lang'] = $newLang;
    }
}

$lang = require __DIR__ . "/../lang/{$_SESSION['lang']}.php";

function __t($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
