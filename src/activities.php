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

// Try to connect to per-user DB
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

// Selected date (default: today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Fetch activities for that day
$activities = [];
try {
    $stmt = $userPdo->prepare("
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
        WHERE DATE(STR_TO_DATE(j.activity_change_time, '%Y-%m-%dT%H:%i:%sZ')) = :date
        ORDER BY start_time ASC
    ");
    $stmt->execute([':date' => $selectedDate]);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table may not exist yet or structure may differ
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
            <form method="get" class="form-inline my-2">
                <label for="date" class="mr-2">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control mr-2"
                       value="<?= htmlspecialchars($selectedDate) ?>">
                <button type="submit" class="btn btn-primary">Show</button>
            </form>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (empty($activities)): ?>
                <div class="alert alert-info mt-3">No activities found for this date.</div>
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

