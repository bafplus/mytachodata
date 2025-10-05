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

// Fetch all tables for this user DB
$tables = $userPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Gather table row counts
$tableCounts = [];
foreach ($tables as $table) {
    $stmt = $userPdo->query("SELECT COUNT(*) FROM `$table`");
    $tableCounts[$table] = (int)$stmt->fetchColumn();
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($tableCounts as $table => $count): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $count ?></h3>
                                <p><?= htmlspecialchars($table) ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <a href="views/view_table.php?table=<?= urlencode($table) ?>" class="small-box-footer">
                                View Details <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
