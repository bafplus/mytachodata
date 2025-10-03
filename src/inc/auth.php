<?php
// Only checks if a session exists, no session_start() here
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

