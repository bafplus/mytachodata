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
$summary = null;

// Handle file upload via standard POST (fallback for non-JS)
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

                $records = $data['records'] ?? [];
                $recordCount = count($records);
                $startTime = $records[0]['timestamp'] ?? null;
                $endTime = !empty($records) ? end($records)['timestamp'] : null;

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
                        <form id="dddUploadForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label><?= $lang['choose_file'] ?? 'Choose .ddd file' ?></label>
                                <input type="file" name="ddd_file" class="form-control" accept=".ddd" required id="dddFileInput">
                            </div>
                            <button type="button" class="btn btn-primary" id="uploadBtn"><?= $lang['upload_preview'] ?? 'Upload & Preview' ?></button>
                        </form>

                        <!-- Progress bar -->
                        <div class="progress mt-3" style="height:25px; display:none;" id="uploadProgressContainer">
                            <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width:0%">0%</div>
                        </div>

                        <div id="importResult" class="mt-3"></div>

                        <?php if ($summary): ?>
                            <div class="card card-success mt-4">
                                <div class="card-header">
                                    <h3 class="card-title"><?= $lang['upload_summary'] ?? 'Upload Summary' ?></h3>
                                </div>
                                <div class="card-body">
                                    <p><?= sprintf($lang['records_found'] ?? 'Records found: %d', $summary['records']) ?></p>
                                    <p><?= sprintf($lang['time_range'] ?? 'Time range: %s - %s', $summary['start'] ?? '-', $summary['end'] ?? '-') ?></p>
                                    <a href="import_execute.php" class="btn btn-success"><?= $lang['confirm_import'] ?? 'Confirm Import' ?></a>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
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
    $('#uploadBtn').click(function() {
        let fileInput = $('#dddFileInput')[0];
        if (!fileInput.files.length) {
            alert('Please choose a .ddd file.');
            return;
        }

        let formData = new FormData();
        formData.append('ddd_file', fileInput.files[0]);

        $('#uploadProgressContainer').show();
        $('#uploadProgressBar').css('width','0%').text('0%');
        $('#importResult').html('');

        // AJAX upload to upload.php
        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        let percent = Math.round((evt.loaded / evt.total) * 100);
                        $('#uploadProgressBar').css('width', percent+'%').text(percent+'%');
                    }
                }, false);
                return xhr;
            },
            success: function(responseHtml) {
                $('#uploadProgressBar').css('width','100%').text('Analyzing...');
                // Trigger import_execute.php via AJAX
                $.ajax({
                    url: 'import_execute.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            $('#importResult').html('<div class="alert alert-danger">'+data.error+'</div>');
                        } else {
                            let html = '<div class="alert alert-success"><strong>Import summary:</strong><ul>';
                            for (let tbl in data.summary) {
                                html += '<li><strong>'+tbl+'</strong>: '+data.summary[tbl]+' rows</li>';
                            }
                            html += '</ul></div>';
                            $('#importResult').html(html);
                        }
                        $('#uploadProgressBar').css('width','100%').text('Done');
                    },
                    error: function(xhr, status, err) {
                        $('#importResult').html('<div class="alert alert-danger">Import error: '+err+'</div>');
                    }
                });
            },
            error: function(xhr, status, err) {
                $('#importResult').html('<div class="alert alert-danger">Upload error: '+err+'</div>');
            }
        });
    });
});
</script>
</body>
</html>
