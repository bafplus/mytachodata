<?php
session_start(); // Only once per request
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <!-- Include AdminLTE CSS -->
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <?php include __DIR__ . '/inc/header.php'; ?>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">Dashboard</h1>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</p>
                <p>This is your MyTacho AdminLTE dashboard skeleton.</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/inc/footer.php'; ?>

</div>
<!-- AdminLTE JS -->
<script src="adminlte/plugins/jquery/jquery.min.js"></script>
<script src="adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
