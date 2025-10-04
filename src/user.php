<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, password, language, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'] ?? $user['username'];
    $new_language = $_POST['language'] ?? $user['language'];
    $new_password = $_POST['password'] ?? '';
    $new_password_confirm = $_POST['password_confirm'] ?? '';

    // only admin can change username
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $new_username = $user['username'];
    }

    if (!empty($new_password)) {
        if ($new_password === $new_password_confirm) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, language = ? WHERE id = ?");
            $stmt->execute([$new_username, $hashed_password, $new_language, $user_id]);
            $message = $lang['update_success'];
        } else {
            $message = $lang['password_mismatch'];
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, language = ? WHERE id = ?");
        $stmt->execute([$new_username, $new_language, $user_id]);
        $message = $lang['update_success'];
    }

    // refresh session values
    $_SESSION['user'] = $new_username;
    $_SESSION['language'] = $new_language;
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1><?= $lang['user_settings'] ?></h1>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label><?= $lang['username'] ?></label>
          <input type="text" name="username" class="form-control"
                 value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                 <?= (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '' : 'readonly' ?>>
        </div>

        <div class="form-group">
          <label><?= $lang['language'] ?></label>
          <select name="language" class="form-control">
            <?php
            $lang_files = glob(__DIR__ . '/lang/*.php');
            foreach ($lang_files as $file) {
                $code = basename($file, '.php');
                $selected = ($code === ($user['language'] ?? 'en')) ? 'selected' : '';
                echo "<option value=\"$code\" $selected>$code</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label><?= $lang['password'] ?></label>
          <input type="password" name="password" class="form-control" placeholder="<?= $lang['new_password'] ?>">
        </div>

        <div class="form-group">
          <label><?= $lang['confirm_password'] ?></label>
          <input type="password" name="password_confirm" class="form-control" placeholder="<?= $lang['confirm_password'] ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?= $lang['save_changes'] ?></button>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
