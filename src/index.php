<?php
require_once __DIR__ . '/inc/db.php';

// Session and login check
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// DB credentials and options
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Connect to main DB for user info
try {
    $mainPdo = new PDO(
        "mysql:host={$dbHost};dbname=" . (getenv('DB_NAME') ?: 'mytacho') . ";charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect to main database: " . htmlspecialchars($e->getMessage()));
}

// Fetch user info
$stmt = $mainPdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    die("User not found.");
}

// Connect to per-user database if exists
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $userDbExists = true;
} catch (PDOException $e) {
    $userDbExists = false; // database not yet created
}

// Include header and sidebar
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <p>Welcome, <?= htmlspecialchars($user['username']) ?>!</p>

            <?php if (!$userDbExists): ?>
                <div class="alert alert-info">
                    You haven’t imported any data yet. <a href="upload.php">Upload a DDD file</a> to get started.
                </div>
            <?php else: ?>
                <?php
                // Example: show simple counts for some main tables
                $tablesToCheck = [
                    'card_event_data_1',
                    'card_driver_activity_1',
                    'card_vehicles_used_1'
                ];

                echo '<ul class="list-group">';
                foreach ($tablesToCheck as $tbl) {
                    try {
                        $cnt = $userPdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
                        echo '<li class="list-group-item"><strong>' . htmlspecialchars($tbl) . ':</strong> ' . intval($cnt) . ' rows</li>';
                    } catch (PDOException $e) {
                        echo '<li class="list-group-item"><strong>' . htmlspecialchars($tbl) . ':</strong> N/A</li>';
                    }
                }
                echo '</ul>';
                ?>
                <p class="mt-3"><a href="upload.php" class="btn btn-primary">Upload More Data</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
