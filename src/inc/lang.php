<?php
// inc/lang.php
session_start();

// Language files directory
$LANG_DIR = __DIR__ . '/../lang';

// Default language
$defaultLang = 'en';

// Determine active language (session > DB > default)
$lang = $_SESSION['lang'] ?? $defaultLang;

// Load language file (fallback to default if missing)
$langFile = "$LANG_DIR/$lang.php";
if (!file_exists($langFile)) {
    $langFile = "$LANG_DIR/$defaultLang.php";
}
require_once $langFile;

// $lang_arr contains the translations
