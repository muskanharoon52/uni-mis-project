<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

$dept_query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_code = trim($_POST['teacher_code'] ?? '');
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (empty($teacher_code) || empty($teacher_name)) {
        $error = 'Teacher code and name are required.';
    } elseif ($department_id <= 0) {
        $error = 'Please select a department.';
    } else {
        $check_sql = "SELECT teacher_id FROM teachers WHERE teacher_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $teacher_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Teacher code already exists. Please use a unique code.';
        } else {
            if (!empty($email)) {
                $email_check_sql = "SELECT teacher_id FROM teachers WHERE email = ?";
                $email_check_stmt = $conn->prepare($email_check_sql);
                $email_check_stmt->bind_param("s", $email);
                $email_check_stmt->execute();
                $email_check_result = $email_check_stmt->get_result();
                
                if ($email_check_result->num_rows > 0) {
                    $error = 'Email already exists. Please use a unique email.';
                }
                $email_check_stmt->close();
            }
            
            if (empty($error)) {
                $insert_sql = "INSERT INTO teachers (teacher_code, teacher_name, email, phone, department_id, specialization, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssssiss", $teacher_code, $teacher_name, $email, $phone, $department_id, $specialization, $status);
                
                if ($insert_stmt->execute()) {
                    header("Location: index.php?success=Teacher added successfully!");
                    exit();
                } else {
                    $error = "Error adding teacher: " . $conn->error;
                }
                $insert_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Teacher';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Add New Teacher</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Teacher Code <span class="required-star">*</span></label>
                <input type="text" name="teacher_code" placeholder="e.g., TCH001" required value="<?= htmlspecialchars($_POST['teacher_code'] ?? '') ?>">
                <div class="hint">Unique code for the teacher</div>
            </div>
            <div class="form-group">
                <label>Teacher Name <span class="required-star">*</span></label>
                <input type="text" name="teacher_name" placeholder="Full name" required value="<?= htmlspecialchars($_POST['teacher_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="teacher@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <div class="hint">Must be unique</div>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="e.g., 0300-1234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Department <span class="required-star">*</span></label>
                <select name="department_id" required>
                    <option value="">Select Department</option>
                    <?php if (!empty($departments)): ?>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No departments found</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" placeholder="e.g., Computer Science, Mathematics" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                <div class="hint">Area of expertise</div>
            </div>
        </div>

        <div class="form-group" style="max-width:280px;">
            <label>Status</label>
            <select name="status">
                <option value="Active" <?= (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Teacher</button>
            <button type="reset" class="btn btn-outline">Reset</button>
            <a href="index.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
