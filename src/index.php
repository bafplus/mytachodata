<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// Connect to user DB
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// --- Dashboard counts ---
$counts = [
    'unique_vehicles' => 0,
    'events' => 0,
    'faults' => 0,
    'driver_activity' => 0
];

// Unique Vehicles
$vehiclesTables = ['card_vehicles_used_1','card_vehicles_used_2','card_vehicle_units_used'];
$uniqueVehicles = [];
foreach ($vehiclesTables as $tbl) {
    $sql = "SELECT JSON_UNQUOTE(JSON_EXTRACT(raw, '$.vehicle_registration_number')) AS reg FROM `{$tbl}`";
    foreach ($userPdo->query($sql) as $row) {
        if (!empty($row['reg'])) $uniqueVehicles[$row['reg']] = true;
    }
}
$counts['unique_vehicles'] = count($uniqueVehicles);

// Events
$eventTables = ['card_event_data_1','card_event_data_2','card_place_daily_work_period_1','card_place_daily_work_period_2'];
$counts['events'] = 0;
foreach ($eventTables as $tbl) {
    $res = $userPdo->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`")->fetch(PDO::FETCH_ASSOC);
    $counts['events'] += $res['cnt'] ?? 0;
}

// Faults
$faultTables = ['card_fault_data_1','card_fault_data_2'];
$counts['faults'] = 0;
foreach ($faultTables as $tbl) {
    $res = $userPdo->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`")->fetch(PDO::FETCH_ASSOC);
    $counts['faults'] += $res['cnt'] ?? 0;
}

// Driver Activity
$activityTables = ['card_driver_activity_1','card_driver_activity_2'];
$counts['driver_activity'] = 0;
foreach ($activityTables as $tbl) {
    $res = $userPdo->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`")->fetch(PDO::FETCH_ASSOC);
    $counts['driver_activity'] += $res['cnt'] ?? 0;
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
            <div class="row">
                <!-- Unique Vehicles -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $counts['unique_vehicles'] ?></h3>
                            <p>Unique Vehicles Used</p>
                        </div>
                        <div class="icon"><i class="fas fa-truck"></i></div>
                    </div>
                </div>
                <!-- Events -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $counts['events'] ?></h3>
                            <p>Events</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
                <!-- Faults -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $counts['faults'] ?></h3>
                            <p>Faults</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
                <!-- Driver Activity -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $counts['driver_activity'] ?></h3>
                            <p>Driver Activity Records</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-clock"></i></div>
                    </div>
                </div>
            </div>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>! This is your MyTacho dashboard.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

