<?php
// Include DB and start session
require_once __DIR__ . '/inc/db.php';
if (!isset($_SESSION)) session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch current user info
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, username, role, language FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Load available languages
$langDir = __DIR__ . '/../lang';
$langFiles = glob($langDir . '/../*.php');
$languages = [];
foreach ($langFiles as $file) {
    $langCode = basename($file, '.php');
    $languages[$langCode] = strtoupper($langCode);
}

// Fallback to English if missing
$userLang = $user['language'] ?? 'en';
if (!isset($languages[$userLang])) {
    $userLang = 'en';
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $newLang = $_POST['language'] ?? $userLang;

    try {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, language = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $newLang, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$newLang, $userId]);
        }

        $success = "Profile updated successfully!";
        $user['language'] = $newLang;
        $_SESSION['language'] = $newLang;
    } catch (Exception $e) {
        $error = "Failed to update profile: " . $e->getMessage();
    }
}

// Include header AFTER session and DB logic
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

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
                        <?php foreach ($languages as $code => $label): ?>
                            <option value="<?= $code ?>" <?= $userLang === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
