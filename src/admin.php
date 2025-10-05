<?php
session_start();
require_once __DIR__ . '/inc/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        // Checkbox fields are only set when checked
        if (in_array($key, ['maintenance_mode', 'allow_registration'])) {
            $value = isset($_POST[$key]) ? '1' : '0';
        }
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
    header("Location: admin.php?saved=1");
    exit;
}

// Load settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Default values
$site_name = $settings['site_name'] ?? 'MyTacho';
$default_language = $settings['default_language'] ?? 'en';
$maintenance_mode = $settings['maintenance_mode'] ?? '0';
$allow_registration = $settings['allow_registration'] ?? '1';
$support_email = $settings['support_email'] ?? 'support@mytacho.com';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $lang['admin_settings'] ?? 'Admin Settings' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <?php include "/inc/header.php"; ?>
  <?php include "/inc/sidebar.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1><?= $lang['admin_settings'] ?? 'Admin Settings' ?></h1>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <?php if (isset($_GET['saved'])): ?>
          <div class="alert alert-success">
            <?= $lang['settings_saved'] ?? 'Settings saved successfully.' ?>
          </div>
        <?php endif; ?>

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
                <label><?= $lang['default_language'] ?? 'Default Language' ?></label>
                <select class="form-control" name="default_language">
                  <option value="en" <?= $default_language=='en'?'selected':'' ?>>English</option>
                  <option value="de" <?= $default_language=='de'?'selected':'' ?>>Deutsch</option>
                  <option value="fr" <?= $default_language=='fr'?'selected':'' ?>>Français</option>
                </select>
              </div>

              <div class="form-group">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" value="1" <?= $maintenance_mode=='1'?'checked':'' ?>>
                  <label class="form-check-label" for="maintenance_mode"><?= $lang['maintenance_mode'] ?? 'Maintenance Mode' ?></label>
                </div>
              </div>

              <div class="form-group">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="allow_registration" name="allow_registration" value="1" <?= $allow_registration=='1'?'checked':'' ?>>
                  <label class="form-check-label" for="allow_registration"><?= $lang['allow_registration'] ?? 'Allow Registration' ?></label>
                </div>
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
      </div>
    </section>
  </div>

  <?php include "/inc/footer.php"; ?>

</div>

<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
