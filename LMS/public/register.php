<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = current_user();

if ($user) {
    redirect_to_dashboard($user);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $department = trim((string) ($_POST['department'] ?? 'Computer Science'));
    $program = trim((string) ($_POST['program'] ?? ''));

    if ($name === '' || $email === '' || strlen($password) < 6) {
        $error = 'Please fill all required fields. Password must be at least 6 characters.';
    } else {
        try {
            $loginId = '9' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $stmt = db()->prepare(
                'INSERT INTO users (login_id, name, email, password_hash, role, department, program) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $loginId,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'student',
                $department,
                $program,
            ]);

            $newUser = [
                'id'           => (int) db()->lastInsertId(),
                'login_id'     => $loginId,
                'role'         => 'student',
                'name'         => $name,
                'department'   => $department,
                'program'      => $program,
                'profile_photo'=> '',
            ];
            login_user($newUser);
            redirect_to_dashboard($newUser);
        } catch (PDOException $exception) {
            $error = 'That email is already registered.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | University LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">LMS</div>
        </div>
        <div class="login-title">
            <h1>Join the University LMS</h1>
            <p>Create your student account to access courses, attendance, marks, fees, and more.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">STU</div>
                <div>
                    <strong>Student Registration</strong>
                    <p class="small">Fill in your details to get started.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">SYS</div>
                <div>
                    <strong>Instant Access</strong>
                    <p class="small">Your account is created immediately.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Create Student Account</h3>
        <p class="muted" style="margin-bottom:22px;">Register as a new student.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;font-size:.84rem;border-radius:8px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <div class="field" style="margin-bottom:14px;">
                <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Full Name</label>
                <input type="text" name="name" required value="<?= old('name') ?>" placeholder="Enter your full name" style="min-height:42px;border-radius:8px;">
            </div>

            <div class="field" style="margin-bottom:14px;">
                <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Email</label>
                <input type="email" name="email" required value="<?= old('email') ?>" placeholder="Enter your email" style="min-height:42px;border-radius:8px;">
            </div>

            <div class="field password-field" style="margin-bottom:14px;">
                <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Password</label>
                <input type="password" name="password" id="pass-field" required minlength="6" placeholder="At least 6 characters" style="min-height:42px;border-radius:8px;padding-right:42px;">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <div class="inline-form-row" style="margin-bottom:14px;">
                <div class="field" style="margin-bottom:0;">
                    <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Department</label>
                    <input type="text" name="department" value="<?= old('department', 'Computer Science') ?>" style="min-height:42px;border-radius:8px;">
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label style="font-size:.84rem;font-weight:600;color:var(--text-strong);display:block;margin-bottom:6px;">Program</label>
                    <input type="text" name="program" value="<?= old('program') ?>" placeholder="e.g. BS CS" style="min-height:42px;border-radius:8px;">
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;border-radius:8px;font-size:.9rem;">Create Account</button>
        </form>

        <div class="login-footer">
            <p class="small">Already have an account? <a href="<?= app_url('public/login.php') ?>" style="color:var(--accent);font-weight:600;">Sign In</a></p>
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
