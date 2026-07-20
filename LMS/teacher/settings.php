<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'settings';
$pageTitle = 'Settings';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'profile') {
            $photoPath = save_uploaded_file('profile_photo', 'profiles', ['jpg', 'jpeg', 'png', 'webp']);
            $stmt = db()->prepare(
                'UPDATE users
                 SET full_name = ?, department_id = ?, profile_photo = COALESCE(?, profile_photo)
                 WHERE user_id = ?'
            );
            $stmt->execute([
                trim((string) ($_POST['name'] ?? '')),
                trim((string) ($_POST['department'] ?? '')),
                $photoPath,
                $user['id'],
            ]);
            $message = 'Profile settings updated.';
        } elseif ($action === 'password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($newPassword === '' || strlen($newPassword) < 6) {
                throw new RuntimeException('New password must be at least 6 characters.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('Passwords do not match.');
            }

            $verifyStmt = db()->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
            $verifyStmt->execute([$user['id']]);
            $passwordHash = (string) $verifyStmt->fetchColumn();

            if (!password_verify($currentPassword, $passwordHash)) {
                throw new RuntimeException('Current password is incorrect.');
            }

            $updateStmt = db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $updateStmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            $message = 'Password updated successfully.';
        }

        $user = current_user() ?: $user;
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$courseStmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = ?');
$courseStmt->execute([$user['id']]);
$courseCount = (int) $courseStmt->fetchColumn();

$studentStmt = db()->prepare(
    'SELECT COUNT(DISTINCT e.student_user_id)
     FROM lms_enrollments e
     JOIN courses c ON c.course_id = e.course_id
     WHERE c.teacher_id = ?'
);
$studentStmt->execute([$user['id']]);
$studentCount = (int) $studentStmt->fetchColumn();

$initials = strtoupper(substr((string) $user['name'], 0, 2));

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="settings-layout">

    <!-- ── Left sidebar: account card ── -->
    <aside class="settings-sidebar">
        <div class="settings-profile-card">
            <?php if ($user['profile_photo']): ?>
                <img class="settings-avatar-img" src="<?= app_url($user['profile_photo']) ?>" alt="">
            <?php else: ?>
                <div class="settings-avatar-initials"><?= e($initials) ?></div>
            <?php endif; ?>
            <div class="settings-profile-name"><?= e($user['name']) ?></div>
            <div class="settings-profile-role"><?= e(ucfirst($user['role'])) ?></div>
            <div class="settings-profile-email"><?= e($user['email']) ?></div>
        </div>

        <div class="settings-stats-card">
            <div class="settings-stat">
                <span class="settings-stat-num"><?= $courseCount ?></span>
                <span class="settings-stat-lbl">Courses</span>
            </div>
            <div class="settings-stat-divider"></div>
            <div class="settings-stat">
                <span class="settings-stat-num"><?= $studentCount ?></span>
                <span class="settings-stat-lbl">Students</span>
            </div>
        </div>

        <div class="settings-meta-card">
            <div class="settings-meta-row">
                <span class="settings-meta-key">Department</span>
                <span class="settings-meta-val"><?= e($user['department'] ?: '—') ?></span>
            </div>
            <div class="settings-meta-row">
                <span class="settings-meta-key">Role</span>
                <span class="settings-meta-val"><span class="badge badge-active"><?= e(ucfirst($user['role'])) ?></span></span>
            </div>
            <div class="settings-meta-row">
                <span class="settings-meta-key">Member since</span>
                <span class="settings-meta-val"><?= e(date('M Y', strtotime((string) $user['created_at']))) ?></span>
            </div>
        </div>
    </aside>

    <!-- ── Right: forms ── -->
    <div class="settings-forms">

        <!-- Profile form -->
        <div class="settings-section-card">
            <div class="settings-section-header">
                <div class="settings-section-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
                <div>
                    <h2>Profile Information</h2>
                    <p class="muted">Update your display name, department, and profile photo.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="settings-form-body">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">

                <!-- Photo upload row -->
                <div class="settings-photo-row">
                    <div class="settings-photo-preview">
                        <?php if ($user['profile_photo']): ?>
                            <img class="settings-avatar-img" src="<?= app_url($user['profile_photo']) ?>" alt="" id="photoPreview">
                        <?php else: ?>
                            <div class="settings-avatar-initials" id="photoPreview"><?= e($initials) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="settings-photo-info">
                        <label for="profile_photo" class="settings-upload-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload New Photo
                        </label>
                        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp" class="settings-file-input">
                        <p class="settings-photo-hint">JPG, PNG or WEBP · max 10 MB</p>
                    </div>
                </div>

                <div class="settings-fields-grid">
                    <div class="settings-field">
                        <label for="name">Full Name</label>
                        <input id="name" name="name" value="<?= e($user['name']) ?>" required placeholder="Dr. Sara Khan">
                    </div>
                    <div class="settings-field">
                        <label for="email">Email Address <span class="settings-readonly-badge">Read-only</span></label>
                        <input id="email" value="<?= e($user['email']) ?>" disabled placeholder="your@email.com">
                    </div>
                    <div class="settings-field">
                        <label for="department">Department</label>
                        <input id="department" name="department" value="<?= e((string) $user['department']) ?>" placeholder="e.g. Computer Science">
                    </div>
                </div>

                <div class="settings-form-footer">
                    <button class="btn btn-primary" type="submit">Save Profile Changes</button>
                </div>
            </form>
        </div>

        <!-- Password form -->
        <div class="settings-section-card">
            <div class="settings-section-header">
                <div class="settings-section-icon settings-section-icon--danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div>
                    <h2>Security &amp; Password</h2>
                    <p class="muted">Choose a strong password of at least 6 characters.</p>
                </div>
            </div>

            <form method="post" class="settings-form-body">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">

                <div class="settings-fields-grid">
                    <div class="settings-field settings-field--full">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" name="current_password" type="password" required placeholder="Enter your current password" autocomplete="current-password">
                    </div>
                    <div class="settings-field">
                        <label for="new_password">New Password</label>
                        <input id="new_password" name="new_password" type="password" minlength="6" required placeholder="Min. 6 characters" autocomplete="new-password">
                    </div>
                    <div class="settings-field">
                        <label for="confirm_password">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" minlength="6" required placeholder="Repeat new password" autocomplete="new-password">
                    </div>
                </div>

                <div class="settings-form-footer">
                    <button class="btn btn-primary" type="submit">Update Password</button>
                    <p class="settings-security-tip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        After updating your password you'll remain logged in on this device.
                    </p>
                </div>
            </form>
        </div>

    </div><!-- /.settings-forms -->
</div><!-- /.settings-layout -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
