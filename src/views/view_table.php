<?php
require_once __DIR__ . '/../inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// Get table from query
$table = $_GET['table'] ?? '';
if (!$table) {
    die("No table specified.");
}
$table = preg_replace('/[^a-z0-9_]/i', '_', $table);

// Pagination settings
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Total rows
try {
    $totalRows = $userPdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
} catch (PDOException $e) {
    die("Table does not exist or cannot be read.");
}

// Fetch rows
$stmt = $userPdo->prepare("SELECT * FROM `$table` ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$totalPages = ceil($totalRows / $perPage);

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Table: <?= htmlspecialchars($table) ?></h1>
            <p>Total rows: <?= $totalRows ?></p>
            <a href="../index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (empty($rows)): ?>
                <div class="alert alert-info">No data available in this table.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($rows[0]) as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td>
                                            <?php
                                            // Pretty-print JSON if cell is JSON
                                            if ($cell && is_string($cell) && (substr($cell,0,1)=='{' || substr($cell,0,1)=='[')) {
                                                $jsonPretty = json_encode(json_decode($cell, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                                echo '<pre style="max-width:300px;white-space:pre-wrap;">' . htmlspecialchars($jsonPretty) . '</pre>';
                                            } else {
                                                echo htmlspecialchars($cell);
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $p ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
