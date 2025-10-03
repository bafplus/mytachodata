<?php
session_start();

// Basic auth check (later you can replace with DB logic)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Include layout pieces
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/views/dashboard.php';
include __DIR__ . '/includes/footer.php';

