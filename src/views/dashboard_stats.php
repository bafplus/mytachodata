<?php
require_once __DIR__ . '/../inc/db.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
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

// --- Helper to count rows safely ---
function table_count($pdo, $table) {
    try {
        return $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// --- Collect summary stats ---
$stats = [
    'card_event_data_1' => table_count($userPdo, 'card_event_data_1'),
    'card_event_data_2' => table_count($userPdo, 'card_event_data_2'),
    'card_fault_data_1' => table_count($userPdo, 'card_fault_data_1'),
    'card_fault_data_2' => table_count($userPdo, 'card_fault_data_2'),
    'card_driver_activity_1' => table_count($userPdo, 'card_driver_activity_1'),
    'card_driver_activity_2' => table_count($userPdo, 'card_driver_activity_2'),
    'card_vehicles_used_1' => table_count($userPdo, 'card_vehicles_used_1'),
    'card_vehicles_used_2' => table_count($userPdo, 'card_vehicles_used_2'),
    'card_border_crossings' => table_count($userPdo, 'card_border_crossings'),
    'card_load_unload_operations' => table_count($userPdo, 'card_load_unload_operations'),
];

// Optional: Extract driver info from first record
$driverInfo = [];
try {
    $stmt = $userPdo->query("SELECT raw FROM `driver_card_application_identification_1` LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        $raw = json_decode($row['raw'], true);
        $driverInfo['name'] = $raw['driver_name'] ?? '-';
        $driverInfo['card_number'] = $raw['card_number'] ?? '-';
        $driverInfo['license_number'] = $raw['driving_licence_number'] ?? '-';
    }
} catch (PDOException $e) {
    $driverInfo = ['name' => '-', 'card_number' => '-', 'license_number' => '-'];
}

// Optional: Total driving hours (sum of card_driver_activity_1)
$totalDrivingSeconds = 0;
try {
    $stmt = $userPdo->query("SELECT raw FROM `card_driver_activity_1`");
    while ($row = $stmt->fetch()) {
        $raw = json_decode($row['raw'], true);
        $totalDrivingSeconds += intval($raw['driving_time'] ?? 0);
    }
} catch (PDOException $e) {}

// Format hours
$totalDrivingHours = round($totalDrivingSeconds / 3600, 2);

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard Stats</h1>
            <p>Summary of your imported card data</p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= htmlspecialchars($driverInfo['name']) ?></h3>
                            <p>Driver Name</p>
                        </div>
                        <div class="icon"><i class="fas fa-user"></i></div>
                    </div>
                </div>

                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= htmlspecialchars($driverInfo['card_number']) ?></h3>
                            <p>Card Number</p>
                        </div>
                        <div class="icon"><i class="fas fa-id-card"></i></div>
                    </div>
                </div>

                <div class="col-lg-4 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $totalDrivingHours ?></h3>
                            <p>Total Driving Hours</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>

            <h4>Record Counts by Table</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Rows</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $tbl => $count): ?>
                    <tr>
                        <td><?= htmlspecialchars($tbl) ?></td>
                        <td><?= intval($count) ?></td>
                        <td><a href="tables/table_view.php?table=<?= urlencode($tbl) ?>" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
