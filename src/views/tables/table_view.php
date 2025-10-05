<?php
require_once __DIR__ . '/../../inc/db.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
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

$table = $_GET['table'] ?? '';
if (!$table) die("No table specified.");

// Sanitize table name
$table = preg_replace('/[^a-z0-9_]/i', '_', $table);

// Connect to user DB
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

// Pagination
$perPage = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Count total rows
try {
    $totalRows = $userPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
} catch (PDOException $e) {
    die("Table not found or error: " . htmlspecialchars($e->getMessage()));
}

// Fetch page rows
$stmt = $userPdo->prepare("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT :offset, :limit");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../inc/header.php';
require_once __DIR__ . '/../../inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Table: <?= htmlspecialchars($table) ?></h1>
            <p>Total rows: <?= intval($totalRows) ?></p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <?php if (!empty($rows)) {
                            foreach (array_keys($rows[0]) as $col) {
                                echo "<th>" . htmlspecialchars($col) . "</th>";
                            }
                        } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= is_array($cell) || is_object($cell) ? htmlspecialchars(json_encode($cell)) : htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <?php
                    $totalPages = ceil($totalRows / $perPage);
                    for ($p = 1; $p <= $totalPages; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

            <a href="../../index.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../inc/footer.php'; ?>
