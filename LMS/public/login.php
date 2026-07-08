<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = current_user();

if ($user) {
    redirect_to_dashboard($user);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $found = $stmt->fetch();

    if ($found && password_verify($password, $found['password_hash'])) {
        login_user($found);
        redirect_to_dashboard($found);
    }

    $error = 'Invalid email or password.';
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-hero">
        <span class="auth-brand">University LMS</span>
        <h1>Faculty and student portal in one place.</h1>
        <p>
            Sign in to manage attendance, internal marks, assignments, announcements, and student records from a single dashboard.
        </p>
        <div class="auth-points">
            <span>Secure login</span>
            <span>Teacher tools</span>
            <span>Student records</span>
        </div>
        <div class="auth-note">
            <strong>Demo accounts</strong>
            <p>teacher@lms.test / password123</p>
            <p>student@lms.test / password123</p>
        </div>
    </div>

    <section class="form-card auth-panel">
        <h2>Login</h2>
        <p class="muted">Use your University LMS account to continue.</p>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="stack">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= old('email') ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button class="btn" type="submit">Login</button>
            <a class="btn secondary" href="<?= app_url('public/register.php') ?>">Create Account</a>
        </form>
    </section>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
