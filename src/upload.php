<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

// Start session
if (!isset($_SESSION)) session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$summary = [];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dddfile'])) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $uploadedFile = $uploadDir . basename($_FILES['dddfile']['name']);

    if (move_uploaded_file($_FILES['dddfile']['tmp_name'], $uploadedFile)) {
        // Run DDD parser
        $parserPath = __DIR__ . "/dddparser"; // adjust if needed
        if (!is_executable($parserPath)) {
            $error = "DDD parser not found or not executable.";
        } else {
            $cmd = escapeshellcmd("$parserPath -card -input " . escapeshellarg($uploadedFile) . " -format");
            $output = shell_exec($cmd);

            if (!$output) {
                $error = "Parser returned no output.";
            } else {
                $jsonData = json_decode($output, true);
                if (!$jsonData) {
                    $error = "Invalid JSON from parser.";
                } else {
                    $events = $jsonData['card_event_data_1']['card_event_records_array'] ?? [];
                    $recordCount = 0;
                    $firstDate = null;
                    $lastDate = null;

                    foreach ($events as $eventGroup) {
                        foreach ($eventGroup['card_event_records'] as $event) {
                            if (!empty($event['event_begin_time'])) {
                                $recordCount++;
                                $time = strtotime($event['event_begin_time']);
                                if (!$firstDate || $time < $firstDate) $firstDate = $time;
                                if (!$lastDate || $time > $lastDate) $lastDate = $time;
                            }
                        }
                    }

                    $summary = [
                        'records_found' => $recordCount,
                        'time_range' => [
                            'from' => $firstDate ? date('Y-m-d H:i:s', $firstDate) : '-',
                            'to'   => $lastDate  ? date('Y-m-d H:i:s', $lastDate)  : '-'
                        ],
                        'json_file' => $uploadedFile // store for later import
                    ];
                }
            }
        }
    } else {
        $error = "Failed to save uploaded file.";
    }
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['upload_title'] ?? 'Upload DDD File' ?></title>
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .upload-box { margin: 5% auto; width: 500px; }
    </style>
</head>
<body>

<div class="upload-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h3><?= $lang['upload_title'] ?? 'Upload DDD File' ?></h3>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label><?= $lang['choose_file'] ?? 'Choose .ddd file' ?></label>
                    <input type="file" name="dddfile" accept=".ddd" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= $lang['upload'] ?? 'Upload' ?></button>
            </form>

            <?php if (!empty($summary)): ?>
                <div class="card mt-3">
                    <div class="card-header"><?= $lang['upload_summary'] ?? 'Upload Summary' ?></div>
                    <div class="card-body">
                        <p><?= $lang['records_found'] ?? 'Records found' ?>: <?= $summary['records_found'] ?></p>
                        <p><?= $lang['time_range'] ?? 'Time range' ?>: <?= $summary['time_range']['from'] ?> - <?= $summary['time_range']['to'] ?></p>

                        <!-- Placeholder for Import button -->
                        <form method="POST" action="import.php">
                            <input type="hidden" name="json_file" value="<?= htmlspecialchars($summary['json_file']) ?>">
                            <button type="submit" class="btn btn-success"><?= $lang['import_to_db'] ?? 'Import to Database' ?></button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>


