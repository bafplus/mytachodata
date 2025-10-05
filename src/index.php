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
$dbMain = getenv('DB_NAME') ?: 'mytacho';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    // Connect to main DB to check if user DB exists
    $adminPdo = new PDO("mysql:host={$dbHost};dbname={$dbMain};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);

    $stmt = $adminPdo->prepare("SHOW DATABASES LIKE ?");
    $stmt->execute([$userDbName]);
    $dbExists = $stmt->fetchColumn();

    if (!$dbExists) {
        die("No data imported yet. Please upload a DDD file first.");
    }

    // Connect to user DB
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);

} catch (PDOException $e) {
    die("Could not connect: " . htmlspecialchars($e->getMessage()));
}

// Fetch driver card info (assuming first record in driver_card_application_identification_1)
$driverName = $cardNumber = $expiryDate = 'Unknown';
try {
    $stmt = $userPdo->query("SELECT `raw` FROM driver_card_application_identification_1 ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        $raw = json_decode($row['raw'], true);
        $driverName = $raw['driver_name'] ?? 'Unknown';
        $cardNumber = $raw['card_number'] ?? 'Unknown';
        $expiryDate = $raw['expiry_date'] ?? 'Unknown';
    }
} catch (PDOException $e) {
    // ignore missing table
}

// Count vehicles used
$vehiclesUsed = 0;
try {
    $stmt = $userPdo->query("SELECT COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw, '$.vehicle_registration_number'))) AS cnt FROM card_vehicles_used_1");
    $row = $stmt->fetch();
    if ($row) $vehiclesUsed = intval($row['cnt']);
} catch (PDOException $e) {
    // ignore missing table
}

// Count total events
$eventsCount = 0;
try {
    $stmt = $userPdo->query("SELECT COUNT(*) AS cnt FROM card_event_data_1");
    $row = $stmt->fetch();
    if ($row) $eventsCount = intval($row['cnt']);
} catch (PDOException $e) {
    // ignore missing table
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
                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Driver Card</h3></div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?= htmlspecialchars($driverName) ?></p>
                            <p><strong>Card Number:</strong> <?= htmlspecialchars($cardNumber) ?></p>
                            <p><strong>Expiry:</strong> <?= htmlspecialchars($expiryDate) ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-success">
                        <div class="card-header"><h3 class="card-title">Vehicles Used</h3></div>
                        <div class="card-body">
                            <p><strong>Total Vehicles:</strong> <?= $vehiclesUsed ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-warning">
                        <div class="card-header"><h3 class="card-title">Events</h3></div>
                        <div class="card-body">
                            <p><strong>Total Events:</strong> <?= $eventsCount ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

