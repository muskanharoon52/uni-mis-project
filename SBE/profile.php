<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(); // Requires any logged-in user

$db = db();
$user = current_user();
$userId = (int) $user['auth_id'];
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    
    if (empty($displayName)) {
        $message = 'Display Name cannot be empty.';
        $messageType = 'error';
    } else {
        try {
            if ($userId > 0) {
                // Database-backed user
                if (!empty($newPassword)) {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare('UPDATE auth_users SET display_name = :display_name, password_hash = :password_hash WHERE auth_id = :auth_id');
                    $stmt->execute([
                        ':display_name' => $displayName,
                        ':password_hash' => $passwordHash,
                        ':auth_id' => $userId
                    ]);
                } else {
                    $stmt = $db->prepare('UPDATE auth_users SET display_name = :display_name WHERE auth_id = :auth_id');
                    $stmt->execute([
                        ':display_name' => $displayName,
                        ':auth_id' => $userId
                    ]);
                }
                
                // Update session
                $_SESSION['auth_user']['display_name'] = $displayName;
                $message = 'Profile updated successfully.';
            } else {
                // Virtual/Fallback demo user (auth_id = 0)
                $_SESSION['auth_user']['display_name'] = $displayName;
                $message = 'Profile updated successfully in current session (Demo Mode).';
            }
            
            // Refresh local variable
            $user = current_user();
        } catch (Throwable $exception) {
            $message = 'Failed to update profile: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }
}

$pageTitle = 'My Profile';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="page-head">
        <div>
            <h2>My Profile</h2>
            <p>View and manage your account configurations inside SBE ERP.</p>
        </div>
        <div class="actions">
            <?php if ($user['role'] === 'teacher'): ?>
                <a class="btn btn-ghost" href="teacher-home.php">← Teacher Portal</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="student-home.php">← Student Portal</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:18px;">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Profile update card -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;">Edit Profile</h3>
            <p class="small" style="margin:0 0 4px;">Update details for your current logged-in role.</p>

            <form method="post">
                <!-- Group: Account Info -->
                <div class="form-group-title">👤 Personal Info</div>
                <div class="form-grid">
                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Display Name</label>
                        <input type="text" name="display_name" required value="<?= e($user['display_name']) ?>" placeholder="e.g. Professor Jane Doe">
                    </div>
                </div>

                <!-- Group: Authentication Info -->
                <div class="form-group-title">🔑 Credentials</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Role</label>
                        <input type="text" disabled value="<?= e(ucfirst($user['role'])) ?>" style="background-color: var(--panel-strong); border-color: var(--border);">
                    </div>
                    <div class="field">
                        <label>Login ID</label>
                        <input type="text" disabled value="<?= e($user['login_id']) ?>" style="background-color: var(--panel-strong); border-color: var(--border);">
                    </div>
                    
                    <?php if ($userId > 0): ?>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label>New Password <span class="small">(leave blank to keep current)</span></label>
                            <input type="password" name="new_password" placeholder="Enter new password" autocomplete="new-password">
                        </div>
                    <?php else: ?>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label>New Password</label>
                            <input type="password" disabled value="" placeholder="Disabled in Demo mode" style="background-color: var(--panel-strong); border-color: var(--border);">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions" style="margin-top:20px;">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                    <?php if ($user['role'] === 'teacher'): ?>
                        <a class="btn btn-ghost" href="teacher-home.php">Cancel</a>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="student-home.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="card">
            <h3 style="margin-bottom:12px;">ERP Reference Info</h3>
            <p class="small" style="margin-bottom:12px; line-height:1.6;">
                Your login credentials and account status are managed centrally under the Authentication Layer.
            </p>
            <div style="display:grid; gap:8px; font-size:.85rem; color:var(--text);">
                <div>Status: <span class="badge active" style="text-transform:uppercase;"><?= e($user['status']) ?></span></div>
                <div>Account Type: <strong><?= $userId > 0 ? 'Database Backed' : 'Virtual Session User (Demo)' ?></strong></div>
                <div>Created At: <span class="small">ERP SSO sync parameters</span></div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
