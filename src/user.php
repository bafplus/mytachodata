<?php
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php';

// Get current user info
$userId = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $newLang = $_POST['language'] ?? 'en';

    // Only admin can update username
    $newUsername = ($user['role'] === 'admin' && isset($_POST['username'])) ? $_POST['username'] : $user['username'];

    try {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, language = ? WHERE id = ?");
            $stmt->execute([$newUsername, $hashedPassword, $newLang, $userId]);
            $success = "Profile updated successfully!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, language = ? WHERE id = ?");
            $stmt->execute([$newUsername, $newLang, $userId]);
            $success = "Profile updated successfully!";
        }
        // Update session and $user array
        $user['language'] = $newLang;
        $user['username'] = $newUsername;

        // Reload language strings immediately
        $langFile = __DIR__ . "/inc/lang/{$newLang}.php";
        if (file_exists($langFile)) {
            $L = include $langFile;
        }

    } catch (PDOException $e) {
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
            <h1 class="m-0"><?= $L['user_settings'] ?? 'User Settings' ?></h1>
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
                    <label for="username"><?= $L['username'] ?? 'Username' ?></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['username']) ?>" 
                           <?= $user['role'] !== 'admin' ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="password"><?= $L['new_password'] ?? 'New Password' ?></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="<?= $L['leave_blank'] ?? 'Leave blank to keep current' ?>">
                </div>

                <div class="form-group">
                    <label for="language"><?= $L['language'] ?? 'Language' ?></label>
                    <select id="language" name="language" class="form-control">
                        <?php foreach ($languages as $lang) : ?>
                            <option value="<?= $lang ?>" <?= $user['language'] === $lang ? 'selected' : '' ?>><?= strtoupper($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $L['save_changes'] ?? 'Save Changes' ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
