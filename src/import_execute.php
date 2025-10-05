<?php
require_once __DIR__ . '/inc/db.php';
if (!isset($_SESSION)) session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$data = $_SESSION['import_data'] ?? null;

if (!$data) {
    die("No data available to import. Please upload a DDD file first.");
}

try {
    // Admin PDO connection (from db.php)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create user-specific DB if not exists
    $userDbName = "mytacho_user_" . $userId;
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$userDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Connect to user DB
    $userPdo = new PDO("mysql:host=$dbHost;dbname=$userDbName;charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Create tables ---
    $schema = [
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100),
            timestamp DATETIME,
            raw JSON
        )",
        "CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_date DATE,
            activity_type VARCHAR(50),
            start_time DATETIME,
            end_time DATETIME,
            raw JSON
        )",
        "CREATE TABLE IF NOT EXISTS vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_registration VARCHAR(50),
            odometer_start INT,
            odometer_end INT,
            first_use DATETIME,
            last_use DATETIME,
            raw JSON
        )",
        "CREATE TABLE IF NOT EXISTS controls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            control_type VARCHAR(100),
            timestamp DATETIME,
            raw JSON
        )",
        "CREATE TABLE IF NOT EXISTS places (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_type VARCHAR(50),
            country VARCHAR(10),
            region VARCHAR(10),
            timestamp DATETIME,
            raw JSON
        )",
        "CREATE TABLE IF NOT EXISTS card_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_number VARCHAR(50),
            card_issuer VARCHAR(100),
            driver_name VARCHAR(200),
            issue_date DATE,
            expiry_date DATE,
            raw JSON
        )"
    ];

    foreach ($schema as $sql) {
        $userPdo->exec($sql);
    }

    $summary = [];

    // --- Import Events ---
    if (!empty($data['card_event_data'])) {
        $stmt = $userPdo->prepare("INSERT INTO events (event_type, timestamp, raw) VALUES (?, ?, ?)");
        $count = 0;
        foreach ($data['card_event_data'] as $event) {
            $ts = $event['timestamp'] ?? null;
            if ($ts) $ts = date("Y-m-d H:i:s", strtotime($ts));
            $stmt->execute([
                $event['event_type'] ?? null,
                $ts,
                json_encode($event)
            ]);
            $count++;
        }
        $summary['events'] = $count;
    }

    // --- Import Activities ---
    if (!empty($data['card_activity_daily'])) {
        $stmt = $userPdo->prepare("INSERT INTO activities (activity_date, activity_type, start_time, end_time, raw) VALUES (?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($data['card_activity_daily'] as $activity) {
            $date = $activity['activity_date'] ?? null;
            $start = $activity['start_time'] ?? null;
            $end = $activity['end_time'] ?? null;
            if ($start) $start = date("Y-m-d H:i:s", strtotime($start));
            if ($end) $end = date("Y-m-d H:i:s", strtotime($end));
            $stmt->execute([
                $date,
                $activity['activity_type'] ?? null,
                $start,
                $end,
                json_encode($activity)
            ]);
            $count++;
        }
        $summary['activities'] = $count;
    }

    // --- Import Vehicles ---
    if (!empty($data['card_vehicle_records'])) {
        $stmt = $userPdo->prepare("INSERT INTO vehicles (vehicle_registration, odometer_start, odometer_end, first_use, last_use, raw) VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($data['card_vehicle_records'] as $vehicle) {
            $stmt->execute([
                $vehicle['registration'] ?? null,
                $vehicle['odometer_begin'] ?? null,
                $vehicle['odometer_end'] ?? null,
                !empty($vehicle['first_use']) ? date("Y-m-d H:i:s", strtotime($vehicle['first_use'])) : null,
                !empty($vehicle['last_use']) ? date("Y-m-d H:i:s", strtotime($vehicle['last_use'])) : null,
                json_encode($vehicle)
            ]);
            $count++;
        }
        $summary['vehicles'] = $count;
    }

    // --- Import Controls ---
    if (!empty($data['card_control_activity_data'])) {
        $stmt = $userPdo->prepare("INSERT INTO controls (control_type, timestamp, raw) VALUES (?, ?, ?)");
        $count = 0;
        foreach ($data['card_control_activity_data'] as $ctrl) {
            $ts = $ctrl['timestamp'] ?? null;
            if ($ts) $ts = date("Y-m-d H:i:s", strtotime($ts));
            $stmt->execute([
                $ctrl['control_type'] ?? null,
                $ts,
                json_encode($ctrl)
            ]);
            $count++;
        }
        $summary['controls'] = $count;
    }

    // --- Import Places ---
    if (!empty($data['card_place_records'])) {
        $stmt = $userPdo->prepare("INSERT INTO places (entry_type, country, region, timestamp, raw) VALUES (?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($data['card_place_records'] as $place) {
            $ts = $place['timestamp'] ?? null;
            if ($ts) $ts = date("Y-m-d H:i:s", strtotime($ts));
            $stmt->execute([
                $place['entry_type'] ?? null,
                $place['country'] ?? null,
                $place['region'] ?? null,
                $ts,
                json_encode($place)
            ]);
            $count++;
        }
        $summary['places'] = $count;
    }

    // --- Import Card Info ---
    if (!empty($data['card_identification'])) {
        $stmt = $userPdo->prepare("INSERT INTO card_info (card_number, card_issuer, driver_name, issue_date, expiry_date, raw) VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($data['card_identification'] as $info) {
            $stmt->execute([
                $info['card_number'] ?? null,
                $info['issuing_member_state'] ?? null,
                $info['driver_name'] ?? null,
                !empty($info['issue_date']) ? date("Y-m-d", strtotime($info['issue_date'])) : null,
                !empty($info['expiry_date']) ? date("Y-m-d", strtotime($info['expiry_date'])) : null,
                json_encode($info)
            ]);
            $count++;
        }
        $summary['card_info'] = $count;
    }

    echo "<h3>Import completed!</h3><ul>";
    foreach ($summary as $table => $count) {
        echo "<li>" . htmlspecialchars($table) . ": " . intval($count) . " records imported</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "Import failed: " . htmlspecialchars($e->getMessage());
}
