<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$config = require __DIR__ . '/config/app.php';

if (current_user()) {
    $user = current_user();
    redirect(($user['role'] ?? '') === 'student' ? 'student-home.php' : 'teacher-home.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role    = (string) ($_POST['role'] ?? 'teacher');
    $loginId = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $user = null;

    try {
        $stmt = db()->prepare('SELECT * FROM auth_users WHERE role = :role AND login_id = :login_id LIMIT 1');
        $stmt->execute([':role' => $role, ':login_id' => $loginId]);
        $user = $stmt->fetch();
    } catch (Throwable $exception) {
        $user = null;
    }

    $fallback = $config['demo_auth'][$role][$loginId] ?? null;
    $fallbackValid = $fallback && hash_equals((string) $fallback['password'], $password);

    if ($user && $user['status'] === 'active' && (password_verify($password, $user['password_hash']) || hash_equals((string) $user['password_hash'], $password))) {
        auth_login($user);
        redirect($role === 'student' ? 'student-home.php' : 'teacher-home.php');
    }

    if ($fallbackValid) {
        auth_login([
            'auth_id'      => 0,
            'role'         => $role,
            'login_id'     => $loginId,
            'display_name' => $fallback['display_name'],
            'status'       => 'active',
        ]);
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
<body class="login-page" style="background: #f4f5f8;">

<div class="login-container" style="max-width: 960px; min-height: 560px; border-radius: var(--radius-lg); box-shadow: 0 20px 40px rgba(0,0,0,0.06); background: #fff;">
    <!-- Left panel (Mesh gradient look as in image 2) -->
    <section class="login-hero" style="background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 40%, #4338ca 100%); padding: 48px; display: flex; flex-direction: column; justify-content: space-between; border: none; overflow: hidden; position: relative;">
        <!-- Asterisk symbol in white -->
        <div style="font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1; user-select: none;">*</div>
        
        <!-- Bottom text -->
        <div style="margin-top: auto; color: #fff;">
            <p style="opacity: 0.85; font-size: 0.95rem; margin-bottom: 8px; font-weight: 500;">SBE Portal</p>
            <h2 style="font-size: 1.8rem; font-weight: 700; line-height: 1.35; color: #fff; margin: 0; letter-spacing: -0.02em;">Secure access to exams, schedules, and results</h2>
        </div>
    </section>

    <!-- Right panel (Login form) -->
    <aside class="login-panel" style="padding: 48px; display: flex; flex-direction: column; justify-content: center; background: #fff;">
        <!-- Blue Asterisk symbol -->
        <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent); line-height: 1; margin-bottom: 20px; user-select: none;">*</div>

        <h3 style="font-size: 1.7rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text-strong); margin: 0 0 6px;">Sign in to SBE</h3>
        <p style="color: var(--muted); font-size: 0.88rem; margin: 0 0 24px; line-height: 1.5;">Access your exams, question banks, and results snapshot instantly.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 16px; font-size: 0.84rem; border-radius: 8px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <!-- Segmented Switcher for Role selection -->
            <div class="login-tabs" style="background: var(--panel-strong); border-radius: var(--radius-sm); border: 1px solid var(--border); padding: 3px; display: flex; margin-bottom: 20px;">
                <button class="login-tab active" type="button" onclick="setRole('teacher', this)" style="flex: 1; padding: 8px 0; font-size: 0.86rem; border-radius: 6px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.2s;">Faculty / Teacher</button>
                <button class="login-tab" type="button" onclick="setRole('student', this)" style="flex: 1; padding: 8px 0; font-size: 0.86rem; border-radius: 6px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.2s;">Student</button>
            </div>
            
            <input type="hidden" name="role" id="selected-role" value="teacher">

            <!-- ID input field -->
            <div class="field" style="margin-bottom: 16px;">
                <label style="font-size: 0.84rem; font-weight: 600; color: var(--text-strong); display: block; margin-bottom: 6px;">User ID</label>
                <input type="text" name="login_id" required placeholder="Enter your ID" autocomplete="username" style="width: 100%; min-height: 42px; border-radius: 8px; border: 1px solid var(--border); padding: 10px 14px; font-size: 0.9rem;">
            </div>

            <!-- Password input field with toggle -->
            <div class="field password-field" style="margin-bottom: 24px; position: relative;">
                <label style="font-size: 0.84rem; font-weight: 600; color: var(--text-strong); display: block; margin-bottom: 6px;">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password" style="width: 100%; min-height: 42px; border-radius: 8px; border: 1px solid var(--border); padding: 10px 42px 10px 14px; font-size: 0.9rem;">
                    <button class="password-toggle" type="button" onclick="togglePass()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--muted); padding: 4px;">👁️</button>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width: 100%; min-height: 44px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; background: var(--accent); color: #fff; border: none; cursor: pointer; transition: all 0.2s;">Get Started</button>
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
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

function setLoading(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.classList.add('btn-loading');
    btn.disabled = true;
}
</script>
</body>
</html>
