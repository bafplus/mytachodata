<?php
// import_execute.php (robust: uses inc/db.php, env fallbacks, forces TCP, creates per-user DB)
require_once __DIR__ . '/inc/db.php';   // expected to set $pdo (optional)
require_once __DIR__ . '/inc/lang.php';  // optional translations

if (!isset($_SESSION)) session_start();

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$data = $_SESSION['import_data'] ?? null;
if (!$data) {
    die("No parsed import data found in session. Please upload a DDD file first.");
}

// --- determine DB connection settings (use inc/db.php variables if present, else env, else defaults) ---
$dbHost    = $dbHost    ?? $DB_HOST    ?? getenv('DB_HOST')    ?? '127.0.0.1'; // force TCP
$dbUser    = $dbUser    ?? $DB_USER    ?? getenv('DB_USER')    ?? 'mytacho_user';
$dbPass    = $dbPass    ?? $DB_PASS    ?? getenv('DB_PASS')    ?? 'mytacho_pass';
$dbMain    = $dbMain    ?? $DB_NAME    ?? getenv('DB_NAME')    ?? 'mytacho';

$pdoOptions = $pdoOptions ?? [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// --- admin/main PDO: prefer existing $pdo from inc/db.php (connected to main DB) ---
if (isset($pdo) && $pdo instanceof PDO) {
    $adminPdo = $pdo;
} else {
    try {
        $adminPdo = new PDO(
            "mysql:host={$dbHost};dbname={$dbMain};charset=utf8mb4",
            $dbUser,
            $dbPass,
            $pdoOptions
        );
    } catch (PDOException $e) {
        die("Could not connect to main DB (admin): " . htmlspecialchars($e->getMessage()));
    }
}

try {
    // 1) create per-user database if not exists
    $userDbName = "mytacho_user_" . $userId;
    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$userDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2) connect to per-user database using TCP to avoid socket issues
    try {
        $userPdo = new PDO(
            "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            $pdoOptions
        );
        $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Failed connecting to per-user DB `{$userDbName}`: " . $e->getMessage());
    }

    // 3) create normalized tables (driver_cards, driver_activity, vehicles_used, events_faults)
    $userPdo->exec("
        CREATE TABLE IF NOT EXISTS driver_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_number VARCHAR(64),
            chip_number VARCHAR(64),
            issue_date DATETIME NULL,
            expiry_date DATETIME NULL,
            issuing_authority VARCHAR(128),
            driver_name VARCHAR(128),
            driver_birthdate DATE NULL,
            driver_licence_number VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $userPdo->exec("
        CREATE TABLE IF NOT EXISTS driver_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            start_time DATETIME,
            end_time DATETIME,
            activity_type VARCHAR(32),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $userPdo->exec("
        CREATE TABLE IF NOT EXISTS vehicles_used (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            registration_number VARCHAR(32),
            start_time DATETIME,
            end_time DATETIME,
            odometer_start INT NULL,
            odometer_end INT NULL,
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $userPdo->exec("
        CREATE TABLE IF NOT EXISTS events_faults (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            timestamp DATETIME,
            event_type VARCHAR(64),
            severity VARCHAR(16),
            description TEXT,
            raw JSON NULL,
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4) Insert driver card static info (best-effort extraction from parsed JSON)
    $insertCard = $userPdo->prepare("
        INSERT INTO driver_cards
        (card_number, chip_number, issue_date, expiry_date, issuing_authority, driver_name, driver_birthdate, driver_licence_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // try a few known key paths (your parser's keys might differ; adjust if needed)
    $cardNumber = $data['card_icc_identification_1']['card_extended_serial_number']['serial_number'] ?? ($data['card_identification_and_driver_card_holder_identification_1']['card_number'] ?? null);
    $chipNumber = $data['card_chip_identification_1']['ic_serial_number'][0] ?? null; // best-effort (may need adjustment)
    // driver card app / identification often has dates in driver_card_application_identification_*
    // fallback to searching
    $issueDate = $data['card_identification_and_driver_card_holder_identification_1']['card_issue_date'] ?? null;
    $expiryDate = $data['card_identification_and_driver_card_holder_identification_1']['card_expiry_date'] ?? null;
    $issuer = $data['card_identification_and_driver_card_holder_identification_1']['issuing_member_state'] ?? null;
    $driverName = $data['card_identification_and_driver_card_holder_identification_1']['holder_name'] ?? ($data['card_identification_and_driver_card_holder_identification_1']['driver_name'] ?? null);
    $birthdate = $data['card_identification_and_driver_card_holder_identification_1']['birth_date'] ?? null;
    $licence = $data['card_driving_licence_information_1']['driving_licence_number'] ?? null;

    // Normalise date strings to MySQL format if present
    $toDateTime = function($v) {
        if (empty($v)) return null;
        $t = strtotime($v);
        return $t === false ? null : date('Y-m-d H:i:s', $t);
    };
    $toDate = function($v) {
        if (empty($v)) return null;
        $t = strtotime($v);
        return $t === false ? null : date('Y-m-d', $t);
    };

    $insertCard->execute([
        $cardNumber,
        is_array($chipNumber) ? json_encode($chipNumber) : $chipNumber,
        $toDateTime($issueDate),
        $toDateTime($expiryDate),
        $issuer,
        $driverName,
        $toDate($birthdate),
        $licence
    ]);
    $cardId = $userPdo->lastInsertId();

    $summary = [
        'driver_cards' => ($cardId ? 1 : 0),
        'driver_activity' => 0,
        'vehicles_used' => 0,
        'events_faults' => 0
    ];

    // 5) Import driver activity (common locations: card_driver_activity_1 / card_driver_activity_2)
    $activityBlocks = [];
    if (!empty($data['card_driver_activity_1'])) $activityBlocks[] = $data['card_driver_activity_1'];
    if (!empty($data['card_driver_activity_2'])) $activityBlocks[] = $data['card_driver_activity_2'];

    if (!empty($activityBlocks)) {
        $insAct = $userPdo->prepare("INSERT INTO driver_activity (card_id, start_time, end_time, activity_type) VALUES (?, ?, ?, ?)");
        foreach ($activityBlocks as $block) {
            // block might be an array of groups or direct records
            if (array_values($block) === $block) {
                foreach ($block as $rec) {
                    // rec field names differ between parsers; try common keys
                    $start = $rec['activity_start_time'] ?? $rec['start_time'] ?? $rec['event_begin_time'] ?? $rec['Start'] ?? null;
                    $end   = $rec['activity_end_time']   ?? $rec['end_time']   ?? $rec['event_end_time'] ?? $rec['End'] ?? null;
                    $type  = $rec['activity_type'] ?? $rec['Activity'] ?? $rec['activity_code'] ?? null;
                    if (empty($start) && empty($end)) continue;
                    $insAct->execute([$cardId, $toDateTime($start), $toDateTime($end), $type]);
                    $summary['driver_activity']++;
                }
            }
        }
    }

    // 6) Import vehicles used
    $vehiclesBlocks = [];
    if (!empty($data['card_vehicles_used_1'])) $vehiclesBlocks[] = $data['card_vehicles_used_1'];
    if (!empty($data['card_vehicles_used_2'])) $vehiclesBlocks[] = $data['card_vehicles_used_2'];

    if (!empty($vehiclesBlocks)) {
        $insVeh = $userPdo->prepare("INSERT INTO vehicles_used (card_id, registration_number, start_time, end_time, odometer_start, odometer_end) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($vehiclesBlocks as $block) {
            if (array_values($block) === $block) {
                foreach ($block as $rec) {
                    $reg = $rec['vehicle_registration_number'] ?? $rec['registration'] ?? $rec['vehicle_registration'] ?? null;
                    $start = $rec['start_time'] ?? $rec['first_use'] ?? $rec['Start'] ?? null;
                    $end = $rec['end_time'] ?? $rec['last_use'] ?? $rec['End'] ?? null;
                    $odoBegin = $rec['odometer_begin'] ?? $rec['odometer_start'] ?? $rec['odometer'] ?? null;
                    $odoEnd = $rec['odometer_end'] ?? $rec['odometer_finish'] ?? null;
                    if (empty($reg) && empty($start) && empty($end)) continue;
                    $insVeh->execute([$cardId, $reg, $toDateTime($start), $toDateTime($end), $odoBegin, $odoEnd]);
                    $summary['vehicles_used']++;
                }
            }
        }
    }

    // 7) Import events/faults (card_event_data_1, card_event_data_2, card_fault_data_*)
    $eventTables = [
        'card_event_data_1',
        'card_event_data_2',
        'card_fault_data_1',
        'card_fault_data_2'
    ];
    $insEvent = $userPdo->prepare("INSERT INTO events_faults (card_id, timestamp, event_type, severity, description, raw) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($eventTables as $evk) {
        if (!empty($data[$evk])) {
            // some blocks are nested arrays of groups with 'card_event_records_array'
            $block = $data[$evk];
            // case: structured nested -> card_event_records_array
            if (isset($block['card_event_records_array'])) {
                foreach ($block['card_event_records_array'] as $group) {
                    if (!empty($group['card_event_records']) && is_array($group['card_event_records'])) {
                        foreach ($group['card_event_records'] as $rec) {
                            $ts = $rec['event_begin_time'] ?? $rec['timestamp'] ?? null;
                            $type = $rec['event_type'] ?? null;
                            $desc = $rec['description'] ?? null;
                            $sev  = $rec['severity'] ?? null;
                            $insEvent->execute([$cardId, $toDateTime($ts), $type, $sev, $desc, json_encode($rec, JSON_UNESCAPED_UNICODE)]);
                            $summary['events_faults']++;
                        }
                    }
                }
            } elseif (array_values($block) === $block) {
                // direct list of events
                foreach ($block as $rec) {
                    $ts = $rec['event_begin_time'] ?? $rec['timestamp'] ?? null;
                    $type = $rec['event_type'] ?? null;
                    $desc = $rec['description'] ?? null;
                    $sev  = $rec['severity'] ?? null;
                    $insEvent->execute([$cardId, $toDateTime($ts), $type, $sev, $desc, json_encode($rec, JSON_UNESCAPED_UNICODE)]);
                    $summary['events_faults']++;
                }
            }
        }
    }

    // done: cleanup session import
    unset($_SESSION['import_data']);

    // simple HTML summary
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8"/>
      <title>Import finished</title>
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
            <div class="card card-primary">
              <div class="card-header"><h3 class="card-title">Import summary</h3></div>
              <div class="card-body">
                <ul>
                  <?php foreach ($summary as $k => $v): ?>
                    <li><strong><?= htmlspecialchars($k) ?></strong>: <?= intval($v) ?> rows</li>
                  <?php endforeach; ?>
                </ul>
                <a href="upload.php" class="btn btn-primary">Back to Upload</a>
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
    </body>
    </html>
    <?php
    exit;

} catch (Exception $e) {
    // show a clear error
    $msg = $e->getMessage();
    die("Import failed: " . htmlspecialchars($msg));
}
