<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../sso/includes/auth.php';

if (!isLoggedIn()) { header('Location: /uni-mis-project/'); exit; }
$role = strtolower($_SESSION['role_name'] ?? '');
if ($role !== 'super admin' && $role !== 'admin') { header('Location: /uni-mis-project/dashboard.php'); exit; }

$conn = getConnection();
$message = '';
$error = '';

// Handle role/status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $targetId = (int)$_POST['user_id'];
    if ($targetId <= 0) { $error = 'Invalid user.'; }
    else {
        $newRole = (int)$_POST['role_id'];
        $newStatus = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
        $stmt = $conn->prepare("UPDATE users SET role_id = ?, status = ? WHERE user_id = ?");
        $stmt->bind_param('isi', $newRole, $newStatus, $targetId);
        if ($stmt->execute()) $message = 'User updated successfully.';
        else $error = 'Failed to update user.';
    }
}

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int)$_POST['role_id'];
    $email = trim($_POST['email'] ?? '');

    if (empty($fullName) || empty($username) || empty($password)) {
        $error = 'Full name, username, and password are required.';
    } else {
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param('ssssi', $fullName, $username, $email, $hash, $roleId);
            if ($stmt->execute()) $message = "User '$username' created successfully.";
            else $error = 'Failed to create user.';
        }
    }
}

$users = $conn->query("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id");
$roles = $conn->query("SELECT * FROM roles ORDER BY role_id");
$pageTitle = 'User Management';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--navy);">User Management</h3>
        <p style="font-size:.84rem;color:var(--text-secondary);margin-top:2px;">Manage all system users, roles, and access.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addUserModal').style.display='flex'">+ Add User</button>
</div>

<?php if ($message): ?>
<div class="alert alert-success" style="padding:10px 14px;border-radius:8px;margin-bottom:16px;background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error" style="padding:10px 14px;border-radius:8px;margin-bottom:16px;background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger-border);"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="panel" style="margin-bottom:24px;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['user_id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                    <td>
                        <span class="status-badge" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);">
                            <?= htmlspecialchars($u['role_name'] ?? 'Unknown') ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?= strtolower($u['status']) ?>"><?= $u['status'] ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', <?= $u['role_id'] ?>, '<?= $u['status'] ?>')">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:440px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,.3);">
        <h4 style="margin-bottom:16px;font-size:1.1rem;color:var(--navy);">Edit User</h4>
        <form method="post">
            <input type="hidden" name="update_user" value="1">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="field" style="margin-bottom:14px;">
                <label>Full Name</label>
                <input type="text" id="edit-full-name" disabled style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;background:#f5f5f5;">
            </div>
            <div class="field" style="margin-bottom:14px;">
                <label>Role</label>
                <select name="role_id" id="edit-role-id" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
                    <?php $roles->data_seek(0); while ($r = $roles->fetch_assoc()): ?>
                    <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:20px;">
                <label>Status</label>
                <select name="status" id="edit-status" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').style.display='none'" style="flex:1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:440px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,.3);">
        <h4 style="margin-bottom:16px;font-size:1.1rem;color:var(--navy);">Add New User</h4>
        <form method="post">
            <input type="hidden" name="add_user" value="1">
            <div class="field" style="margin-bottom:14px;">
                <label>Full Name</label>
                <input type="text" name="full_name" required style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
            </div>
            <div class="field" style="margin-bottom:14px;">
                <label>Username</label>
                <input type="text" name="username" required style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
            </div>
            <div class="field" style="margin-bottom:14px;">
                <label>Email</label>
                <input type="email" name="email" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
            </div>
            <div class="field" style="margin-bottom:14px;">
                <label>Password</label>
                <input type="text" name="password" required style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
            </div>
            <div class="field" style="margin-bottom:20px;">
                <label>Role</label>
                <select name="role_id" required style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;">
                    <?php $roles->data_seek(0); while ($r = $roles->fetch_assoc()): ?>
                    <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addUserModal').style.display='none'" style="flex:1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, roleId, status) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-full-name').value = name;
    document.getElementById('edit-role-id').value = roleId;
    document.getElementById('edit-status').value = status;
    document.getElementById('editUserModal').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>