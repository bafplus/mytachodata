<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

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

// --- Connect to per-user database ---
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
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// --- Fetch summary stats ---
$totals = [];
$tables = [
    'card_event_data_1' => 'Events',
    'card_driver_activity_1' => 'Driver Activities',
    'card_vehicles_used_1' => 'Vehicles Used'
];
foreach ($tables as $tbl => $label) {
    try {
        $stmt = $userPdo->query("SELECT COUNT(*) AS cnt FROM `$tbl`");
        $totals[$tbl] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $totals[$tbl] = 0;
    }
}

// --- Fetch recent events ---
$recentEvents = [];
try {
    $stmt = $userPdo->query("SELECT `timestamp`,`label` FROM `card_event_data_1` ORDER BY `timestamp` DESC LIMIT 10");
    $recentEvents = $stmt->fetchAll();
} catch (Exception $e) {
    $recentEvents = [];
}

// --- Include AdminLTE layout ---
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
            <p>Welcome, <?= htmlspecialchars($user['username']) ?>!</p>

            <!-- Summary cards -->
            <div class="row">
                <?php foreach ($tables as $tbl => $label): ?>
                    <div class="col-lg-4 col-6">
                        <div class="small-box <?= $tbl == 'card_event_data_1' ? 'bg-info' : ($tbl == 'card_driver_activity_1' ? 'bg-success' : 'bg-warning') ?>">
                            <div class="inner">
                                <h3><?= intval($totals[$tbl]) ?></h3>
                                <p><?= htmlspecialchars($label) ?></p>
                            </div>
                            <div class="icon">
                                <i class="<?= $tbl == 'card_event_data_1' ? 'fas fa-calendar-alt' : ($tbl == 'card_driver_activity_1' ? 'fas fa-user-clock' : 'fas fa-truck') ?>"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent events table -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent Events</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Event</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentEvents): ?>
                            <?php foreach ($recentEvents as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['timestamp']) ?></td>
                                    <td><?= htmlspecialchars($e['label']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No events found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Placeholder for charts -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Charts</h3></div>
                <div class="card-body">
                    <canvas id="activityChart" style="height:250px"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('activityChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // fill with dates
            datasets: [{
                label: 'Events over time',
                data: [], // fill with counts
                borderColor: 'rgba(60,141,188,1)',
                backgroundColor: 'rgba(60,141,188,0.2)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
