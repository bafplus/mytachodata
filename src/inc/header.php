<?php
if (!isset($_SESSION)) {
    session_start();
}

// Determine current language
$currentLang = $_SESSION['language'] ?? 'en';
$langFile = __DIR__ . "/../lang/$currentLang.php";

if (file_exists($langFile)) {
    $lang = include $langFile;
} else {
    $lang = include __DIR__ . '/../lang/en.php';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($lang['dashboard_title'] ?? 'MyTacho Dashboard') ?></title>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="index.php" class="nav-link"><?= htmlspecialchars($lang['nav_home'] ?? 'Home') ?></a>
      </li>
    </ul>

<!-- Right navbar links -->
<ul class="navbar-nav ml-auto">
  <!-- Theme toggle -->
  <li class="nav-item">
    <a href="#" id="theme-toggle" class="nav-link" title="<?= htmlspecialchars($lang['nav_toggle_theme'] ?? 'Toggle Light/Dark') ?>">
      <i class="fas fa-adjust"></i>
    </a>
  </li>

  <!-- Admin button (only visible to admin) -->
  <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
    <li class="nav-item">
      <a href="admin.php" class="nav-link" title="<?= htmlspecialchars($lang['nav_admin'] ?? 'Admin Settings') ?>">
        <i class="fas fa-cog"></i>
      </a>
    </li>
  <?php endif; ?>

  <!-- User page -->
  <li class="nav-item">
    <a href="user.php" class="nav-link" title="<?= htmlspecialchars($lang['nav_user'] ?? 'User Page') ?>">
      <i class="fas fa-user"></i>
    </a>
  </li>

  <!-- Logout -->
  <li class="nav-item">
    <a href="logout.php" class="nav-link" title="<?= htmlspecialchars($lang['nav_logout'] ?? 'Logout') ?>">
      <i class="fas fa-sign-out-alt"></i>
    </a>
  </li>
</ul>
  </nav>
  <!-- /.navbar -->
