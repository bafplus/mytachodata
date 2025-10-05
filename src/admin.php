<?php
session_start();
require_once __DIR__ . '/inc/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
$stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUsername = $userData['username'];

// Handle save for site settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_name'])) {
    $site_name = $_POST['site_name'];
    $support_email = $_POST['support_email'];

    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES ('site_name', :site_name), ('support_email', :support_email)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([':site_name' => $site_name, ':support_email' => $support_email]);
    header("Location: admin.php?saved=1");
    exit;
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $deleteUserId = (int)$_GET['delete_user'];
    if ($deleteUserId !== $currentUserId) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteUserId]);
        header("Location: admin.php?user_deleted=1");
        exit;
    } else {
        $error = $lang['cannot_delete_self'] ?? 'You cannot delete yourself.';
    }
}

// Load settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$site_name = $settings['site_name'] ?? 'MyTacho';
$support_email = $settings['support_email'] ?? 'support@mytacho.com';

// Load all users
$stmt = $pdo->query("SELECT id, username, role, language, created_at FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $lang['admin_settings'] ?? 'Admin Settings' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <?php require_once __DIR__ . '/inc/header.php'; ?>
  <?php require_once __DIR__ . '/inc/sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1><?= $lang['admin_settings'] ?? 'Admin Settings' ?></h1>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <?php if (isset($_GET['saved'])): ?>
          <div class="alert alert-success"><?= $lang['settings_saved'] ?? 'Settings saved successfully.' ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['user_deleted'])): ?>
          <div class="alert alert-success"><?= $lang['user_deleted'] ?? 'User deleted successfully.' ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Site Settings Form -->
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"><?= $lang['site_settings'] ?? 'Site Settings' ?></h3>
          </div>
          <form method="post">
            <div class="card-body">
              <div class="form-group">
                <label><?= $lang['site_name'] ?? 'Site Name' ?></label>
                <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($site_name) ?>">
              </div>
              <div class="form-group">
                <label><?= $lang['support_email'] ?? 'Support Email' ?></label>
                <input type="email" class="form-control" name="support_email" value="<?= htmlspecialchars($support_email) ?>">
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary"><?= $lang['save'] ?? 'Save' ?></button>
            </div>
          </form>
        </div>

        <!-- Users Overview Table -->
        <div class="card card-secondary mt-4">
          <div class="card-header">
            <h3 class="card-title"><?= $lang['user_overview'] ?? 'Users Overview' ?></h3>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th><?= $lang['id'] ?? 'ID' ?></th>
                  <th><?= $lang['username'] ?? 'Username' ?></th>
                  <th><?= $lang['role'] ?? 'Role' ?></th>
                  <th><?= $lang['language'] ?? 'Language' ?></th>
                  <th><?= $lang['created_at'] ?? 'Created At' ?></th>
                  <th><?= $lang['actions'] ?? 'Actions' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['language']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                      <?php if ($user['id'] !== $currentUserId): ?>
                        <a href="admin.php?delete_user=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= $lang['confirm_delete_user'] ?? 'Are you sure you want to delete this user?' ?>')"><?= $lang['delete'] ?? 'Delete' ?></a>
                      <?php else: ?>
                        <span class="text-muted"><?= $lang['cannot_delete_self'] ?? 'Protected' ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </section>
  </div>

  <?php require_once __DIR__ . '/inc/footer.php'; ?>

</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
