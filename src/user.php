<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Admin can change username
    if ($_SESSION['role'] === 'admin' && isset($_POST['username'])) {
        $newUsername = trim($_POST['username']);
        if ($newUsername !== '') {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $user['id']]);
            $_SESSION['user'] = $newUsername;
            $user['username'] = $newUsername;
            $success = "Username updated.";
        } else {
            $errors[] = "Username cannot be empty.";
        }
    }

    // Change password
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $user['id']]);
        $success = $success ? $success . " Password updated." : "Password updated.";
    }

    // Change language
    if (!empty($_POST['language'])) {
        $lang = $_POST['language'];
        // Optionally: check if file exists
        if (file_exists(__DIR__ . "/lang/$lang.php")) {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$lang, $user['id']]);
            $user['language'] = $lang;
            $_SESSION['language'] = $lang;
            $success = $success ? $success . " Language updated." : "Language updated.";
        } else {
            $errors[] = "Invalid language selected.";
        }
    }
}

// Load available languages
$langFiles = glob(__DIR__ . '/lang/*.php');
$languages = array_map(function($file) {
    return basename($file, '.php');
}, $langFiles);

?>

<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">User Settings</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input 
            type="text" 
            class="form-control" 
            id="username" 
            name="username" 
            value="<?= htmlspecialchars($user['username']) ?>" 
            <?= $_SESSION['role'] !== 'admin' ? 'readonly' : '' ?>
          >
          <?php if ($_SESSION['role'] !== 'admin'): ?>
            <small class="form-text text-muted">Only admin can change the username.</small>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="password">New Password</label>
          <input type="password" class="form-control" id="password" name="password">
          <small class="form-text text-muted">Leave blank to keep current password.</small>
        </div>

        <div class="form-group">
          <label for="language">Language</label>
          <select name="language" id="language" class="form-control">
            <?php foreach ($languages as $lang): ?>
              <option value="<?= htmlspecialchars($lang) ?>" <?= $user['language'] === $lang ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($lang)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
