<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = current_user();
if ($user) {
    header('Location: ' . ($user['role'] === 'teacher' ? app_url('teacher/dashboard.php') : app_url('student/dashboard.php')));
    exit;
}

$config_demo_auth = $demo_auth ?? [];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = (string) ($_POST['role'] ?? 'teacher');
    $loginId  = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $dbUser = null;

    try {
        $stmt = db()->prepare('SELECT u.*, r.role_name AS role FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = ? AND u.login_id = ? LIMIT 1');
        $stmt->execute([$role, $loginId]);
        $dbUser = $stmt->fetch();
    } catch (Throwable $e) {
        $dbUser = null;
    }

    $fallback = $config_demo_auth[$role][$loginId] ?? null;
    $fallbackValid = $fallback && hash_equals((string) $fallback['password'], $password);

    if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
        auth_login($dbUser);
        header('Location: ' . ($role === 'teacher' ? app_url('teacher/dashboard.php') : app_url('student/dashboard.php')));
        exit;
    }

    if ($fallbackValid) {
        auth_login([
            'id' => 0,
            'login_id' => $loginId,
            'role' => $role,
            'name' => $fallback['display_name'],
            'department' => 'Computer Science',
            'program' => null,
            'profile_photo' => null,
        ]);
        header('Location: ' . ($role === 'teacher' ? app_url('teacher/dashboard.php') : app_url('student/dashboard.php')));
        exit;
    }

    $error = 'Invalid credentials or inactive account.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | University LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">LMS</div>
        </div>
        <div class="login-title">
            <h1>Faculty &amp; student portal in one place</h1>
            <p>Manage attendance, internal marks, assignments, announcements, and student records from a single dashboard.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">TCH</div>
                <div>
                    <strong>Faculty Dashboard</strong>
                    <p class="small">Mark attendance, grade assignments, manage internal marks.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">STU</div>
                <div>
                    <strong>Student Portal</strong>
                    <p class="small">View courses, track grades, submit assignments, check fees.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">SYS</div>
                <div>
                    <strong>Centralized Records</strong>
                    <p class="small">All academic data unified in one secure platform.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Sign in to LMS</h3>
        <p class="muted" style="margin-bottom:22px;">Use your University LMS account to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;font-size:.84rem;border-radius:8px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <div class="login-tabs">
                <button class="login-tab active" type="button" onclick="setRole('teacher', this)">Faculty / Teacher</button>
                <button class="login-tab" type="button" onclick="setRole('student', this)">Student</button>
            </div>
            <input type="hidden" name="role" id="selected-role" value="teacher">

            <div class="field" style="margin-bottom:16px;">
                <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">User ID</label>
                <input type="text" name="login_id" required placeholder="Enter your ID" autocomplete="username" style="min-height:42px;border-radius:8px;">
            </div>

            <div class="field password-field" style="margin-bottom:24px;">
                <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Password</label>
                <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password" style="min-height:42px;border-radius:8px;padding-right:42px;">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;border-radius:8px;font-size:.9rem;">Get Started</button>
        </form>

        <div class="login-footer">
            <p class="small">Demo: 5001 / teacher123 &nbsp;&middot;&nbsp; 9001 / student123</p>
        </div>
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
