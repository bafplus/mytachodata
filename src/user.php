<?php
require_once __DIR__ . '/inc/db.php';

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

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $newLang = $_POST['language'] ?? 'en';

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, language = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $newLang, $userId])) {
            $success = $lang['profile_updated'] ?? "Profile updated successfully!";
            $user['language'] = $newLang;
            $_SESSION['language'] = $newLang;
        } else {
            $error = $lang['profile_update_failed'] ?? "Failed to update profile.";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
        if ($stmt->execute([$newLang, $userId])) {
            $success = $lang['language_updated'] ?? "Language updated successfully!";
            $user['language'] = $newLang;
            $_SESSION['language'] = $newLang;
        } else {
            $error = $lang['language_update_failed'] ?? "Failed to update language.";
        }
    }
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';

// Load available languages
$langFiles = glob(__DIR__ . '/lang/*.php');
$languages = array_map(fn($f) => basename($f, '.php'), $langFiles);
$userLang = $user['language'] ?? 'en';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0"><?= $lang['user_settings'] ?? 'User Settings' ?></h1>
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
                    <label for="username"><?= $lang['username'] ?? 'Username' ?></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['username']) ?>" 
                           <?= $user['role'] !== 'admin' ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="password"><?= $lang['new_password'] ?? 'New Password' ?></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="<?= $lang['password_placeholder'] ?? 'Leave blank to keep current' ?>">
                </div>

                <div class="form-group">
                    <label for="language"><?= $lang['language'] ?? 'Language' ?></label>
                    <select id="language" name="language" class="form-control">
                        <?php foreach ($languages as $langOption) : ?>
                            <option value="<?= $langOption ?>" <?= $userLang === $langOption ? 'selected' : '' ?>>
                                <?= strtoupper($langOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $lang['save_changes'] ?? 'Save Changes' ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
