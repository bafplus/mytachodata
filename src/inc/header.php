<?php include(__DIR__."/auth.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MyTacho</title>
  <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav ml-auto">
    <li class="nav-item"><a class="nav-link" href="/views/logout.php">Logout</a></li>
  </ul>
</nav>

<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="/views/dashboard.php" class="brand-link">
    <span class="brand-text font-weight-light">MyTacho</span>
  </a>
  <div class="sidebar">
    <nav>
      <ul class="nav nav-pills nav-sidebar flex-column">
        <li class="nav-item"><a href="/views/dashboard.php" class="nav-link"><i class="nav-icon fas fa-home"></i><p>Dashboard</p></a></li>
      </ul>
    </nav>
  </div>
</aside>

<!-- Content Wrapper -->
<div class="content-wrapper">
