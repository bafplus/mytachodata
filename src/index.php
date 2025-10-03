<?php
session_start();
require_once __DIR__ . '/inc/auth.php';   // checks if user is logged in
require_once __DIR__ . '/inc/db.php';
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</p>
            <p>Start adding your driver card data tables here.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

