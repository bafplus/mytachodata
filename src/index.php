<?php
// index.php - Main dashboard entry point

require_once __DIR__ . '/inc/db.php';

// Start session and check login
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Include layout
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($user['username']) ?>!</p>
            <p>This is your MyTacho overview.</p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php
            // Include the main stats dashboard
            $dashboardView = __DIR__ . '/views/dashboard_stats.php';
            if (file_exists($dashboardView)) {
                include $dashboardView;
            } else {
                echo "<p>Dashboard stats view not found.</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
