<?php
session_start();

$LANG_DIR = __DIR__ . "/../lang";

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Switch language if requested
if (isset($_GET['lang'])) {
    $newLang = basename($_GET['lang']); // safe, prevents directory traversal
    if (file_exists("$LANG_DIR/$newLang.php")) {
        $_SESSION['lang'] = $newLang;
    }
}

// Load language file
$lang = require "$LANG_DIR/{$_SESSION['lang']}.php";

// Translation helper
function __t($key) {
    global $lang;
    return $lang[$key] ?? $key;
}

// Get list of languages
function getAvailableLanguages() {
    global $LANG_DIR;
    $langs = [];
    foreach (glob("$LANG_DIR/*.php") as $file) {
        $code = basename($file, ".php");
        $langs[$code] = ucfirst($code); // e.g. "en" -> "En"
    }
    return $langs;
}

