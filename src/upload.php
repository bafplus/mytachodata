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
$summary = null;

// Recursive flatten function to count records
function countRecords($data) {
    $count = 0;
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (isset($value['card_event_records_array'])) {
                foreach ($value['card_event_records_array'] as $group) {
                    foreach ($group as $items) {
                        $count += count($items);
                    }
                }
            } elseif (array_values($value) !== $value) {
                $count += countRecords($value); // associative array
            } else {
                $count += count($value); // numeric array
            }
        }
    }
    return $count;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ddd_file'])) {
    if ($_FILES['ddd_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['ddd_file']['tmp_name'];

        // Run parser like import_raw.php
        $cmd = escapeshellcmd("dddparser -card -input " . escapeshellarg($tmpPath) . " -format");
        $jsonOutput = shell_exec($cmd);

        if ($jsonOutput) {
            $data = json_decode($jsonOutput, true);
            if ($data === null) {
                $error = $lang['parser_invalid_json'] ?? 'Parser returned invalid JSON.';
            } else {
                $_SESSION['import_data'] = $data;

                // Count all records in JSON
                $recordCount = countRecords($data);

                // Determine earliest and latest timestamp if exists
                $timestamps = [];
                $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
                foreach ($iterator as $key => $val) {
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $val)) {
                        $timestamps[] = $val;
                    }
                }
                sort($timestamps);
                $startTime = $timestamps[0] ?? null;
                $endTime = !empty($timestamps) ? end($timestamps) : null;

                $summary = [
                    'records' => $recordCount,
                    'start' => $startTime,
                    'end' => $endTime
                ];
            }
        } else {
            $error = $lang['parser_failed'] ?? 'Parser execution failed or returned no output.';
        }
    } else {
        $error = $lang['file_upload_error'] ?? 'File upload failed.';
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
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= $lang['upload_title'] ?? 'Upload DDD File' ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label><?= $lang['choose_file'] ?? 'Choose .ddd file' ?></label>
                                <input type="file" name="ddd_file" class="form-control" accept=".ddd" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= $lang['upload_preview'] ?? 'Upload & Preview' ?></button>
                        </form>
                    </div>
                </div>

                <?php if ($summary): ?>
                    <div class="card card-success mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><?= $lang['upload_summary'] ?? 'Upload Summary' ?></h3>
                        </div>
                        <div class="card-body">
                            <p><?= sprintf($lang['records_found'] ?? 'Records found: %d', $summary['records']) ?></p>
                            <p><?= sprintf(
                                $lang['time_range'] ?? 'Time range: %s - %s',
                                $summary['start'] ?? '-',
                                $summary['end'] ?? '-'
                            ) ?></p>
                            <a href="import_execute.php" class="btn btn-success"><?= $lang['confirm_import'] ?? 'Confirm Import' ?></a>
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
