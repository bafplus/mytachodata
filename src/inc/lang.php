<?php
session_start();

$LANG_DIR = __DIR__ . "/../lang";

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Switch language if requested
if (isset($_GET['lang'])) {
    $newLang = basename($_GET['lang']); // safe
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

// Human-readable names
function getLanguageNames() {
    return [
        'en' => 'English',
        'nl' => 'Nederlands',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano'
        // add more if needed
    ];
}

// Get list of available languages based on files
function getAvailableLanguages() {
    global $LANG_DIR;
    $allNames = getLanguageNames();
    $langs = [];
    foreach (glob("$LANG_DIR/*.php") as $file) {
        $code = basename($file, ".php");
        $langs[$code] = $allNames[$code] ?? strtoupper($code);
    }
    return $langs;
}
