<?php include("../inc/header.php"); ?>
<section class="content-header">
  <div class="container-fluid">
    <h1>Dashboard</h1>
  </div>
</section>
<section class="content">
  <div class="card">
    <div class="card-body">
      Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!
    </div>
  </div>
</section>
<?php include("../inc/footer.php"); ?>
