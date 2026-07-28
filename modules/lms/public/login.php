<?php
 
declare(strict_types=1);
 
require_once __DIR__ . '/../includes/auth.php';
 
// Also load SSO auth for unified authentication
require_once __DIR__ . '/../../sso/includes/auth.php';
 
$user = current_user();
if ($user) {
    $redirect = strtolower($user['role']) === 'teacher' ? app_url('teacher/dashboard.php') : app_url('student/dashboard.php');
    header('Location: ' . $redirect);
    exit;
}
 
$error = null;
$selected_role = 'teacher'; // default
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $role     = (string) ($_POST['role'] ?? 'teacher');
    $selected_role = $role;
    $loginId  = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
 
    // Try SSO unified auth first (works with username or login_id)
    $ssoUser = loginUserById($loginId, $password);
 
    if ($ssoUser && is_array($ssoUser)) {
        $lmsRole = strtolower($ssoUser['role_name'] ?? '');
        if (in_array($lmsRole, ['student', 'admin', 'examiner', 'finance officer'])) {
            $lmsRole = 'student';
        }
 
        $teacherId = 0;
        try {
            $tStmt = db()->prepare('SELECT teacher_id FROM teachers WHERE user_id = ? LIMIT 1');
            $tStmt->execute([(int) $ssoUser['user_id']]);
            $teacherId = (int) ($tStmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $teacherId = 0;
        }
 
        auth_login([
            'id'           => (int) $ssoUser['user_id'],
            'login_id'     => (string) ($ssoUser['login_id'] ?: $ssoUser['username']),
            'role'         => $lmsRole,
            'name'         => $ssoUser['full_name'] ?? 'User',
            'department'   => (string) ($ssoUser['department'] ?? ''),
            'program'      => (string) ($ssoUser['program'] ?? ''),
            'profile_photo'=> (string) ($ssoUser['profile_photo'] ?? ''),
            'teacher_id'   => $teacherId,
        ]);
 
        header('Location: ' . ($lmsRole === 'teacher' ? app_url('teacher/dashboard.php') : app_url('student/dashboard.php')));
        exit;
    }
 
    // Direct DB lookup
    $dbUser = null;
    try {
        $stmt = db()->prepare('SELECT u.*, r.role_name AS role, t.teacher_id FROM users u JOIN roles r ON r.role_id = u.role_id LEFT JOIN teachers t ON t.user_id = u.user_id WHERE r.role_name = ? AND (u.login_id = ? OR u.username = ?) LIMIT 1');
        $stmt->execute([$role, $loginId, $loginId]);
        $dbUser = $stmt->fetch();
    } catch (Throwable $e) {
        $dbUser = null;
    }
 
    if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
        $_SESSION['user_id'] = (int) $dbUser['user_id'];
        $_SESSION['role_id'] = (int) ($dbUser['role_id'] ?? 0);
        $_SESSION['role_name'] = $dbUser['role'] ?? $role;
        $_SESSION['full_name'] = $dbUser['full_name'] ?? '';
        $_SESSION['username'] = $dbUser['username'] ?? '';
        $_SESSION['login_id'] = $dbUser['login_id'] ?? '';
 
        auth_login($dbUser);
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
    <link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body class="login-page">

<div class="login-container">
    <aside class="login-panel">
        <h3>Sign in to LMS</h3>
        <p class="muted" style="margin-bottom:20px;">Use your University LMS account to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;font-size:.84rem;border-radius:8px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <?= csrf_field() ?>

            <div class="field" style="margin-bottom:16px;">
                <label>Login As</label>
                <select name="role" required>
                    <option value="teacher" <?= $selected_role === 'teacher' ? 'selected' : '' ?>>Faculty</option>
                    <option value="student" <?= $selected_role === 'student' ? 'selected' : '' ?>>Student</option>
                </select>
            </div>

            <div class="field" style="margin-bottom:16px;">
                <label>User ID</label>
                <input type="text" name="login_id" required placeholder="Enter your ID" autocomplete="username">
            </div>

            <div class="field password-field" style="margin-bottom:20px;">
                <label>Password</label>
                <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;border-radius:8px;font-size:.9rem;">Sign In</button>
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
