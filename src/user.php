<?php
require_once __DIR__ . '/inc/db.php';

// Start session
if (!isset($_SESSION)) session_start();

// Get current user info
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Include header after login check
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $newLang = $_POST['language'] ?? 'en';

    // Update password if provided
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, language = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $newLang, $userId])) {
            $success = "Profile updated successfully!";
            $user['language'] = $newLang;
        } else {
            $error = "Failed to update profile.";
        }
    } else {
        // Only update language
        $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
        if ($stmt->execute([$newLang, $userId])) {
            $success = "Language updated successfully!";
            $user['language'] = $newLang;
        } else {
            $error = "Failed to update language.";
        }
    }
}

// Load available languages
$langFiles = glob(__DIR__ . '/inc/lang/*.php');
$languages = array_map(fn($f) => basename($f, '.php'), $langFiles);
$userLang = $user['language'] ?? 'en';
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
                            <option value="<?= $lang ?>" <?= $userLang === $lang ? 'selected' : '' ?>><?= strtoupper($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
