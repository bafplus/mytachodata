<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php'; // optional for translations

// Start session
if (!isset($_SESSION)) session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Ensure we have data to import
if (empty($_SESSION['import_data'])) {
    $error = $lang['no_import_data'] ?? 'No data to import. Please upload a file first.';
} else {
    $data = $_SESSION['import_data'];

    // Optional: create a user-specific table if not exists
    $userTable = "user_data_" . (int)$userId;
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `$userTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type INT,
            timestamp DATETIME,
            vehicle_number VARCHAR(50),
            additional_json JSON
        ) ENGINE=InnoDB
    ");

    try {
        $pdo->beginTransaction();

        // Insert each record
        $stmt = $pdo->prepare("
            INSERT INTO `$userTable` (event_type, timestamp, vehicle_number, additional_json)
            VALUES (:event_type, :timestamp, :vehicle_number, :additional_json)
        ");

        $records = $data['records'] ?? [];

        foreach ($records as $rec) {
            $stmt->execute([
                ':event_type' => $rec['event_type'] ?? null,
                ':timestamp' => $rec['timestamp'] ?? null,
                ':vehicle_number' => $rec['vehicle_number'] ?? null,
                ':additional_json' => json_encode($rec)
            ]);
        }

        $pdo->commit();

        $success = sprintf(
            $lang['import_success'] ?? 'Successfully imported %d records.',
            count($records)
        );

        // Clear session import data
        unset($_SESSION['import_data']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $lang['import_failed'] ?? 'Import failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['import_execute_title'] ?? 'Execute Import' ?></title>
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
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <a href="upload.php" class="btn btn-primary"><?= $lang['back_to_upload'] ?? 'Back to Upload' ?></a>
                <?php else: ?>
                    <a href="upload.php" class="btn btn-secondary"><?= $lang['back_to_upload'] ?? 'Back to Upload' ?></a>
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
