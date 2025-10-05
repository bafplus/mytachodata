<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['import_data'])) {
    header('Location: upload.php');
    exit;
}

$userId = $_SESSION['user_id'];
$data = $_SESSION['import_data'];
$error = '';
$importedRecords = 0;

// Create or connect to user's database
$userDbName = "user_$userId";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$userDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $userPdo = new PDO("mysql:host={$dbHost};dbname={$userDbName}", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Could not create/connect user database: " . $e->getMessage());
}

// Helper function to flatten nested arrays into table-friendly rows
function flattenArray($array, $prefix = '') {
    $rows = [];
    foreach ($array as $key => $value) {
        $fullKey = $prefix ? "{$prefix}_{$key}" : $key;
        if (is_array($value) && array_values($value) !== $value) {
            // associative array → flatten recursively
            $rows = array_merge($rows, flattenArray($value, $fullKey));
        } elseif (is_array($value)) {
            // numeric array → store as JSON string
            $rows[$fullKey] = json_encode($value);
        } else {
            $rows[$fullKey] = $value;
        }
    }
    return $rows;
}

try {
    foreach ($data as $tableName => $tableData) {
        // Only handle arrays of records
        if (is_array($tableData)) {
            $records = $tableData['card_event_records_array'] ?? $tableData;
            if (!is_array($records)) continue;

            $userPdo->beginTransaction();

            // Create table if it doesn't exist
            $columnsSql = [];
            foreach ($records as $recordGroup) {
                foreach ($recordGroup as $recordKey => $recordItems) {
                    foreach ($recordItems as $rec) {
                        $flat = flattenArray($rec);
                        foreach ($flat as $col => $val) {
                            $columnsSql[$col] = 'TEXT';
                        }
                    }
                }
            }
            $columnsDefs = [];
            foreach ($columnsSql as $col => $type) {
                $colSanitized = preg_replace('/[^a-z0-9_]/i', '_', $col);
                $columnsDefs[] = "`$colSanitized` $type";
            }

            $createTableSQL = "CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                " . implode(", ", $columnsDefs) . "
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $userPdo->exec($createTableSQL);

            // Insert records
            $insertedCount = 0;
            foreach ($records as $recordGroup) {
                foreach ($recordGroup as $recordKey => $recordItems) {
                    foreach ($recordItems as $rec) {
                        $flat = flattenArray($rec);
                        // Convert ISO8601 timestamps to DATETIME
                        foreach ($flat as $k => $v) {
                            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $v)) {
                                $dt = date('Y-m-d H:i:s', strtotime($v));
                                $flat[$k] = $dt;
                            }
                        }
                        $cols = implode(", ", array_map(fn($c) => "`$c`", array_keys($flat)));
                        $placeholders = implode(", ", array_fill(0, count($flat), "?"));
                        $stmt = $userPdo->prepare("INSERT INTO `$tableName` ($cols) VALUES ($placeholders)");
                        $stmt->execute(array_values($flat));
                        $insertedCount++;
                    }
                }
            }
            $userPdo->commit();
            $importedRecords += $insertedCount;
        }
    }
    echo "Import successful! Records imported: $importedRecords";
    unset($_SESSION['import_data']);
} catch (Exception $e) {
    if ($userPdo->inTransaction()) $userPdo->rollBack();
    die("Import failed: " . $e->getMessage());
}

