<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Include DB connection
require_once __DIR__ . '/inc/db.php';

// Include reusable header
include __DIR__ . '/inc/header.php';
?>

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h1 class="mt-4">Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</p>

        <!-- Example info boxes -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3>0</h3>
                <p>Uploaded DDD Files</p>
              </div>
              <div class="icon">
                <i class="fas fa-upload"></i>
              </div>
              <a href="upload-raw.php" class="small-box-footer">
                Upload Now <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>
          <!-- Add more boxes here -->
        </div>

      </div>
    </div>
  </div>
</div>
<!-- /.content -->

<?php
// Include reusable footer
include __DIR__ . '/inc/footer.php';
