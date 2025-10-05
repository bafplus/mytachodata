<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

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
$stmt = $mainPdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) die("User not found.");

// Connect to user DB if exists
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
    $userDbExists = false;
}

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
                <div class="row">
                    <?php
                    // Define blocks to show on dashboard
                    $blocks = [
                        'card_event_data_1' => 'Events',
                        'card_driver_activity_1' => 'Driver Activities',
                        'card_vehicles_used_1' => 'Vehicles Used',
                        'card_fault_data_1' => 'Faults',
                        'card_border_crossings' => 'Border Crossings',
                        'card_load_unload_operations' => 'Load/Unload Ops',
                        'gnss_accumulated_driving' => 'GNSS Driving'
                    ];

                    foreach ($blocks as $table => $label):
                        try {
                            $count = $userPdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                        } catch (PDOException $e) {
                            $count = 0;
                        }
                    ?>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= intval($count) ?></h3>
                                    <p><?= htmlspecialchars($label) ?></p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <a href="view_table.php?table=<?= urlencode($table) ?>" class="small-box-footer">
                                    View <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="mt-3"><a href="upload.php" class="btn btn-primary">Upload More Data</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

