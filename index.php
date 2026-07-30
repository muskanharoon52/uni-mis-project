<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_unset();
session_destroy();
session_start();

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/modules/sso/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginUser($username, $password)) {
        header('Location: ' . dashboardUrlForRole());
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Sign In | University MIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f172a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-wrapper { display: flex; max-width: 900px; width: 100%; margin: 24px; border-radius: 20px; overflow: hidden; background: rgba(255,255,255,0.05); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 25px 60px rgba(0,0,0,0.3); }
        .login-brand { width: 380px; padding: 48px 36px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; }
        .login-brand::before { content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,0.15), transparent 60%); top: -30%; right: -40%; pointer-events: none; }
        .login-brand::after { content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(16,185,129,0.1), transparent 60%); bottom: -20%; left: -20%; pointer-events: none; }
        .login-brand > * { position: relative; z-index: 1; }
        .login-brand .brand-icon { font-size: 2.5rem; margin-bottom: 16px; }
        .login-brand h2 { font-size: 1.6rem; font-weight: 800; letter-spacing: -.02em; background: linear-gradient(135deg, #fff 0%, #c7d2fe 50%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px; }
        .login-brand p { font-size: .88rem; color: rgba(255,255,255,0.55); line-height: 1.6; }
        .login-brand .features { margin-top: 24px; display: flex; flex-direction: column; gap: 12px; }
        .login-brand .features span { font-size: .82rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 8px; }
        .login-brand .features span::before { content: '✓'; color: #22c55e; font-weight: 700; }
        .login-form { flex: 1; padding: 48px 40px; }
        .login-form h3 { font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
        .login-form .subtitle { font-size: .85rem; color: rgba(255,255,255,0.5); margin-bottom: 28px; }
        .field { margin-bottom: 18px; }
        .field label { font-size: .82rem; font-weight: 600; color: rgba(255,255,255,0.8); display: block; margin-bottom: 6px; }
        .field input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06); color: #fff; font-size: .92rem; font-family: inherit; outline: none; transition: border-color .15s; }
        .field input:focus { border-color: #6366f1; }
        .field input::placeholder { color: rgba(255,255,255,0.3); }
        .password-field { position: relative; }
        .password-toggle { position: absolute; right: 12px; top: 38px; background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 1rem; padding: 4px; }
        .password-toggle:hover { color: rgba(255,255,255,0.7); }
        .btn-login { width: 100%; padding: 12px; border-radius: 10px; border: none; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; font-size: .95rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: opacity .15s; min-height: 46px; }
        .btn-login:hover { opacity: .9; }
        .btn-login:disabled { opacity: .5; cursor: not-allowed; }
        .btn-loading { position: relative; }
        .btn-loading::after { content: ''; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; display: inline-block; animation: spin .6s linear infinite; margin-left: 8px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .alert-error { padding: 10px 14px; border-radius: 8px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #f87171; font-size: .84rem; margin-bottom: 18px; }
        .credential-hints { margin-top: 28px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); }
        .credential-hints p { font-size: .75rem; color: rgba(255,255,255,0.35); margin-bottom: 10px; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
        .hint-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .hint-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; background: rgba(255,255,255,0.04); font-size: .78rem; color: rgba(255,255,255,0.5); }
        .hint-item .role-tag { padding: 2px 8px; border-radius: 4px; font-size: .65rem; font-weight: 600; text-transform: uppercase; flex-shrink: 0; }
        .tag-super { background: rgba(239,68,68,0.2); color: #f87171; }
        .tag-admin { background: rgba(99,102,241,0.2); color: #818cf8; }
        .tag-admission { background: rgba(16,185,129,0.2); color: #34d399; }
        .tag-finance { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .tag-examiner { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .tag-teacher { background: rgba(168,85,247,0.2); color: #c084fc; }
        .tag-student { background: rgba(34,197,94,0.2); color: #4ade80; }
        .hint-item .cred { font-family: monospace; color: rgba(255,255,255,0.6); }
        @media (max-width: 680px) {
            .login-wrapper { flex-direction: column; margin: 12px; }
            .login-brand { width: 100%; padding: 32px 24px; }
            .login-form { padding: 32px 24px; }
            .hint-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-brand">
            <div class="brand-icon">&#127891;</div>
            <h2>University MIS</h2>
            <p>Centralized management system for learning, examinations, admissions, finance, and records.</p>
            <div class="features">
                <span>Single sign-on for all modules</span>
                <span>Role-based access control</span>
                <span>Real-time dashboards &amp; analytics</span>
                <span>Integrated fee &amp; attendance tracking</span>
            </div>
        </div>
        <div class="login-form">
            <h3>Sign In</h3>
            <p class="subtitle">Enter your credentials to access the system.</p>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" onsubmit="setLoading(this)">
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter your username" autocomplete="username">
                </div>
                <div class="field password-field">
                    <label>Password</label>
                    <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password">
                    <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
                </div>
                <button class="btn-login" type="submit">Sign In</button>
            </form>

            <div class="credential-hints">
                <p>Demo Credentials</p>
                <div class="hint-grid">
                    <div class="hint-item"><span class="role-tag tag-super">Super</span> <span class="cred">superadmin / password123</span></div>
                    <div class="hint-item"><span class="role-tag tag-admin">Admin</span> <span class="cred">admin / password123</span></div>
                    <div class="hint-item"><span class="role-tag tag-admission">Admission</span> <span class="cred">admission / password123</span></div>
                    <div class="hint-item"><span class="role-tag tag-finance">Finance</span> <span class="cred">finance / finance123</span></div>
                    <div class="hint-item"><span class="role-tag tag-examiner">Examiner</span> <span class="cred">examiner / examiner123</span></div>
                    <div class="hint-item"><span class="role-tag tag-teacher">Teacher</span> <span class="cred">sara.khan / teacher123</span></div>
                    <div class="hint-item"><span class="role-tag tag-student">Student</span> <span class="cred">ali.raza / student123</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePass() {
        var input = document.getElementById('pass-field');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    function setLoading(form) {
        var btn = form.querySelector('.btn-login');
        btn.classList.add('btn-loading');
        btn.disabled = true;
    }
    </script>
</body>
</html>