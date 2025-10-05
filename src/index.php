<?php
require_once __DIR__ . '/inc/db.php';

// Session and login check
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Include header/sidebar
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';

// --- Per-user DB connection setup ---
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$userDbName = "mytacho_user_" . $userId;

// Check if user DB exists
try {
    $dbExistsStmt = $pdo->query("SHOW DATABASES LIKE '{$userDbName}'");
    $dbExists = $dbExistsStmt->rowCount() > 0;
} catch (PDOException $e) {
    $dbExists = false;
}

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
} else {
    $userPdo = null;
}
?>

<!-- Dashboard content -->
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
                <!-- Example: Display some summary info -->
                <div class="row">
                    <?php
                    // Count number of records per top-level table
                    $summary = [];
                    $tablesStmt = $userPdo->query("SHOW TABLES");
                    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($tables as $table) {
                        $cntStmt = $userPdo->query("SELECT COUNT(*) FROM `{$table}`");
                        $count = $cntStmt->fetchColumn();
                        $summary[$table] = $count;
                    }
                    ?>

                    <?php foreach ($summary as $tbl => $cnt): ?>
                        <div class="col-md-3">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?= intval($cnt) ?></h3>
                                    <p><?= htmlspecialchars($tbl) ?></p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-database"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
