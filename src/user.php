<?php
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/db.php';

// Get current user info from session
$username = $_SESSION['user'] ?? null;

if (!$username) {
    header('Location: login.php');
    exit;
}

// Fetch user info from DB by username
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

$userId = $user['id']; // store numeric ID for updates

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = $_POST['username'] ?? $user['username'];
    $newPassword = $_POST['password'] ?? '';
    $newLang = $_POST['language'] ?? $user['language'];

    // Only admin can change username
    if ($user['role'] !== 'admin') {
        $newUsername = $user['username'];
    }

    try {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, language = ? WHERE id = ?");
            $stmt->execute([$newUsername, $hashedPassword, $newLang, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, language = ? WHERE id = ?");
            $stmt->execute([$newUsername, $newLang, $userId]);
        }
        $success = "Profile updated successfully!";
        $user['username'] = $newUsername;
        $user['language'] = $newLang;
    } catch (Exception $e) {
        $error = "Failed to update profile: " . $e->getMessage();
    }
}

// Available languages from /inc/lang/
$langFiles = glob(__DIR__ . '/inc/lang/*.php');
$languages = array_map(fn($f) => basename($f, '.php'), $langFiles);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">User Settings</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($success) : ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error) : ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           value="<?= htmlspecialchars($user['username']) ?>"
                           <?= $user['role'] !== 'admin' ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language" class="form-control">
                        <?php foreach ($languages as $lang) : ?>
                            <option value="<?= $lang ?>" <?= $user['language'] === $lang ? 'selected' : '' ?>><?= strtoupper($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
