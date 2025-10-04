<?php
require_once __DIR__ . '/inc/db.php';

// Session and login check
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user info if needed
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Optional: handle case if user somehow doesn't exist
if (!$user) {
    echo "User not found.";
    exit;
}

// Include header after all checks
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<!-- Dashboard content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <p>Welcome, <?= htmlspecialchars($user['username']) ?>!</p>
            <p>This is your MyTacho dashboard skeleton.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
