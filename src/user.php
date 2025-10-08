<?php
require_once __DIR__ . '/inc/db.php';

// Start session
if (!isset($_SESSION)) {
    session_start();
}

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

// Initialize messages
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //
    // 🔹 Handle password and language updates
    //
    if (isset($_POST['language']) || isset($_POST['password'])) {
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

    //
    // 🔹 Delete personal database (if any)
    //
    if (isset($_POST['delete_database'])) {
        $dbName = "mytacho_user_" . intval($userId);
        try {
            $dbHost = getenv('DB_HOST');
            $dbUser = getenv('DB_USER');
            $dbPass = getenv('DB_PASS');

            $pdoRoot = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
            $pdoRoot->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtCheck = $pdoRoot->prepare("SHOW DATABASES LIKE ?");
            $stmtCheck->execute([$dbName]);

            if ($stmtCheck->fetch()) {
                $pdoRoot->exec("DROP DATABASE `$dbName`");
                $success = $lang['database_deleted'] ?? "Your personal database has been deleted successfully.";
            } else {
                $error = $lang['database_not_found'] ?? "No personal database found to delete.";
            }
        } catch (PDOException $e) {
            $error = $lang['database_delete_failed'] ?? "Error deleting database: " . htmlspecialchars($e->getMessage());
        }
    }

    //
    // 🔹 Delete full account (database + user record) — blocked for admins
    //
    if (isset($_POST['delete_account'])) {
        if ($user['role'] === 'admin') {
            $error = $lang['admin_delete_blocked'] ?? "Admins cannot delete their own account.";
        } else {
            $dbName = "mytacho_user_" . intval($userId);
            try {
                $dbHost = getenv('DB_HOST');
                $dbUser = getenv('DB_USER');
                $dbPass = getenv('DB_PASS');

                $pdoRoot = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
                $pdoRoot->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Drop personal DB if exists
                $stmtCheck = $pdoRoot->prepare("SHOW DATABASES LIKE ?");
                $stmtCheck->execute([$dbName]);
                if ($stmtCheck->fetch()) {
                    $pdoRoot->exec("DROP DATABASE `$dbName`");
                }

                // Delete user record
                $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmtDelete->execute([$userId]);

                // Log out & destroy session
                session_unset();
                session_destroy();

                header("Location: login.php?msg=account_deleted");
                exit;

            } catch (PDOException $e) {
                $error = $lang['account_delete_failed'] ?? "Error deleting your account: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Load available languages
$langFiles = glob(__DIR__ . '/lang/*.php');
$languages = array_map(fn($f) => basename($f, '.php'), $langFiles);
$userLang = $user['language'] ?? 'en';

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
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

            <!-- 🔹 Update Profile Form -->
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
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="<?= $lang['password_placeholder'] ?? 'Leave blank to keep current' ?>">
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

                <button type="submit" class="btn btn-primary">
                    <?= $lang['save_changes'] ?? 'Save Changes' ?>
                </button>
            </form>

            <hr>

            <!-- 🔹 Delete Personal Database -->
            <form method="POST"
                  onsubmit="return confirm('⚠️ This will permanently delete your personal database (if it exists). Continue?');">
                <input type="hidden" name="delete_database" value="1">
                <button type="submit" class="btn btn-warning">
                    <?= $lang['delete_my_database'] ?? 'Delete My Database' ?>
                </button>
            </form>

            <hr>

            <!-- 🔹 Delete Full Account (blocked for admins) -->
            <?php if ($user['role'] !== 'admin') : ?>
                <form method="POST"
                      onsubmit="return confirm('⚠️ This will permanently delete your account AND your personal database. This action cannot be undone. Continue?');">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="btn btn-danger">
                        <?= $lang['delete_my_account'] ?? 'Delete My Account' ?>
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    <?= $lang['admin_delete_blocked'] ?? 'Admins cannot delete their own account.' ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

