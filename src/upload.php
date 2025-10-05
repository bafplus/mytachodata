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
$summary = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ddd_file'])) {
    if ($_FILES['ddd_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['ddd_file']['tmp_name'];

        // Run parser exactly like import_raw.php
        $cmd = escapeshellcmd("dddparser -card -input " . escapeshellarg($tmpPath) . " -format");
        $jsonOutput = shell_exec($cmd);

        if ($jsonOutput) {
            $data = json_decode($jsonOutput, true);
            if ($data === null) {
                $error = $lang['parser_invalid_json'] ?? 'Parser returned invalid JSON.';
            } else {
                $_SESSION['import_data'] = $data;

                // Safe summary generation
                $records = [];
                foreach ($data as $block) {
                    if (is_array($block)) {
                        $records = array_merge($records, array_values($block));
                    }
                }
                $recordCount = count($records);

                // Try to get timestamps safely
                $startTime = isset($records[0]['timestamp']) ? $records[0]['timestamp'] : null;
                $endTime = !empty($records) ? (isset(end($records)['timestamp']) ? end($records)['timestamp'] : null) : null;

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
    <style>
        #importProgressContainer {
            display: none;
            margin-top: 20px;
        }
    </style>
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
                        <form id="uploadForm" method="post" enctype="multipart/form-data">
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
                            <p><?= sprintf($lang['time_range'] ?? 'Time range: %s - %s', $summary['start'] ?? '-', $summary['end'] ?? '-') ?></p>
                            <button id="startImportBtn" class="btn btn-success"><?= $lang['confirm_import'] ?? 'Start Import' ?></button>

                            <div id="importProgressContainer">
                                <p>Importing... <span id="importCount">0</span> / <?= $summary['records'] ?></p>
                                <div class="progress">
                                    <div id="importProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <div id="importResult" class="mt-3"></div>
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

<script>
$(document).ready(function() {
    $('#startImportBtn').on('click', function() {
        $(this).prop('disabled', true);
        $('#importProgressContainer').show();
        $('#importResult').html('');

        $.ajax({
            url: 'import_execute.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.summary) {
                    let total = 0;
                    for(let tbl in response.summary) {
                        total += response.summary[tbl];
                    }
                    $('#importCount').text(total);
                    $('#importProgress').css('width', '100%');
                    $('#importResult').html('<div class="alert alert-success">Import complete! Records imported: ' + total + '</div>');
                } else {
                    $('#importResult').html('<div class="alert alert-danger">Import failed.</div>');
                }
            },
            error: function(xhr, status, err) {
                $('#importResult').html('<div class="alert alert-danger">Error during import: ' + err + '</div>');
            }
        });
    });
});
</script>
</body>
</html>
