<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// --- DB credentials fallback ---
$dbHost = $dbHost ?? getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = $dbUser ?? getenv('DB_USER') ?: 'mytacho_user';
$dbPass = $dbPass ?? getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = $pdoOptions ?? [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Connect to user-specific DB
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

// Fetch driver info (from first card identification table)
$driverName = $cardNumber = $cardExpiry = '-';
$stmt = $userPdo->query("SELECT raw FROM card_icc_identification_1 ORDER BY id ASC LIMIT 1");
if ($row = $stmt->fetch()) {
    $raw = json_decode($row['raw'], true);
    $driverName = $raw['driver_name'] ?? '-';
    $cardNumber = $raw['card_number'] ?? '-';
    $cardExpiry = $raw['expiry_date'] ?? '-';
}

// Count vehicles
$vehicleCount = 0;
if ($userPdo->query("SHOW TABLES LIKE 'card_vehicles_used_1'")->rowCount() > 0) {
    $stmt = $userPdo->query("SELECT COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw, '$.registration'))) AS cnt FROM card_vehicles_used_1");
    $vehicleCount = intval($stmt->fetchColumn());
}

// Count events
$eventCount = 0;
if ($userPdo->query("SHOW TABLES LIKE 'card_event_data_1'")->rowCount() > 0) {
    $stmt = $userPdo->query("SELECT COUNT(*) FROM card_event_data_1");
    $eventCount = intval($stmt->fetchColumn());
}

// Count faults
$faultCount = 0;
if ($userPdo->query("SHOW TABLES LIKE 'card_fault_data_1'")->rowCount() > 0) {
    $stmt = $userPdo->query("SELECT COUNT(*) FROM card_fault_data_1");
    $faultCount = intval($stmt->fetchColumn());
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
            <div class="row">
                <div class="col-md-4">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-id-card"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Driver Name</span>
                            <span class="info-box-number"><?= htmlspecialchars($driverName) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-car"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Vehicles Used</span>
                            <span class="info-box-number"><?= $vehicleCount ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Events / Faults</span>
                            <span class="info-box-number"><?= $eventCount ?> / <?= $faultCount ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="info-box bg-primary">
                        <span class="info-box-icon"><i class="fas fa-credit-card"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Card Number</span>
                            <span class="info-box-number"><?= htmlspecialchars($cardNumber) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Card Expiry</span>
                            <span class="info-box-number"><?= htmlspecialchars($cardExpiry) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

