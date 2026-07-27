<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../../sso/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginUser($username, $password)) {
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? 'User';
        $_SESSION['user_role'] = $_SESSION['role_name'] ?? 'Staff';
        header('Location: ' . BASE_URL . 'modules/admission/index.php');
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | Admission System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime(__DIR__ . '/../../lms/public/assets/style.css') ?>">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">ADM</div>
        </div>
        <div class="login-title">
            <h1>Admission Management System</h1>
            <p>Process applications, manage student enrollments, track fees, and generate admission reports.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">APP</div>
                <div>
                    <strong>Application Processing</strong>
                    <p class="small">Review, approve, or reject student applications.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">ENR</div>
                <div>
                    <strong>Student Enrollment</strong>
                    <p class="small">Enroll accepted students and assign programs.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">SCH</div>
                <div>
                    <strong>Scholarships</strong>
                    <p class="small">Manage scholarship criteria and award allocations.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Sign in to Admission</h3>
        <p class="muted" style="margin-bottom:22px;">Use your University account to access the admission portal.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <div class="field" style="margin-bottom:16px;">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username" autocomplete="username">
            </div>

            <div class="field password-field" style="margin-bottom:24px;">
                <label>Password</label>
                <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;">Sign In</button>
        </form>
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
