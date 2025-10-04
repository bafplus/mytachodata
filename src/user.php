<?php
require_once __DIR__ . '/inc/auth.php';   // handles session + login check
require_once __DIR__ . '/inc/db.php';     // database connection
require_once __DIR__ . '/inc/lang.php';   // language system

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT username, password, language FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$username = $row ? htmlspecialchars($row['username']) : '';
$password = $row ? htmlspecialchars($row['password']) : '';
$language = $row ? htmlspecialchars($row['language']) : 'en';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_language = $_POST['language'];

    if (!empty($new_username)) {
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, language = ? WHERE id = ?");
            $stmt->execute([$new_username, $hash, $new_language, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, language = ? WHERE id = ?");
            $stmt->execute([$new_username, $new_language, $user_id]);
        }

        // Update session + language
        $_SESSION['username'] = $new_username;
        $_SESSION['language'] = $new_language;

        header("Location: user.php?success=1");
        exit;
    }
}

include 'header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $lang['user_settings'] ?? 'User Settings' ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= $lang['settings_updated'] ?? 'Settings updated successfully!' ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username"><?= $lang['username'] ?? 'Username' ?></label>
                    <input type="text" name="username" id="username" class="form-control" value="<?= $username ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><?= $lang['password'] ?? 'Password' ?></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="<?= $lang['leave_blank_password'] ?? 'Leave blank to keep current password' ?>">
                </div>

                <div class="form-group">
                    <label for="language"><?= $lang['language'] ?? 'Language' ?></label>
                    <select name="language" id="language" class="form-control">
                        <?php
                        $lang_files = glob(__DIR__ . '/lang/*.php');
                        foreach ($lang_files as $file) {
                            $code = basename($file, '.php');
                            $selected = ($code === $language) ? 'selected' : '';
                            echo "<option value=\"$code\" $selected>" . strtoupper($code) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $lang['save'] ?? 'Save' ?></button>
            </form>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>
