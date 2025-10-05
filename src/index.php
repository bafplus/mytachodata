<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$userDbName = "mytacho_user_" . $userId;

try {
    $dbExistsStmt = $pdo->query("SHOW DATABASES LIKE '{$userDbName}'");
    $dbExists = $dbExistsStmt->rowCount() > 0;
} catch (PDOException $e) {
    $dbExists = false;
}

$userPdo = null;
if ($dbExists) {
    try {
        $userPdo = new PDO(
            "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            $pdoOptions
        );
    } catch (PDOException $e) {
        die("<div class='alert alert-danger'>Could not connect to user database: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <p>Welcome, <?= htmlspecialchars($user['username']) ?>!</p>

            <?php if (!$dbExists): ?>
                <div class="alert alert-info">
                    No data imported yet. Please <a href="upload.php">upload a DDD file</a> to start.
                </div>
            <?php else: ?>
                <?php
                // Fetch only key summary data
                $summary = [
                    'total_records' => 0,
                    'events_faults' => 0,
                    'vehicles_used' => 0,
                    'last_card_download' => null
                ];

                $tables = $userPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $count = $userPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $summary['total_records'] += $count;

                    if (strpos($table, 'card_event_data') !== false || strpos($table, 'card_fault_data') !== false) {
                        $summary['events_faults'] += $count;
                    }

                    if (strpos($table, 'card_vehicles_used') !== false) {
                        $summary['vehicles_used'] += $count;
                    }

                    if (strpos($table, 'last_card_download') !== false) {
                        $last = $userPdo->query("SELECT MAX(`timestamp`) FROM `{$table}`")->fetchColumn();
                        if ($last) $summary['last_card_download'] = $last;
                    }
                }
                ?>

                <div class="row">
                    <div class="col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?= $summary['total_records'] ?></h3>
                                <p>Total Records</p>
                            </div>
                            <div class="icon"><i class="fas fa-database"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= $summary['events_faults'] ?></h3>
                                <p>Events & Faults</p>
                            </div>
                            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $summary['vehicles_used'] ?></h3>
                                <p>Vehicles Used</p>
                            </div>
                            <div class="icon"><i class="fas fa-truck"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $summary['last_card_download'] ?? '-' ?></h3>
                                <p>Last Card Download</p>
                            </div>
                            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
