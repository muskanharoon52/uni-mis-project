<?php
// teacher_assignment/edit_teacher.php - Edit Teacher

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

$conn = getConnection();
$teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($teacher_id == 0) {
    header('Location: index.php?error=Invalid teacher ID');
    exit;
}

$query = "SELECT * FROM teachers WHERE teacher_id = $teacher_id";
$result = mysqli_query($conn, $query);
$teacher = mysqli_fetch_assoc($result);

if (!$teacher) {
    header('Location: index.php?error=Teacher not found');
    exit;
}

$dept_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments ORDER BY department_name");
$departments = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_name = mysqli_real_escape_string($conn, $_POST['teacher_name']);
    $teacher_code = mysqli_real_escape_string($conn, $_POST['teacher_code']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department_id = (int)$_POST['department_id'];
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $status = $_POST['status'] ?? 'Active';
    
    $update_query = "UPDATE teachers SET 
                      teacher_name = '$teacher_name',
                      teacher_code = '$teacher_code',
                      email = '$email',
                      phone = '$phone',
                      department_id = $department_id,
                      specialization = '$specialization',
                      status = '$status'
                    WHERE teacher_id = $teacher_id";
    
    if (mysqli_query($conn, $update_query)) {
        header('Location: index.php?success=Teacher updated successfully');
        exit;
    } else {
        $error = mysqli_error($conn);
        header('Location: edit_teacher.php?id=' . $teacher_id . '&error=' . urlencode($error));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Edit Teacher</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Teacher Name</label>
                <input type="text" name="teacher_name" value="<?= htmlspecialchars($teacher['teacher_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Teacher Code</label>
                <input type="text" name="teacher_code" value="<?= htmlspecialchars($teacher['teacher_code']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Department</label>
                <select name="department_id">
                    <option value="0">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['department_id'] ?>" <?= ($teacher['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" value="<?= htmlspecialchars($teacher['specialization']) ?>">
            </div>
        </div>

        <div class="form-group" style="max-width:280px;">
            <label>Status</label>
            <select name="status">
                <option value="Active" <?= ($teacher['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($teacher['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Teacher</button>
            <a href="index.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
