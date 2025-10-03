<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyTacho Dashboard</title>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
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
        <a href="index.php" class="nav-link">Home</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Dark/Light theme toggle -->
      <li class="nav-item">
        <a class="nav-link" href="#" id="theme-toggle" title="Toggle Dark/Light Theme">
          <i class="fas fa-adjust"></i>
        </a>
      </li>

      <!-- User page link -->
      <li class="nav-item">
        <a class="nav-link" href="user.php" title="User Page">
          <i class="fas fa-user"></i>
        </a>
      </li>

      <!-- Logout -->
      <li class="nav-item">
        <a class="nav-link" href="logout.php" title="Logout">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

<script>
  // Simple theme toggle: adds/removes 'dark-mode' class on body
  document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('theme-toggle');
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      document.body.classList.toggle('dark-mode');
    });
  });
</script>


