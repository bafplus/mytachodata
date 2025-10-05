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

// Get selected date (default today)
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch activities for that day
$activities = [];
try {
    $stmt = $userPdo->prepare("
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(raw,'$.activity_change_info.activity')) AS activity_type,
            JSON_UNQUOTE(JSON_EXTRACT(raw,'$.activity_change_info.start_time')) AS start_time,
            JSON_UNQUOTE(JSON_EXTRACT(raw,'$.activity_change_info.end_time')) AS end_time,
            JSON_UNQUOTE(JSON_EXTRACT(raw,'$.vehicle_registration_number')) AS vehicle
        FROM card_activity_daily_1
        WHERE DATE(JSON_UNQUOTE(JSON_EXTRACT(raw,'$.activity_change_info.start_time'))) = :day
        ORDER BY start_time ASC
    ");
    $stmt->execute([':day' => $selectedDate]);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignore if no table/data
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Daily Activities</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Date selector -->
            <form method="get" class="mb-3">
                <label for="date">Select Date:</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($selectedDate) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </form>

            <?php if (empty($activities)): ?>
                <div class="alert alert-info">No activities found for <?= htmlspecialchars($selectedDate) ?>.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Activity</th>
                            <th>Vehicle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['start_time']) ?></td>
                                <td><?= htmlspecialchars($row['end_time']) ?></td>
                                <td><?= htmlspecialchars($row['activity_type']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
