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
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password_hash, role, department, program) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'student',
                $department,
                $program,
            ]);

            $newUser = [
                'id' => (int) db()->lastInsertId(),
                'role' => 'student',
            ];
            login_user($newUser);
            redirect_to_dashboard($newUser);
        } catch (PDOException $exception) {
            $error = 'That email is already registered.';
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-hero">
        <span class="auth-brand">University LMS</span>
        <h1>Create your student account.</h1>
        <p>
            Use this form to join the LMS and access courses, attendance, internal marks, fees, and notifications.
        </p>
    </div>

    <section class="form-card auth-panel">
        <h2>Create Account</h2>
        <p class="muted">Register as a student.</p>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="stack">
            <label for="name">Full Name</label>
            <input id="name" name="name" value="<?= old('name') ?>" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= old('email') ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="6" required>

            <label for="department">Department</label>
            <input id="department" name="department" value="<?= old('department', 'Computer Science') ?>">

            <label for="program">Program</label>
            <input id="program" name="program" value="<?= old('program') ?>">

            <button class="btn" type="submit">Create Account</button>
            <a class="btn secondary" href="<?= app_url('public/login.php') ?>">Back to Login</a>
        </form>
    </section>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
