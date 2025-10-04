<?php
require_once __DIR__ . '/inc/auth.php';

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = trim($_POST['password'] ?? '');
    $new_language = $_POST['language'] ?? $_SESSION['language'];

    if ($new_username === '') {
        $message = $lang['username_required'] ?? 'Username is required';
    } else {
        // Update username, password (if provided), and language
        $sql = "UPDATE users SET username = ?, language = ?".($new_password !== '' ? ", password = ?" : "")." WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        if ($new_password !== '') {
            $stmt->execute([$new_username, $new_language, password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        } else {
            $stmt->execute([$new_username, $new_language, $_SESSION['user_id']]);
        }

        // Update session
        $_SESSION['username'] = $new_username;
        $_SESSION['language'] = $new_language;
        $lang_file = __DIR__ . "/lang/{$new_language}.php";
        if (file_exists($lang_file)) {
            $lang = include $lang_file;
        }

        $message = $lang['profile_updated'] ?? 'Profile updated successfully';
    }
}

// Load current user info for the form
$current_username = $_SESSION['username'];
$current_language = $_SESSION['language'];

?>

<?php include __DIR__ . '/inc/header.php'; ?>
<?php include __DIR__ . '/inc/sidebar.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0"><?= htmlspecialchars($lang['user_profile'] ?? 'User Profile') ?></h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><?= htmlspecialchars($lang['username'] ?? 'Username') ?></label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($current_username) ?>" required>
                </div>

                <div class="form-group">
                    <label><?= htmlspecialchars($lang['password'] ?? 'Password') ?> (<?= htmlspecialchars($lang['leave_blank_if_no_change'] ?? 'Leave blank if no change') ?>)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="form-group">
                    <label><?= htmlspecialchars($lang['language'] ?? 'Language') ?></label>
                    <select name="language" class="form-control">
                        <?php
                        foreach (glob(__DIR__ . "/lang/*.php") as $file) {
                            $lang_code = basename($file, '.php');
                            $selected = $lang_code === $current_language ? 'selected' : '';
                            echo "<option value=\"$lang_code\" $selected>$lang_code</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang['save_changes'] ?? 'Save Changes') ?></button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
