<?php
require_once __DIR__ . '/inc/db.php'; // contains $pdo, $dbUser, $dbPass, $pdoOptions
require_once __DIR__ . '/inc/lang.php'; // optional

// Start session
if (!isset($_SESSION)) session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get import data from session
if (empty($_SESSION['import_data'])) {
    die('No import data found. Please upload a DDD file first.');
}

$data = $_SESSION['import_data'];

// Define per-user database
$userDb = "mytacho_user_" . $userId;

try {
    // 1. Create per-user database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$userDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2. Connect to the user-specific database
    $pdoUser = new PDO(
        "mysql:host=127.0.0.1;dbname=$userDb;charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );

    // 3. Create events table if not exists
    $pdoUser->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type INT,
            event_begin DATETIME NULL,
            event_end DATETIME NULL,
            vehicle_registration VARCHAR(50),
            vehicle_nation INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Prepare insert statement
    $stmt = $pdoUser->prepare("
        INSERT INTO events (event_type, event_begin, event_end, vehicle_registration, vehicle_nation)
        VALUES (:type, :begin, :end, :reg, :nation)
    ");

    $recordCount = 0;

    // 5. Loop through all card events in the JSON
    if (!empty($data['card_event_data_1']['card_event_records_array'])) {
        foreach ($data['card_event_data_1']['card_event_records_array'] as $eventGroup) {
            foreach ($eventGroup['card_event_records'] as $event) {
                // Skip empty events
                if (empty($event['event_type']) || empty($event['event_begin_time'])) {
                    continue;
                }

                // Convert ISO 8601 to MySQL DATETIME
                $begin = date('Y-m-d H:i:s', strtotime($event['event_begin_time']));
                $end = isset($event['event_end_time']) ? date('Y-m-d H:i:s', strtotime($event['event_end_time'])) : null;

                $stmt->execute([
                    ':type' => $event['event_type'],
                    ':begin' => $begin,
                    ':end' => $end,
                    ':reg' => $event['event_vehicle_registration']['vehicle_registration_number'] ?? null,
                    ':nation' => $event['event_vehicle_registration']['vehicle_registration_nation'] ?? null
                ]);

                $recordCount++;
            }
        }
    }

    echo "Import successful! Records imported: $recordCount";

    // Optionally, unset session import data
    unset($_SESSION['import_data']);

} catch (PDOException $e) {
    die("Import failed: " . $e->getMessage());
}

