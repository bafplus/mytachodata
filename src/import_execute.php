<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION)) session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Check if import data exists
if (empty($_SESSION['import_data'])) {
    die('No data to import. Please upload a .ddd file first.');
}

$data = $_SESSION['import_data'];

// Ensure user-specific table exists
$tableName = "events_user_$userId";
$createTableSQL = "
CREATE TABLE IF NOT EXISTS `$tableName` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type INT NOT NULL,
    timestamp DATETIME NOT NULL,
    vehicle_country_code VARCHAR(10),
    vehicle_number VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$pdo->exec($createTableSQL);

// Prepare insert statement
$insertSQL = "INSERT INTO `$tableName` (event_type, timestamp, vehicle_country_code, vehicle_number) VALUES (:event_type, :timestamp, :vehicle_country_code, :vehicle_number)";
$stmt = $pdo->prepare($insertSQL);

$recordsImported = 0;

try {
    $pdo->beginTransaction();

    // Navigate to event records
    $eventArrays = $data['card_event_data_1']['card_event_records_array'] ?? [];

    foreach ($eventArrays as $array) {
        $cardEvents = $array['card_event_records'] ?? [];
        foreach ($cardEvents as $event) {

            // Skip records without valid start time
            if (empty($event['event_begin_time'])) continue;

            // Convert ISO 8601 to MySQL DATETIME
            $timestamp = str_replace('T', ' ', $event['event_begin_time']);
            $timestamp = rtrim($timestamp, 'Z'); // Remove UTC marker

            $stmt->execute([
                ':event_type' => $event['event_type'] ?? 0,
                ':timestamp' => $timestamp,
                ':vehicle_country_code' => $event['event_vehicle_registration']['vehicle_registration_nation'] ?? null,
                ':vehicle_number' => $event['event_vehicle_registration']['vehicle_registration_number'] ?? null
            ]);

            $recordsImported++;
        }
    }

    $pdo->commit();

    echo "<div class='alert alert-success'>Import successful! Records imported: $recordsImported</div>";

    // Clear session import data
    unset($_SESSION['import_data']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Import failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<a href="upload.php" class="btn btn-primary">Back to Upload</a>
