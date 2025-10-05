<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

// Start session
if (!isset($_SESSION)) session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$imported = 0;

// Check if import data exists
if (!isset($_SESSION['import_data'])) {
    $error = $lang['no_import_data'] ?? 'No import data found in session.';
} else {
    $data = $_SESSION['import_data'];

    // Flatten card event data
    $flatRecords = [];
    if (!empty($data['card_event_data_1']['card_event_records_array'])) {
        foreach ($data['card_event_data_1']['card_event_records_array'] as $block) {
            foreach ($block['card_event_records'] as $rec) {
                $flatRecords[] = [
                    'event_type' => $rec['event_type'] ?? null,
                    'timestamp' => $rec['event_begin_time'] ?? null,
                    'vehicle_number' => $rec['event_vehicle_registration']['vehicle_registration_number'] ?? '',
                    'raw_json' => $rec
                ];
            }
        }
    }

    if (!empty($flatRecords)) {
        // Create per-user table if not exists
        $tableName = "events_user_" . intval($userId);
        $createSQL = "CREATE TABLE IF NOT EXISTS `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type INT,
            timestamp DATETIME,
            vehicle_number VARCHAR(20),
            raw_json JSON
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($createSQL);

        try {
            $pdo->beginTransaction();

            $insertSQL = "INSERT INTO `$tableName` (event_type, timestamp, vehicle_number, raw_json)
                          VALUES (:event_type, :timestamp, :vehicle_number, :raw_json)";
            $insertStmt = $pdo->prepare($insertSQL);

            foreach ($flatRecords as $r) {
                // Convert ISO 8601 to MySQL DATETIME
                $ts = $r['timestamp'] ?? null;
                if ($ts) {
                    $ts = str_replace('T', ' ', $ts);
                    $ts = str_replace('Z', '', $ts);
                }

                $insertStmt->execute([
                    ':event_type' => $r['event_type'],
                    ':timestamp' => $ts,
                    ':vehicle_number' => $r['vehicle_number'],
                    ':raw_json' => json_encode($r['raw_json'])
                ]);
                $imported++;
            }

            $pdo->commit();

            // Clear import data from session
            unset($_SESSION['import_data']);

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = $lang['import_failed'] ?? 'Import failed: ' . $e->getMessage();
        }
    } else {
        $error = $lang['no_records_found'] ?? 'No records to import.';
    }
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
                        <?= sprintf($lang['import_success'] ?? 'Successfully imported %d records.', $imported) ?>
                    </div>
                <?php endif; ?>

                <a href="upload.php" class="btn btn-primary"><?= $lang['back_to_upload'] ?? 'Back to Upload' ?></a>
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

