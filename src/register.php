<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php'; // optional for translations

// Start session
if (!isset($_SESSION)) session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if registration is allowed
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allow_registration'");
$stmt->execute();
$allowRegistration = $stmt->fetchColumn();
if ($allowRegistration !== '1') {
    header('Location: login.php');
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $language = $_POST['language'] ?? 'en';

    if ($username && $password && $confirmPassword) {
        // Check if passwords match
        if ($password !== $confirmPassword) {
            $error = $lang['password_mismatch'] ?? 'Passwords do not match.';
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() == 0) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, language) VALUES (?, ?, 'user', ?)");
                $stmt->execute([$username, $hashedPassword, $language]);

                // Redirect to login with prefilled username and success message
                header('Location: login.php?username=' . urlencode($username) . '&registered=1');
                exit;
            } else {
                $error = $lang['username_exists'] ?? 'Username already exists.';
            }
        }
    } else {
        $error = $lang['username_password_required'] ?? 'Username and password are required.';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['register_title'] ?? 'Register - MyTacho' ?></title>
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
    <style>
        body.login-page { background-color: #f4f6f9; }
        .login-box { margin: 7% auto; width: 360px; }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1"><b>My</b>Tacho</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg"><?= $lang['register_msg'] ?? 'Register a new account' ?></p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="<?= $lang['username'] ?? 'Username' ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="<?= $lang['password'] ?? 'Password' ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" name="confirm_password" class="form-control" placeholder="<?= $lang['confirm_password'] ?? 'Confirm Password' ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <select name="language" class="form-control">
                        <option value="en"><?= $lang['lang_en'] ?? 'English' ?></option>
                        <option value="de"><?= $lang['lang_de'] ?? 'Deutsch' ?></option>
                        <option value="fr"><?= $lang['lang_fr'] ?? 'Français' ?></option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block"><?= $lang['register'] ?? 'Register' ?></button>
                    </div>
                </div>
            </form>

            <p class="mt-3 mb-1">
                <a href="login.php"><?= $lang['login_link'] ?? 'Already have an account? Sign in' ?></a>
            </p>
        </div>
    </div>
</div>

<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
