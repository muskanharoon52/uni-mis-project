<?php
session_start();

$module = isset($_GET['module']) ? $_GET['module'] : 'mis';
$error = isset($_GET['error']) ? $_GET['error'] : '';

include __DIR__ . '/../../config/db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed.");
}

$login_error = '';
$already_logged_in = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $module = trim($_POST['module'] ?? 'mis');

    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
    } else {
        $sql = "SELECT u.*, r.role_name FROM users u
                JOIN roles r ON u.role_id = r.role_id
                WHERE u.username = ? AND u.status = 'Active'";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if ($password == $user['password_hash']) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['module'] = $module;

                $update_sql = "UPDATE users SET last_login_at = NOW() WHERE user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

                if ($user['role_id'] == 3 || $user['role_id'] == 1) {
                    header('Location: ../finance/dashboard.php');
                    exit();
                } else {
                    header('Location: ../index.php');
                    exit();
                }
            } else {
                $login_error = 'Invalid password.';
            }
        } else {
            $login_error = 'Username not found or account inactive.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | <?= htmlspecialchars(ucfirst($module)) ?> Module</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/modules/lms/public/assets/style.css') ?>">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">SSO</div>
        </div>
        <div class="login-title">
            <h1>University Single Sign-On</h1>
            <p>One account for all modules — LMS, Finance, Admission, and Examination.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">SEC</div>
                <div>
                    <strong>Secure Authentication</strong>
                    <p class="small">Centralized login with role-based access control.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">ACC</div>
                <div>
                    <strong>Unified Account</strong>
                    <p class="small">Same credentials work across all university modules.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Sign In</h3>
        <p class="muted" style="margin-bottom:22px;">Use your University account to continue.</p>

        <?php if ($already_logged_in): ?>
            <div class="alert alert-success" style="margin-bottom:16px;">
                Already logged in as <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>.
                <a href="../index.php" style="color:var(--accent);font-weight:600;">Go to Home</a> |
                <a href="logout.php" style="color:var(--danger);font-weight:600;">Logout</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($login_error)): ?>
            <div class="alert alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <input type="hidden" name="module" value="<?= htmlspecialchars($module) ?>">

            <div class="field" style="margin-bottom:16px;">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username" autocomplete="username" autofocus>
            </div>

            <div class="field password-field" style="margin-bottom:24px;">
                <label>Password</label>
                <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;">Sign In</button>
        </form>

        <div class="login-footer">
            <p class="small"><a href="../index.php" style="color:var(--accent);font-weight:600;">&#8592; Back to Module Selection</a></p>
        </div>
    </aside>
</div>

<script>
function togglePass() {
    const input = document.getElementById('pass-field');
    input.type = input.type === 'password' ? 'text' : 'password';
}
function setLoading(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.classList.add('btn-loading');
    btn.disabled = true;
}
</script>
</body>
</html>
