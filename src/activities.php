<?php
require_once __DIR__ . '/inc/db.php';

// Start session and check login
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// DB credentials
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Connect to per-user DB
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// Fetch all activities from both tables
$activities = [];

try {
    $tables = ['card_driver_activity_1', 'card_driver_activity_2'];

    foreach ($tables as $table) {
        $stmt = $userPdo->query("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(raw, '$.start_time')) AS start_time,
                JSON_UNQUOTE(JSON_EXTRACT(raw, '$.end_time')) AS end_time,
                JSON_UNQUOTE(JSON_EXTRACT(raw, '$.activity_type')) AS activity_type,
                JSON_UNQUOTE(JSON_EXTRACT(raw, '$.vehicle_registration_number')) AS vehicle
            FROM {$table}
            ORDER BY start_time ASC
        ");
        $activities = array_merge($activities, $stmt->fetchAll());
    }

} catch (PDOException $e) {
    $activities = [];
}

// Include layout
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Driver Activities</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (!empty($activities)): ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Activity Type</th>
                            <th>Vehicle</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['start_time']) ?></td>
                            <td><?= htmlspecialchars($act['end_time']) ?></td>
                            <td><?= htmlspecialchars($act['activity_type']) ?></td>
                            <td><?= htmlspecialchars($act['vehicle']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No activities found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

