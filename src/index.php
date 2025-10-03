<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Include DB connection
require_once __DIR__ . '/../inc/db.php';
?>

<!-- Include reusable header -->
<?php include __DIR__ . '/../inc/header.php'; ?>

<!-- Include sidebar -->
<?php include __DIR__ . '/../inc/sidebar.php'; ?>

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
            <p>This is your MyTacho dashboard skeleton.</p>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include __DIR__ . '/../inc/footer.php'; ?>



