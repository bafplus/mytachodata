<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Include DB connection if needed
require_once __DIR__ . '/inc/db.php';

// Include header + sidebar
include __DIR__ . '/inc/header.php';
include __DIR__ . '/inc/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Dashboard</h1>
    </div>
  </div>

  <div class="content">
<div class="container-fluid">
    <div class="row">
        <!-- Card 1: Total Uploads -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>10</h3>
                    <p>Total Uploads</p>
                </div>
                <div class="icon">
                    <i class="fas fa-upload"></i>
                </div>
                <a href="upload-raw.php" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Processed Files -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>5</h3>
                    <p>Processed Files</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <a href="#" class="small-box-footer">
                    Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Card 3: Users -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>3</h3>
                    <p>Users</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="user.php" class="small-box-footer">
                    Manage <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Card 4: System Alerts -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>2</h3>
                    <p>System Alerts</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="#" class="small-box-footer">
                    Review <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

