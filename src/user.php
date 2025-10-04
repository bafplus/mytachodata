<?php
require_once __DIR__ . '/inc/auth.php';  // ensures logged in
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

// Fetch user info
$stmt = $pdo->prepare("SELECT username, language FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch available languages dynamically
$langFiles = glob(__DIR__ . '/lang/*.php');
$languages = [];
foreach ($langFiles as $file) {
    $langCode = basename($file, '.php');
    $languages[] = $langCode;
}
?>

<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0"><?= $lang_arr['user_settings'] ?? 'User Settings' ?></h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title"><?= $lang_arr['profile'] ?? 'Profile' ?></h3>
        </div>
        <div class="card-body">
          <form action="user_update.php" method="POST">
            <div class="form-group">
              <label><?= $lang_arr['username'] ?? 'Username' ?></label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>

            <div class="form-group">
              <label><?= $lang_arr['language'] ?? 'Language' ?></label>
              <select name="language" class="form-control">
                <?php foreach ($languages as $langCode): ?>
                  <option value="<?= $langCode ?>" <?= ($langCode === $user['language']) ? 'selected' : '' ?>>
                    <?= strtoupper($langCode) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> <?= $lang_arr['save'] ?? 'Save' ?>
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

