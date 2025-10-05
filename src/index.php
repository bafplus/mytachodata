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

// Attempt connection to per-user DB
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    $userPdo = null; // no DB yet
}

// Include layout
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
        <div class="container-fluid mt-4">
            <?php if (!$userPdo): ?>
                <div class="alert alert-info">
                    No data imported yet. Please upload your first DDD file.
                </div>
            <?php else: ?>
                <?php
                // Fetch summary statistics
                $summary = [];

                try {
                    // Unique vehicles used
                    $stmt = $userPdo->query("
                        SELECT COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw,'$.vehicle_registration_number'))) AS vehicles
                        FROM card_vehicles_used_1
                    ");
                    $summary['vehicles'] = intval($stmt->fetchColumn() ?: 0);

                    // Total events
                    $stmt = $userPdo->query("SELECT COUNT(*) FROM card_event_data_1");
                    $summary['events'] = intval($stmt->fetchColumn() ?: 0);

                    // Total faults
                    $stmt = $userPdo->query("SELECT COUNT(*) FROM card_fault_data_1");
                    $summary['faults'] = intval($stmt->fetchColumn() ?: 0);

                    // Driver info (take first non-empty name)
                    $stmt = $userPdo->query("
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(raw,'$.driver_name')) AS driver
                        FROM driver_card_application_identification_1
                        WHERE JSON_UNQUOTE(JSON_EXTRACT(raw,'$.driver_name')) IS NOT NULL
                        LIMIT 1
                    ");
                    $summary['driver'] = $stmt->fetchColumn() ?: 'Unknown';

                    // Card number
                    $stmt = $userPdo->query("
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(raw,'$.card_number')) AS card_number
                        FROM card_icc_identification_1
                        WHERE JSON_UNQUOTE(JSON_EXTRACT(raw,'$.card_number')) IS NOT NULL
                        LIMIT 1
                    ");
                    $summary['card'] = $stmt->fetchColumn() ?: 'Unknown';
                } catch (PDOException $e) {
                    // ignore errors if tables are missing
                }
                ?>

                <div class="row">
                    <!-- Driver info -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= htmlspecialchars($summary['driver'] ?? 'Unknown') ?></h3>
                                <p>Driver Name</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Card number -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= htmlspecialchars($summary['card'] ?? 'Unknown') ?></h3>
                                <p>Card Number</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicles used -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= intval($summary['vehicles'] ?? 0) ?></h3>
                                <p>Unique Vehicles Used</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Events -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= intval($summary['events'] ?? 0) ?></h3>
                                <p>Events Recorded</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Faults -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3><?= intval($summary['faults'] ?? 0) ?></h3>
                                <p>Faults</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
