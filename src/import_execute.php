<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php'; // optional for translations

// Start session
if (!isset($_SESSION)) session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if import data exists
if (empty($_SESSION['import_data'])) {
    header('Location: import.php');
    exit;
}

$userId = $_SESSION['user_id'];
$importData = $_SESSION['import_data'];

// Optional: create a user-specific table if not exists
$userTable = "user_data_" . (int)$userId;

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `$userTable` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME,
        rpm INT,
        speed FLOAT,
        coolant_temp FLOAT,
        voltage FLOAT,
        latitude FLOAT,
        longitude FLOAT,
        fuel_level FLOAT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Insert records
$records = $importData['records'] ?? [];
$inserted = 0;

$stmt = $pdo->prepare("
    INSERT INTO `$userTable` (timestamp, rpm, speed, coolant_temp, voltage, latitude, longitude, fuel_level)
    VALUES (:timestamp, :rpm, :speed, :coolant_temp, :voltage, :latitude, :longitude, :fuel_level)
");

foreach ($records as $r) {
    $stmt->execute([
        ':timestamp' => $r['timestamp'] ?? null,
        ':rpm' => $r['rpm'] ?? null,
        ':speed' => $r['speed'] ?? null,
        ':coolant_temp' => $r['coolant_temp'] ?? null,
        ':voltage' => $r['voltage'] ?? null,
        ':latitude' => $r['latitude'] ?? null,
        ':longitude' => $r['longitude'] ?? null,
        ':fuel_level' => $r['fuel_level'] ?? null,
    ]);
    $inserted++;
}

// Clear session import data
unset($_SESSION['import_data']);

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['import_complete'] ?? 'Import Complete' ?></title>
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php require_once __DIR__ . '/inc/header.php'; ?>
    <?php require_once __DIR__ . '/inc/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid mt-4">
                <div class="alert alert-success">
                    <?= sprintf($lang['import_success'] ?? 'Import successful! %d records inserted.', $inserted) ?>
                </div>
                <a href="import.php" class="btn btn-primary"><?= $lang['import_another'] ?? 'Import Another File' ?></a>
                <a href="index.php" class="btn btn-secondary"><?= $lang['back_dashboard'] ?? 'Back to Dashboard' ?></a>
            </div>
        </section>
    </div>

    <?php require_once __DIR__ . '/inc/footer.php'; ?>
</div>

<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
