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

// Get selected date (default = today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$activities = [];

try {
    $stmt = $userPdo->prepare("
        SELECT 
            j.activity,
            j.vehicle_registration_number AS vehicle,
            j.time AS start_time
        FROM card_activity_daily_record_1,
             JSON_TABLE(
                raw,
                '$.activity_daily_records.activity_changes[*]'
                COLUMNS (
                    activity VARCHAR(50) PATH '$.activity',
                    vehicle_registration_number VARCHAR(50) PATH '$.vehicle_registration_number',
                    time VARCHAR(50) PATH '$.time'
                )
             ) AS j
        WHERE DATE(j.time) = :date
        ORDER BY j.time ASC
    ");
    $stmt->execute([':date' => $selectedDate]);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore if table or JSON paths not found yet
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Activities</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Date selector -->
            <form method="get" class="mb-3">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </form>

            <!-- Activities table -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Activity</th>
                        <th>Vehicle</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($activities): ?>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['start_time']) ?></td>
                            <td><?= htmlspecialchars($act['activity']) ?></td>
                            <td><?= htmlspecialchars($act['vehicle']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No activities found for this date.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
