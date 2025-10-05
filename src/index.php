<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// DB connection details
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
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// Helper: extract a value from nested JSON
function extract_from_raw($rawJson, $pathKeys) {
    $data = json_decode($rawJson, true);
    foreach ($pathKeys as $key) {
        if (is_array($data) && isset($data[$key])) {
            $data = $data[$key];
        } elseif (is_array($data) && isset($data[0][$key])) {
            $data = $data[0][$key];
        } else {
            return null;
        }
    }
    return is_scalar($data) ? $data : null;
}

// Fetch driver info
$driverName = 'Unknown';
$cardNumber = 'Unknown';
$stmt = $userPdo->query("SELECT raw FROM driver_card_application_identification_1 LIMIT 1");
if ($raw = $stmt->fetchColumn()) {
    $driverName = extract_from_raw($raw, ['driver_info', 'name']) ?? 'Unknown';
    $cardNumber = extract_from_raw($raw, ['card_info', 'card_number']) ?? 'Unknown';
}

// Fetch unique vehicles used
$vehicles = [];
$stmt = $userPdo->query("SELECT raw FROM card_vehicles_used_1");
while ($raw = $stmt->fetchColumn()) {
    $reg = extract_from_raw($raw, ['vehicle_registration_number']);
    if ($reg) $vehicles[$reg] = true;
}
$vehicleCount = count($vehicles);

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
                <div class="col-md-4">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Driver Name</span>
                            <span class="info-box-number"><?= htmlspecialchars($driverName) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-id-card"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Card Number</span>
                            <span class="info-box-number"><?= htmlspecialchars($cardNumber) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-truck"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Vehicles Used</span>
                            <span class="info-box-number"><?= $vehicleCount ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
