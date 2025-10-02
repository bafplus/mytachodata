<?php
session_start();
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'rootpassword';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Please login first.";
    exit;
}

$userId = $_SESSION['user_id'];
$userDbName = "user_$userId";

$pdo = new PDO("mysql:host=$dbHost;dbname=$userDbName", $dbUser, $dbPass);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dddfile'])) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $uploadedFile = $uploadDir . basename($_FILES['dddfile']['name']);
    if (!move_uploaded_file($_FILES['dddfile']['tmp_name'], $uploadedFile)) {
        echo json_encode(['error' => 'Failed to save uploaded file']);
        exit;
    }

    // Run tachoparser
    $cmd = escapeshellcmd("dddparser -card -input " . escapeshellarg($uploadedFile) . " -format");
    $output = shell_exec($cmd);

    if (!$output) {
        echo json_encode(['error' => 'Parser returned no output']);
        exit;
    }

    $records = json_decode($output, true);
    if (!$records) {
        echo json_encode(['error' => 'Failed to decode parser JSON']);
        exit;
    }

    // Deduplicate & insert
    $new = 0;
    $duplicates = 0;
    foreach ($records as $r) {
        $hash = md5($r['start'] . $r['end'] . $r['type'] . ($r['vehicle_id'] ?? ''));

        $stmt = $pdo->prepare("SELECT 1 FROM driver_data WHERE hash = ?");
        $stmt->execute([$hash]);

        if ($stmt->fetch()) {
            $duplicates++;
            continue;
        }

        $insert = $pdo->prepare("
            INSERT INTO driver_data (start, end, type, vehicle_id, distance, duration, hash)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $r['start'],
            $r['end'],
            $r['type'],
            $r['vehicle_id'] ?? null,
            $r['distance'] ?? null,
            $r['duration'] ?? null,
            $hash
        ]);
        $new++;
    }

    echo json_encode([
        'new_records' => $new,
        'duplicates_skipped' => $duplicates
    ]);

} else {
    ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="dddfile" accept=".ddd" required>
        <button type="submit">Upload & Import</button>
    </form>
    <?php
}
