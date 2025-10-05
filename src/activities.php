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

// Handle selected date
$selectedDate = $_GET['date'] ?? null;

// Fetch all activity rows
$activityRows = [];
try {
    $stmt = $userPdo->query("SELECT raw, timestamp FROM card_driver_activity_1 ORDER BY timestamp DESC");
    $activityRows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching activities: " . htmlspecialchars($e->getMessage()));
}

// Flatten all activity segments
$activities = [];
$dates = [];
foreach ($activityRows as $row) {
    $raw = json_decode($row['raw'], true);
    if (!$raw || !isset($raw['activity_change_info'])) continue;

    $activityDate = substr($raw['activity_record_date'], 0, 10);
    $dates[$activityDate] = $activityDate; // collect available dates

    if ($selectedDate && $activityDate !== $selectedDate) continue;

    $segments = $raw['activity_change_info'];
    if (count($segments) <= 1) continue;

    // Skip the first segment (index 0)
    $segments = array_slice($segments, 1);

    $previousMinutes = 0;
    $currentType = null;
    $startMinutes = 0;

    foreach ($segments as $segment) {
        $type = $segment['work_type'];
        $endMinutes = $segment['minutes'];

        if ($currentType === null) {
            // First segment after slice
            $currentType = $type;
            $startMinutes = $previousMinutes;
        } elseif ($type !== $currentType) {
            // Save previous block
            $activities[] = [
                'date' => $activityDate,
                'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
                'end_time' => sprintf('%02d:%02d', intdiv($previousMinutes, 60), $previousMinutes % 60),
                'activity_type' => $currentType,
                'duration' => $previousMinutes - $startMinutes
            ];

            // Start new block
            $currentType = $type;
            $startMinutes = $previousMinutes;
        }

        $previousMinutes = $endMinutes;
    }

    // Add last segment
    $activities[] = [
        'date' => $activityDate,
        'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
        'end_time' => sprintf('%02d:%02d', intdiv($previousMinutes, 60), $previousMinutes % 60),
        'activity_type' => $currentType,
        'duration' => $previousMinutes - $startMinutes
    ];
}

// Map work_type numbers to labels
$activityLabels = [
    0 => 'Other Work/Rest',
    1 => 'Available',
    2 => 'Driving',
    3 => 'Rest'
];

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

            <!-- Day selector -->
            <?php if (!empty($dates)): ?>
                <form method="get" class="mb-3">
                    <label for="date">Select Day:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <option value="">-- All Days --</option>
                        <?php foreach ($dates as $dateOption): ?>
                            <option value="<?= htmlspecialchars($dateOption) ?>" <?= $selectedDate === $dateOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dateOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <?php if (empty($activities)): ?>
                <div class="alert alert-info">No activities found.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Activity Type</th>
                            <th>Duration (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $act): ?>
                            <tr>
                                <td><?= htmlspecialchars($act['date']) ?></td>
                                <td><?= htmlspecialchars($act['start_time']) ?></td>
                                <td><?= htmlspecialchars($act['end_time']) ?></td>
                                <td><?= htmlspecialchars($activityLabels[$act['activity_type']] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($act['duration']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
