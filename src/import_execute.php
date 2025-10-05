<?php
require_once __DIR__ . '/inc/db.php';
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$records = $_SESSION['import_records'] ?? [];

if (!$records) {
    die('No data to import. Go back to upload page.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO user_events (user_id, event_type, timestamp, vehicle_number, raw_json)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($records as $event) {
        $stmt->execute([
            $userId,
            $event['event_type'],
            $event['timestamp'],
            $event['vehicle_number'],
            json_encode($event['additional'])
        ]);
    }

    $pdo->commit();
    unset($_SESSION['import_records']);
    echo "Successfully imported " . count($records) . " records.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Import failed: " . $e->getMessage();
}
