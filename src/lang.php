<?php
require_once __DIR__ . '/inc/db.php';

$LANG_DIR = __DIR__ . '/lang';

if (isset($_GET['lang'])) {
    $newLang = basename($_GET['lang']); // sanitize input
    if (file_exists("$LANG_DIR/$newLang.php")) {
        $_SESSION['lang'] = $newLang;

        // Update database if user is logged in
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$newLang, $_SESSION['user_id']]);
        }
    }
}

// Redirect back to the referring page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
