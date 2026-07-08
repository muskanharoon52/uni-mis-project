<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'profile';
$pageTitle = 'Profile';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $photoPath = save_uploaded_file('profile_photo', 'profiles', ['jpg', 'jpeg', 'png', 'webp']);
        $stmt = db()->prepare(
            'UPDATE users
             SET name = ?, department = ?, program = ?, profile_photo = COALESCE(?, profile_photo)
             WHERE id = ?'
        );
        $stmt->execute([
            trim((string) ($_POST['name'] ?? '')),
            trim((string) ($_POST['department'] ?? '')),
            trim((string) ($_POST['program'] ?? '')),
            $photoPath,
            $user['id'],
        ]);
        $message = 'Profile updated.';
        $user = current_user() ?: $user;
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Profile</h1></div>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
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
    <div class="form-card">
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= e($user['name']) ?>" required>
        <label for="email">Email</label>
        <input id="email" value="<?= e($user['email']) ?>" disabled>
        <label for="department">Department</label>
        <input id="department" name="department" value="<?= e((string) $user['department']) ?>">
        <label for="program">Program</label>
        <input id="program" name="program" value="<?= e((string) $user['program']) ?>">
        <button class="btn" type="submit">Save Profile</button>
    </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
