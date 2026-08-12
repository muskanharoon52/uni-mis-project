<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$config = require __DIR__ . '/config/app.php';

if (current_user()) {
    redirect(current_user()['role'] === 'Student' ? 'student-home.php' : 'teacher-home.php');
}

$error = '';
$formRole = 'Teacher';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $loginId = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $formRole = $_POST['role'] === 'Student' ? 'Student' : 'Teacher';

    if ($loginId === '' || $password === '') {
        $error = 'Please enter your Login ID and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM sbe_auth_users WHERE login_id = ? AND role = ? LIMIT 1');
        $stmt->execute([$loginId, $formRole]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid Login ID or password.';
        } elseif (($user['status'] ?? 'Active') !== 'Active') {
            $error = 'Your account is inactive. Contact the administrator.';
        } else {
            auth_login($user);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            redirect($formRole === 'Student' ? 'student-home.php' : 'teacher-home.php');
        }
    }
}

$pageTitle = 'Sign In';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-container">
        <div class="login-panel">
            <h3>Sign In</h3>
            <p class="muted">System Based Examination Portal</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" style="margin-bottom:16px; font-size:.82rem;"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="login-tabs">
                <button type="button" class="login-tab <?= $formRole === 'Teacher' ? 'active' : '' ?>" data-tab="Teacher" onclick="switchTab('Teacher')">Teacher</button>
                <button type="button" class="login-tab <?= $formRole === 'Student' ? 'active' : '' ?>" data-tab="Student" onclick="switchTab('Student')">Student</button>
            </div>

            <form class="login-form-pane <?= $formRole === 'Teacher' ? 'active' : '' ?>" data-role="Teacher" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="Teacher">
                <div class="field">
                    <label for="teacher_id">Teacher Login ID</label>
                    <input type="text" id="teacher_id" name="login_id" value="<?= e(old($_POST, 'login_id', '5001')) ?>" placeholder="e.g. 5001" required>
                </div>
                <div class="field" style="margin-top:12px;">
                    <label for="teacher_pass">Password</label>
                    <input type="password" id="teacher_pass" name="password" placeholder="Enter password" required>
                </div>
                <button class="btn btn-primary" type="submit">Sign In</button>
            </form>

            <form class="login-form-pane <?= $formRole === 'Student' ? 'active' : '' ?>" data-role="Student" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="Student">
                <div class="field">
                    <label for="student_id">Student Login ID</label>
                    <input type="text" id="student_id" name="login_id" value="<?= e(old($_POST, 'login_id', '9001')) ?>" placeholder="e.g. 9001" required>
                </div>
                <div class="field" style="margin-top:12px;">
                    <label for="student_pass">Password</label>
                    <input type="password" id="student_pass" name="password" placeholder="Enter password" required>
                </div>
                <button class="btn btn-primary" type="submit">Sign In</button>
            </form>

            <div class="login-footer">
                <span class="small">Demo &mdash; Teacher: <strong>5001</strong> / <strong>teacher123</strong> &middot; Student: <strong>9001</strong> / <strong>student123</strong></span>
                <div style="margin-top:8px;">
                    <a href="index.php" style="color:rgba(255,255,255,0.7); font-size:.78rem;">&larr; Back to portal</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function switchTab(role) {
    document.querySelectorAll('.login-tab').forEach(function (t) {
        t.classList.toggle('active', t.dataset.tab === role);
    });
    document.querySelectorAll('.login-form-pane').forEach(function (p) {
        p.classList.toggle('active', p.dataset.role === role);
    });
}
</script>
</body>
</html>
