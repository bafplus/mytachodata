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

// --- Check current driver info ---
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$userDbName = "mytacho_user_" . intval($userId);
$currentDriver = null;

try {
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // Fetch driver name if exists
    $stmt = $userPdo->query("SELECT first_name, last_name FROM driver_info LIMIT 1");
    $driver = $stmt->fetch();
    if ($driver) {
        $currentDriver = strtoupper($driver['first_name'] . ' ' . $driver['last_name']);
    }
} catch (PDOException $e) {
    // Ignore — table may not exist yet
}

// Handle file upload via standard POST (fallback for non-JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ddd_file'])) {
    if ($_FILES['ddd_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['ddd_file']['tmp_name'];

        // Run parser
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

                <!-- Current driver info -->
                <?php if ($currentDriver): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-id-card"></i>
                        This account is currently linked to driver:
                        <strong><?= htmlspecialchars($currentDriver) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No driver data imported yet. The next upload will set the driver for this account.
                    </div>
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

        // Fake progress
        let progress = 0;
        let fakeInterval = setInterval(() => {
            if (progress < 90) { 
                progress += Math.floor(Math.random() * 5) + 1;
                if (progress > 90) progress = 90;
                $('#uploadProgressBar').css('width', progress+'%').text(progress+'%');
            }
        }, 300);

        // AJAX upload to upload.php
        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(responseHtml) {
                // After upload, trigger import_execute.php
                $.ajax({
                    url: 'import_execute.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        clearInterval(fakeInterval); // stop fake progress now
                        $('#uploadProgressBar').animate({ width: '100%' }, 300, function() {
                            $('#uploadProgressBar').text('Done');
                        });

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
                    },
                    error: function(xhr, status, err) {
                        clearInterval(fakeInterval);
                        $('#uploadProgressBar').css('width','0%').text('0%');
                        $('#importResult').html('<div class="alert alert-danger">Import error: '+err+'</div>');
                    }
                });
            },
            error: function(xhr, status, err) {
                clearInterval(fakeInterval);
                $('#uploadProgressBar').css('width','0%').text('0%');
                $('#importResult').html('<div class="alert alert-danger">Upload error: '+err+'</div>');
            }
        });
    });
});
</script>
</body>
</html>
