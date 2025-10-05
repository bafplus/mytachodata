<?php
require_once __DIR__ . '/inc/db.php'; // main DB connection

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

// Fetch ALL activities
$activities = [];
try {
    $stmt = $userPdo->query("
        SELECT 
            j.activity,
            j.vehicle_registration_number AS vehicle,
            STR_TO_DATE(j.activity_change_time, '%Y-%m-%dT%H:%i:%sZ') AS start_time
        FROM card_activity_daily_record_1,
             JSON_TABLE(
                raw,
                '$.activity_daily_records.activity_changes[*].activity_change_info'
                COLUMNS (
                    activity VARCHAR(50) PATH '$.activity',
                    vehicle_registration_number VARCHAR(50) PATH '$.vehicle_registration_number',
                    activity_change_time VARCHAR(50) PATH '$.activity_change_time'
                )
             ) AS j
        ORDER BY start_time ASC
    ");
    $activities = $stmt->fetchAll();
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
            <h1 class="m-0">All Driver Activities</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (empty($activities)): ?>
                <div class="alert alert-info mt-3">No activities found.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th>Activity</th>
                            <th>Vehicle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $act): ?>
                            <tr>
                                <td><?= htmlspecialchars($act['start_time']) ?></td>
                                <td><?= htmlspecialchars($act['activity']) ?></td>
                                <td><?= htmlspecialchars($act['vehicle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
