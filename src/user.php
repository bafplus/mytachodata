<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Include DB connection
require_once __DIR__ . '/inc/db.php';
?>

<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">User Page</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</p>
            <p>This is a placeholder for the user profile/settings page.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
