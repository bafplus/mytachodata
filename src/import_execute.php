<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$data = $_SESSION['import_data'] ?? null;

if (!$data) {
    die('No data to import. Please upload a DDD file first.');
}

// Create a user-specific database
$userDbName = "mytacho_user_" . $userId;
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$userDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$userDb = new PDO($dsn . ";dbname=$userDbName", $dbUser, $dbPass, $pdoOptions);

// Create events table if not exists
$userDb->exec("
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `timestamp` DATETIME,
    `event_type` INT,
    `vehicle_registration` VARCHAR(32) DEFAULT NULL,
    `vehicle_country` VARCHAR(4) DEFAULT NULL,
    `extra_data` JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$insertStmt = $userDb->prepare("
    INSERT INTO `events` (`timestamp`, `event_type`, `vehicle_registration`, `vehicle_country`, `extra_data`)
    VALUES (:timestamp, :event_type, :vehicle_registration, :vehicle_country, :extra_data)
");

$recordsImported = 0;

// Loop through card events
$cardEventsArray = $data['card_event_data_1']['card_event_records_array'] ?? [];

foreach ($cardEventsArray as $recordGroup) {
    foreach ($recordGroup['card_event_records'] ?? [] as $record) {
        $ts = $record['event_begin_time'] ?? null;

        // Skip if timestamp is null
        if (!$ts) continue;

        // Convert ISO8601 UTC to MySQL DATETIME
        $dt = date('Y-m-d H:i:s', strtotime($ts));

        $vehicle = $record['event_vehicle_registration'] ?? [];
        $vehicleReg = $vehicle['vehicle_registration_number'] ?? null;
        $vehicleCountry = $vehicle['vehicle_registration_nation'] ?? null;

        $insertStmt->execute([
            ':timestamp' => $dt,
            ':event_type' => $record['event_type'] ?? null,
            ':vehicle_registration' => $vehicleReg,
            ':vehicle_country' => $vehicleCountry,
            ':extra_data' => json_encode($record)
        ]);

        $recordsImported++;
    }
}

unset($_SESSION['import_data']);

echo "<p>Import successful! Records imported: $recordsImported</p>";
echo '<p><a href="upload.php">Back to Upload</a></p>';
