<?php
session_start();
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/lang.php';
require_once __DIR__ . '/inc/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check role
$isAdmin = ($user['role'] === 'admin');

// Handle form submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = $_POST['username'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $newLanguage = $_POST['language'] ?? '';

    try {
        // Only admin can change username
        if ($isAdmin && $newUsername) {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $user['id']]);
        }

        // Update password if provided
        if ($newPassword) {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newPassword, $user['id']]);
        }

        // Update language
        if ($newLanguage) {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$newLanguage, $user['id']]);
        }

        $success = "Profile updated successfully.";
        // Refresh user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Available languages (from lang folder)
$langFiles = glob(__DIR__ . '/lang/*.php');
$languages = array_map(function($f) {
    return basename($f, '.php');
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
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                        value="<?= htmlspecialchars($user['username']) ?>"
                        <?= $isAdmin ? '' : 'readonly' ?>>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="text" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language" class="form-control">
                        <?php foreach($languages as $lang): ?>
                            <option value="<?= $lang ?>" <?= $user['language'] === $lang ? 'selected' : '' ?>>
                                <?= strtoupper($lang) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mt-2">Save</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
