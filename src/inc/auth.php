<?php
// inc/auth.php
// Only checks if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

