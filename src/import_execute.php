<?php
require_once __DIR__ . '/inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$data = $_SESSION['import_data'] ?? null;

if (!$data) {
    die("No data to import. Please upload a DDD file first.");
}

// Create per-user table if not exists
$tableName = "events_user_" . $userId;
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `$tableName` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type INT,
        vehicle_reg VARCHAR(20),
        timestamp DATETIME
    )
");

// Begin transaction
$imported = 0;
$pdo->beginTransaction();

try {
    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'card_event_data')) {
            if (!empty($value['card_event_records_array'])) {
                foreach ($value['card_event_records_array'] as $arrayItem) {
                    if (!empty($arrayItem['card_event_records'])) {
                        foreach ($arrayItem['card_event_records'] as $record) {
                            if (!empty($record['event_begin_time'])) {
                                // Convert ISO8601 to MySQL DATETIME
                                $dt = date('Y-m-d H:i:s', strtotime($record['event_begin_time']));
                                $vehicle = $record['event_vehicle_registration']['vehicle_registration_number'] ?? '';
                                $stmt = $pdo->prepare("INSERT INTO `$tableName` (event_type, vehicle_reg, timestamp) VALUES (?, ?, ?)");
                                $stmt->execute([$record['event_type'], $vehicle, $dt]);
                                $imported++;
                            }
                        }
                    }
                }
            }
        }
    }

    $pdo->commit();
    unset($_SESSION['import_data']);
    echo "Import successful! Records imported: $imported";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Import failed: " . $e->getMessage();
}
