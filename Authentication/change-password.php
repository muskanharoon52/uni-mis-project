<?php
// 1. Authentication/change-password.php
// Change user password

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

requireLogin();
$page_title = 'Change Password';
$conn = getConnection();

$error = '';
$success = '';
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Verify current password
        $user_data = getSingleRecord("SELECT user_id, password_hash FROM users WHERE user_id = ?", [$user['user_id']], 'i');
        
        if ($user_data && $current_password === $user_data['password_hash']) {
            // Update password (in production, hash this)
            $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            if (updateRecord($sql, [$new_password, $user['user_id']], 'si')) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error updating password!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}
?>
<?php include '../../15. Includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../15. Includes/footer.php'; ?>