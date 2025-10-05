<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/lang.php'; // optional for translations

// Start session
if (!isset($_SESSION)) session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if registration is allowed
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allow_registration'");
$stmt->execute();
$allowRegistration = $stmt->fetchColumn() === '1';

// Prefill username and success message from registration
$prefillUsername = $_GET['username'] ?? '';
$registrationSuccess = isset($_GET['registered']);

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password, role, language FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['language'] = $user['language'] ?? 'en';

            header('Location: index.php');
            exit;
        } else {
            $error = $lang['incorrect_username_password'] ?? "Incorrect username or password.";
        }
    } else {
        $error = $lang['username_password_required'] ?? "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['login_title'] ?? 'Login - MyTacho' ?></title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
    <style>
        body.login-page {
            background-color: #f4f6f9;
        }
        .login-box {
            margin: 7% auto;
            width: 360px;
        }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1"><b>My</b>Tacho</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg"><?= $lang['login_msg'] ?? 'Sign in to start your session' ?></p>

            <?php if ($registrationSuccess): ?>
                <div class="alert alert-success"><?= $lang['registration_success'] ?? 'Registration successful! You can now log in.' ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="<?= $lang['username'] ?? 'Username' ?>" required
                           value="<?= htmlspecialchars($prefillUsername) ?>">
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

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block"><?= $lang['sign_in'] ?? 'Sign In' ?></button>
                    </div>
                </div>
            </form>

            <?php if ($allowRegistration): ?>
                <p class="mt-3 mb-1 text-center">
                    <a href="register.php"><?= $lang['no_account_register'] ?? 'No account yet? Register' ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AdminLTE JS -->
<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>

