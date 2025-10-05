<?php
require_once __DIR__ . '/inc/db.php'; // Provides $dbHost, $dbUser, $dbPass, $pdoOptions

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

try {
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to user database: " . $e->getMessage());
}

// --- Fetch driver info ---
$driverName = '';
$stmt = $userPdo->query("SELECT raw FROM card_identification_and_driver_card_holder_identification_1 LIMIT 1");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $json = json_decode($row['raw'], true);
    $driverName = $json['driver_name'] ?? ($json['name'] ?? 'Unknown');
}

// --- Count distinct vehicles ---
$vehiclesUsed = 0;
$stmt = $userPdo->query("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw,'$.vehicle_registration_number')) AS reg FROM card_vehicles_used_1");
$vehiclesUsed = $stmt->rowCount();

// --- Count events and faults ---
$totalEvents = $userPdo->query("SELECT COUNT(*) FROM card_event_data_1")->fetchColumn()
             + $userPdo->query("SELECT COUNT(*) FROM card_event_data_2")->fetchColumn();
$totalFaults = $userPdo->query("SELECT COUNT(*) FROM card_fault_data_1")->fetchColumn()
              + $userPdo->query("SELECT COUNT(*) FROM card_fault_data_2")->fetchColumn();

// --- Calculate driving hours ---
$totalHours = 0;
foreach (['card_driver_activity_1','card_driver_activity_2'] as $table) {
    $stmt = $userPdo->query("SELECT raw FROM {$table}");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rec = json_decode($row['raw'], true);
        if (!empty($rec['start_time']) && !empty($rec['end_time'])) {
            $start = strtotime($rec['start_time']);
            $end = strtotime($rec['end_time']);
            if ($start && $end && $end > $start) {
                $totalHours += ($end - $start)/3600;
            }
        }
    }
}
$totalHours = round($totalHours,2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MyTacho Dashboard</title>
<link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
<?php require_once __DIR__ . '/inc/header.php'; ?>
<?php require_once __DIR__ . '/inc/sidebar.php'; ?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Dashboard</h1>
      <p>Welcome, <?= htmlspecialchars($driverName) ?></p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?= $vehiclesUsed ?></h3>
              <p>Vehicles Used</p>
            </div>
            <div class="icon"><i class="fas fa-truck"></i></div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?= $totalEvents ?></h3>
              <p>Events</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?= $totalFaults ?></h3>
              <p>Faults</p>
            </div>
            <div class="icon"><i class="fas fa-bug"></i></div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?= $totalHours ?></h3>
              <p>Driving Hours</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
