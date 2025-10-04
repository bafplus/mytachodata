<?php
session_start();
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $newLang = basename($_POST['language']); // sanitize
    $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->execute([$newLang, $_SESSION['user_id']]);

    $_SESSION['lang'] = $newLang;
}

header("Location: user.php");
exit;
