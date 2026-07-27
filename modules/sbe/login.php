<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$config = require __DIR__ . '/config/app.php';

if (current_user()) {
    $user = current_user();
    redirect(($user['role'] ?? '') === 'Student' ? 'student-home.php' : 'teacher-home.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role    = (string) ($_POST['role'] ?? 'teacher');
    $loginId = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $user = null;

    try {
        $stmt = db()->prepare('SELECT * FROM sbe_auth_users WHERE role = :role AND login_id = :login_id LIMIT 1');
        $stmt->execute([':role' => $role, ':login_id' => $loginId]);
        $user = $stmt->fetch();
    } catch (Throwable $exception) {
        $user = null;
    }

    if (!$user) {
        try {
            $mappedRole = $role === 'teacher' ? 'Teacher' : 'Student';
            $stmt = db()->prepare('SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.username = :login_id LIMIT 1');
            $stmt->execute([':login_id' => $loginId]);
            $dbUser = $stmt->fetch();
            if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
                $user = [
                    'auth_id' => (int)$dbUser['user_id'],
                    'role' => $mappedRole,
                    'login_id' => $dbUser['username'],
                    'display_name' => $dbUser['full_name'],
                    'password_hash' => $dbUser['password_hash'],
                    'status' => $dbUser['status'] === 'Active' ? 'Active' : 'Inactive',
                ];
            }
        } catch (Throwable $exception) {
            $user = null;
        }
    }

    if ($user && $user['status'] === 'Active' && password_verify($password, $user['password_hash'])) {
        auth_login($user);
        redirect($role === 'student' ? 'student-home.php' : 'teacher-home.php');
    }

    $error = 'Invalid credentials or inactive account.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | SBE Portal</title>
    <meta name="description" content="Sign in to System Based Examination Portal">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">SBE</div>
        </div>
        <div class="login-title">
            <h1>System Based Examination</h1>
            <p>Secure access to exams, schedules, question banks, and results.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">TCH</div>
                <div>
                    <strong>Faculty Portal</strong>
                    <p class="small">Create exams, manage question banks, and review results.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">STU</div>
                <div>
                    <strong>Student Portal</strong>
                    <p class="small">Take exams, view schedules, and check results.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Sign in to SBE</h3>
        <p class="muted" style="margin-bottom:22px;">Access your exams and results instantly.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <div class="login-tabs">
                <button class="login-tab active" type="button" onclick="setRole('teacher', this)">Faculty / Teacher</button>
                <button class="login-tab" type="button" onclick="setRole('student', this)">Student</button>
            </div>

            <input type="hidden" name="role" id="selected-role" value="teacher">

            <div class="field" style="margin-bottom:16px;">
                <label>User ID</label>
                <input type="text" name="login_id" required placeholder="Enter your ID" autocomplete="username">
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
function setRole(role, btn) {
    document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('selected-role').value = role;
}

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
