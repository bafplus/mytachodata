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
$error = '';
$success = '';

// Check if parsed data exists in session
if (empty($_SESSION['import_data'])) {
    $error = $lang['no_import_data'] ?? 'No parsed data found. Please upload a file first.';
} else {
    $data = $_SESSION['import_data'];

    try {
        $pdo->beginTransaction();

        // Optional: create a user-specific table (if you want full separation)
        // Example: user_123_events
        $tableName = "user_{$userId}_events";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type INT,
                event_begin_time DATETIME NULL,
                event_end_time DATETIME NULL,
                vehicle_registration_nation INT,
                vehicle_registration_number VARCHAR(20)
            )
        ");

        // Insert all records from parsed data
        $insertStmt = $pdo->prepare("
            INSERT INTO `$tableName` 
            (event_type, event_begin_time, event_end_time, vehicle_registration_nation, vehicle_registration_number) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $records = $data['records'] ?? [];

        foreach ($records as $record) {
            $insertStmt->execute([
                $record['event_type'] ?? 0,
                $record['event_begin_time'] ?? null,
                $record['event_end_time'] ?? null,
                $record['event_vehicle_registration']['vehicle_registration_nation'] ?? 0,
                $record['event_vehicle_registration']['vehicle_registration_number'] ?? ''
            ]);
        }

        $pdo->commit();
        $success = sprintf($lang['import_success'] ?? 'Successfully imported %d records.', count($records));

        // Clear session data after import
        unset($_SESSION['import_data']);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $lang['import_failed'] ?? 'Import failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['import_execute'] ?? 'Execute Import' ?></title>
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
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <a href="upload.php" class="btn btn-primary mt-2"><?= $lang['upload_another'] ?? 'Upload Another File' ?></a>
                <?php endif; ?>
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
