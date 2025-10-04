<?php
require_once __DIR__ . '/inc/auth.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load user language
$lang_file = __DIR__ . "/lang/" . ($_SESSION['language'] ?? 'en') . ".php";
$lang = file_exists($lang_file) ? include $lang_file : [];

?>

<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0"><?= htmlspecialchars($lang['dashboard'] ?? 'Dashboard') ?></h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="alert alert-success">
                <?= htmlspecialchars($lang['welcome'] ?? 'Welcome') ?>, <?= htmlspecialchars($_SESSION['username']) ?>!
            </div>

            <p><?= htmlspecialchars($lang['dashboard_info'] ?? 'This is your MyTacho dashboard skeleton.') ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
