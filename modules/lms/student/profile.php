<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'profile';
$pageTitle = 'Profile';
$message = '';
$error = '';

$departments = [];
$deptStmt = db()->query('SELECT department_id, department_name FROM departments ORDER BY department_name');
$departments = $deptStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $photoPath = save_uploaded_file('profile_photo', 'profiles', ['jpg', 'jpeg', 'png', 'webp']);
        $deptId = (int) ($_POST['department'] ?? 0);
        if ($deptId <= 0) $deptId = null;
        $stmt = db()->prepare(
            'UPDATE users
             SET full_name = ?, department_id = ?, profile_photo = COALESCE(?, profile_photo)
             WHERE user_id = ?'
        );
        $stmt->execute([
            trim((string) ($_POST['name'] ?? '')),
            $deptId,
            $photoPath,
            $user['id'],
        ]);
        $message = 'Profile updated.';
        $user = current_user() ?: $user;
        // Refresh session with updated data
        $stmt = db()->prepare('SELECT u.*, d.department_name FROM users u LEFT JOIN departments d ON d.department_id = u.department_id WHERE u.user_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $updated = $stmt->fetch();
        if ($updated) {
            $_SESSION['lms_auth_user']['name'] = $updated['full_name'] ?? '';
            $_SESSION['lms_auth_user']['department'] = $updated['department_name'] ?? '';
            $_SESSION['lms_auth_user']['department_id'] = (int) ($updated['department_id'] ?? 0);
            if (!empty($updated['profile_photo'])) $_SESSION['lms_auth_user']['profile_photo'] = $updated['profile_photo'];
            $user = $_SESSION['lms_auth_user'];
        }
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form class="profile-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="profile-preview">
        <?php if ($user['profile_photo']): ?>
            <img class="profile-photo" src="<?= app_url($user['profile_photo']) ?>" alt="">
        <?php else: ?>
            <div class="student-avatar"><?= e(strtoupper(substr((string) $user['name'], 0, 1))) ?></div>
        <?php endif; ?>
        <label for="profile_photo">Profile Picture</label>
        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <div class="card">
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= e($user['name']) ?>" required>
        <label for="email">Email</label>
        <input id="email" value="<?= e($user['login_id']) ?>" disabled>
        <label for="department">Department</label>
        <select id="department" name="department">
            <option value="">-- Select Department --</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= (int) $dept['department_id'] ?>" <?= (int) ($user['department_id'] ?? 0) === (int) $dept['department_id'] ? 'selected' : '' ?>><?= e($dept['department_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Save Profile</button>
    </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
