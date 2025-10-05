<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['import_data'])) {
    header('Location: upload.php');
    exit;
}

$userId = $_SESSION['user_id'];
$data = $_SESSION['import_data'];
$error = '';
$importedCount = 0;

// Define per-user table
$tableName = "events_user_" . intval($userId);

try {
    // Create table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `event_type` INT,
            `timestamp` DATETIME,
            `vehicle_number` VARCHAR(50),
            `raw_json` JSON
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Flatten card_event_records
    $flatRecords = [];
    if (!empty($data['card_event_data_1']['card_event_records_array'])) {
        foreach ($data['card_event_data_1']['card_event_records_array'] as $block) {
            if (!empty($block['card_event_records'])) {
                foreach ($block['card_event_records'] as $rec) {
                    $timestamp = $rec['event_begin_time'] ?? null;
                    if ($timestamp) { // skip null timestamps
                        // Convert ISO8601 to MySQL DATETIME
                        $dt = date('Y-m-d H:i:s', strtotime($timestamp));
                        $flatRecords[] = [
                            'event_type' => $rec['event_type'] ?? null,
                            'timestamp' => $dt,
                            'vehicle_number' => $rec['event_vehicle_registration']['vehicle_registration_number'] ?? '',
                            'raw_json' => json_encode($rec)
                        ];
                    }
                }
            }
        }
    }

    if (empty($flatRecords)) {
        $error = $lang['no_records_to_import'] ?? "No records to import.";
    } else {
        // Insert into DB
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO `$tableName` (`event_type`, `timestamp`, `vehicle_number`, `raw_json`)
            VALUES (:event_type, :timestamp, :vehicle_number, :raw_json)
        ");

        foreach ($flatRecords as $rec) {
            $stmt->execute([
                ':event_type' => $rec['event_type'],
                ':timestamp' => $rec['timestamp'],
                ':vehicle_number' => $rec['vehicle_number'],
                ':raw_json' => $rec['raw_json']
            ]);
            $importedCount++;
        }
        $pdo->commit();

        // Clear session import data
        unset($_SESSION['import_data']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $lang['import_failed'] ?? "Import failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['import_execute_title'] ?? 'Import DDD File' ?></title>
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
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <?= sprintf($lang['records_imported'] ?? '%d records successfully imported.', $importedCount) ?>
                    </div>
                <?php endif; ?>
                <a href="upload.php" class="btn btn-primary mt-3"><?= $lang['back_to_upload'] ?? 'Back to Upload' ?></a>
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


