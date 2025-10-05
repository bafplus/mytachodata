<?php
// import_execute.php - deduplicated version

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

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

// --- DB credentials ---
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$dbMain = getenv('DB_NAME') ?: 'mytacho';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

if (isset($pdo) && $pdo instanceof PDO) {
    $adminPdo = $pdo;
} else {
    try {
        $adminPdo = new PDO("mysql:host={$dbHost};dbname={$dbMain};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    } catch (PDOException $e) {
        die("Could not connect to main database: " . htmlspecialchars($e->getMessage()));
    }
}

// --- Utility functions ---
function safe_table_name(string $name): string {
    $n = preg_replace('/[^a-z0-9_]/i', '_', $name);
    $n = preg_replace('/_{2,}/', '_', $n);
    $n = trim($n, '_');
    if ($n === '') $n = 'table';
    if (preg_match('/^[0-9]/', $n)) $n = 't_' . $n;
    return strtolower($n);
}

function find_timestamp($rec) {
    if (!is_array($rec)) return null;
    $candidates = [
        'event_begin_time','event_end_time','timestamp','start_time','first_use','last_use',
        'issue_date','expiry_date','date','activity_date','record_time','time'
    ];
    foreach ($candidates as $k) {
        if (!empty($rec[$k]) && is_string($rec[$k])) {
            $t = strtotime($rec[$k]);
            if ($t !== false) return date('Y-m-d H:i:s', $t);
        }
    }
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($rec));
    foreach ($it as $v) {
        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $v)) {
            $t = strtotime($v);
            if ($t !== false) return date('Y-m-d H:i:s', $t);
        }
    }
    return null;
}

function find_label($rec) {
    if (!is_array($rec)) return null;
    $candidates = ['event_type','activity_type','control_type','registration','vehicle_registration_number','card_number','type','name','reason'];
    foreach ($candidates as $k) {
        if (!empty($rec[$k]) && !is_array($rec[$k])) {
            return (string)$rec[$k];
        }
    }
    return null;
}

// --- User DB setup ---
$userDbName = "mytacho_user_" . $userId;
try {
    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$userDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die("Failed creating user database: " . htmlspecialchars($e->getMessage()));
}

try {
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Failed connecting to user DB: " . htmlspecialchars($e->getMessage()));
}

$summary = [];

// --- Process each block ---
foreach ($data as $topKey => $block) {
    $table = safe_table_name($topKey);

    // create table with unique hash to prevent duplicates
    $createSql = "
        CREATE TABLE IF NOT EXISTS `{$table}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `timestamp` DATETIME NULL,
            `label` VARCHAR(255) NULL,
            `raw` JSON NULL,
            `hash` CHAR(32) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_hash` (`hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $userPdo->exec($createSql);

    // flatten items
    $items = [];
    if (is_array($block)) {
        if (isset($block['card_event_records_array']) && is_array($block['card_event_records_array'])) {
            foreach ($block['card_event_records_array'] as $group) {
                if (isset($group['card_event_records']) && is_array($group['card_event_records'])) {
                    foreach ($group['card_event_records'] as $rec) $items[] = $rec;
                } else {
                    foreach ($group as $maybeRec) if (is_array($maybeRec)) $items[] = $maybeRec;
                }
            }
        } elseif (array_values($block) === $block) {
            foreach ($block as $rec) $items[] = $rec;
        } else {
            $found = false;
            foreach ($block as $k => $v) {
                if (is_array($v) && array_values($v) === $v) {
                    foreach ($v as $rec) $items[] = $rec;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] = $block;
        }
    } else {
        $items[] = ['value' => $block];
    }

    // insert with deduplication
    $inserted = 0;
    if (!empty($items)) {
        $userPdo->beginTransaction();
        try {
            $insertStmt = $userPdo->prepare("INSERT IGNORE INTO `{$table}` (`timestamp`,`label`,`raw`,`hash`) VALUES (:timestamp,:label,:raw,:hash)");
            foreach ($items as $rec) {
                $ts = find_timestamp($rec);
                $label = find_label($rec);
                $raw = json_encode($rec, JSON_UNESCAPED_UNICODE);
                $hash = md5($raw);

                $insertStmt->execute([
                    ':timestamp' => $ts,
                    ':label' => $label,
                    ':raw' => $raw,
                    ':hash' => $hash
                ]);
                $inserted += $insertStmt->rowCount();
            }
            $userPdo->commit();
        } catch (PDOException $e) {
            if ($userPdo->inTransaction()) $userPdo->rollBack();
            $summary[$table] = "failed: " . $e->getMessage();
            continue;
        }
    }

    $summary[$table] = $inserted;
}

unset($_SESSION['import_data']);

// --- Output summary ---
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <title>Import result</title>
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
<?php foreach ($summary as $tbl => $cnt): ?>
              <li><strong><?= htmlspecialchars($tbl) ?></strong>: <?= is_int($cnt) ? intval($cnt) . " rows inserted" : htmlspecialchars($cnt) ?></li>
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
