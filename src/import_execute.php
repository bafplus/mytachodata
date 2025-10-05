<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_SESSION['import_data'])) {
    die('No data to import.');
}

$data = $_SESSION['import_data'];
$flatRecords = $data['flat_records'] ?? [];
$tableName = "events_user_" . $userId;

try {
    // Create per-user table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type INT,
            timestamp DATETIME,
            vehicle_number VARCHAR(50),
            raw_json JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->beginTransaction();
    $insertStmt = $pdo->prepare("
        INSERT INTO `$tableName` (event_type, timestamp, vehicle_number, raw_json)
        VALUES (:event_type, :timestamp, :vehicle_number, :raw_json)
    ");

    $imported = 0;
    foreach ($flatRecords as $r) {
        $insertStmt->execute([
            ':event_type' => $r['event_type'],
            ':timestamp' => $r['timestamp'],
            ':vehicle_number' => $r['vehicle_number'],
            ':raw_json' => json_encode($r['raw_json'])
        ]);
        $imported++;
    }

    $pdo->commit();
    unset($_SESSION['import_data']);

    echo "<p class='alert alert-success'>Import successful! $imported records imported for user $userId.</p>";
    echo "<a href='upload.php' class='btn btn-primary'>Back to Upload</a>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p class='alert alert-danger'>Import failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='upload.php' class='btn btn-secondary'>Back to Upload</a>";
}

