<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php'; // optional for translations

// Start session
if (!isset($_SESSION)) session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$summary = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ddd_file'])) {
    $file = $_FILES['ddd_file'];
    
    // Basic validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = $lang['file_upload_error'] ?? 'Error uploading file.';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'ddd') {
        $error = $lang['invalid_file_type'] ?? 'Invalid file type, must be .ddd';
    } else {
        // Move to a temporary location
        $tmpPath = sys_get_temp_dir() . '/' . uniqid('ddd_') . '.ddd';
        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $error = $lang['file_move_error'] ?? 'Failed to process uploaded file.';
        } else {
            // Run the parser binary and capture JSON
            $parserBinary = __DIR__ . '/bin/dddparser'; // adjust if needed
            $cmd = escapeshellcmd("$parserBinary " . escapeshellarg($tmpPath));
            $output = shell_exec($cmd);
            
            if (!$output) {
                $error = $lang['parser_error'] ?? 'Failed to parse file.';
            } else {
                $json = json_decode($output, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = $lang['invalid_json'] ?? 'Parser output invalid JSON.';
                } else {
                    // Prepare a summary for preview
                    $summary = [
                        'filename' => $file['name'],
                        'total_records' => count($json['records'] ?? []),
                        'start_time' => $json['start_time'] ?? null,
                        'end_time' => $json['end_time'] ?? null,
                        'vehicle_info' => $json['vehicle'] ?? [],
                    ];
                    
                    // Store parsed JSON in session for actual import step
                    $_SESSION['import_data'] = $json;
                }
            }
            
            // Delete temp file
            unlink($tmpPath);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['import_title'] ?? 'Import DDD File' ?></title>
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php require_once __DIR__ . '/inc/header.php'; ?>
    <?php require_once __DIR__ . '/inc/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1><?= $lang['import_title'] ?? 'Import DDD File' ?></h1>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= $lang['upload_file'] ?? 'Upload DDD File' ?></h3>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="form-group">
                                <input type="file" name="ddd_file" accept=".ddd" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><?= $lang['parse_preview'] ?? 'Parse & Preview' ?></button>
                        </div>
                    </form>
                </div>

                <?php if ($summary): ?>
                    <div class="card card-info mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><?= $lang['summary'] ?? 'Parsed File Summary' ?></h3>
                        </div>
                        <div class="card-body">
                            <p><strong><?= $lang['filename'] ?? 'File' ?>:</strong> <?= htmlspecialchars($summary['filename']) ?></p>
                            <p><strong><?= $lang['total_records'] ?? 'Total Records' ?>:</strong> <?= $summary['total_records'] ?></p>
                            <p><strong><?= $lang['start_time'] ?? 'Start Time' ?>:</strong> <?= htmlspecialchars($summary['start_time']) ?></p>
                            <p><strong><?= $lang['end_time'] ?? 'End Time' ?>:</strong> <?= htmlspecialchars($summary['end_time']) ?></p>
                            <p><strong><?= $lang['vehicle_info'] ?? 'Vehicle Info' ?>:</strong> <?= htmlspecialchars(json_encode($summary['vehicle_info'])) ?></p>

                            <form method="post" action="import_execute.php">
                                <button type="submit" class="btn btn-success"><?= $lang['import_confirm'] ?? 'Import to Database' ?></button>
                            </form>
                        </div>
                    </div>
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

