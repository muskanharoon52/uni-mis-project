<?php
// teacher_assignment/edit_teacher.php - Edit Teacher

require_once __DIR__ . '../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($teacher_id == 0) {
    header('Location: index.php?error=Invalid teacher ID');
    exit;
}

// Get teacher data
$query = "SELECT * FROM teachers WHERE teacher_id = $teacher_id";
$result = mysqli_query($conn, $query);
$teacher = mysqli_fetch_assoc($result);

if (!$teacher) {
    header('Location: index.php?error=Teacher not found');
    exit;
}

// Get departments
$dept_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments ORDER BY department_name");
$departments = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $row;
}

// Handle form submission
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

<style>
    .main-content { margin-left: 250px; padding: 20px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0; font-weight: 600; }
    .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 10px 30px; border-radius: 10px; font-weight: 600; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); color: white; }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit"></i> Edit Teacher</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-user-edit me-2"></i> Edit Teacher Details</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Teacher Name</label>
                            <input type="text" name="teacher_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Teacher Code</label>
                            <input type="text" name="teacher_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($teacher['teacher_code']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($teacher['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="0">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>" 
                                        <?php echo ($teacher['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Specialization</label>
                            <input type="text" name="specialization" class="form-control" 
                                   value="<?php echo htmlspecialchars($teacher['specialization']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo ($teacher['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($teacher['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <hr>
                            <div class="text-center">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i> Update Teacher
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>