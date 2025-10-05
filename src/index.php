<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

// Connect to main DB to check if user DB exists
try {
    $adminPdo = new PDO(
        "mysql:host={$dbHost};dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );

    $stmt = $adminPdo->prepare("SHOW DATABASES LIKE ?");
    $stmt->execute([$userDbName]);
    $dbExists = $stmt->fetchColumn() !== false;

    if (!$dbExists) {
        $noData = true;
    } else {
        $userPdo = new PDO(
            "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            $pdoOptions
        );
        $noData = false;
    }

} catch (PDOException $e) {
    die("Database connection error: " . htmlspecialchars($e->getMessage()));
}

// Include header & sidebar
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

<?php if ($noData): ?>
            <div class="alert alert-info">
                <h5>No imported data yet</h5>
                <p>Please upload a DDD file first. Once imported, your dashboard will display summary information here.</p>
                <a href="upload.php" class="btn btn-primary">Upload DDD File</a>
            </div>
<?php else: ?>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</p>

            <?php
            // Define main tables you want to summarize
            $tablesToSummarize = [
                'card_event_data_1' => 'Card Events 1',
                'card_event_data_2' => 'Card Events 2',
                'card_driver_activity_1' => 'Driver Activity 1',
                'card_driver_activity_2' => 'Driver Activity 2',
                'card_vehicles_used_1' => 'Vehicles Used 1',
                'card_vehicles_used_2' => 'Vehicles Used 2',
                'card_fault_data_1' => 'Faults 1',
                'card_fault_data_2' => 'Faults 2'
            ];

            echo '<div class="row">';
            foreach ($tablesToSummarize as $table => $label) {
                try {
                    $count = $userPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                } catch (PDOException $e) {
                    $count = 0;
                }
                ?>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= intval($count) ?></h3>
                            <p><?= htmlspecialchars($label) ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <a href="#" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            <?php
            }
            echo '</div>'; // row
            ?>

<?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

