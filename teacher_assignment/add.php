<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$errors = [];
$success = false;

// Get departments for dropdown
$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($teacher_name)) $errors[] = "Teacher name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($department_id) || $department_id <= 0) $errors[] = "Department is required";

    // Check if email already exists
    if (empty($errors)) {
        $check_query = "SELECT teacher_id FROM teachers WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
        $check_stmt->close();
    }

    // Insert teacher
    if (empty($errors)) {
        $insert_query = "INSERT INTO teachers (teacher_name, designation, email, phone, department_id, status) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt === false) {
            die("Error in insert query: " . $conn->error);
        }
        
        $stmt->bind_param("ssssis", $teacher_name, $designation, $email, $phone, $department_id, $status);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Teacher added successfully");
            exit;
        } else {
            $errors[] = "Error adding teacher: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Add Teacher';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .form-section-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f2f5;
    }
    
    .required-field::after {
        content: '*';
        color: #e74c3c;
        margin-left: 4px;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-user-plus"></i> Add New Teacher</h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <h6 class="form-section-title">
                <i class="fas fa-user text-primary"></i> Personal Information
            </h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="required-field">Full Name</label>
                    <input type="text" name="teacher_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['teacher_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Designation</label>
                    <select name="designation" class="form-select">
                        <option value="">Select Designation</option>
                        <option value="Professor" <?php echo ($_POST['designation'] ?? '') == 'Professor' ? 'selected' : ''; ?>>Professor</option>
                        <option value="Associate Professor" <?php echo ($_POST['designation'] ?? '') == 'Associate Professor' ? 'selected' : ''; ?>>Associate Professor</option>
                        <option value="Assistant Professor" <?php echo ($_POST['designation'] ?? '') == 'Assistant Professor' ? 'selected' : ''; ?>>Assistant Professor</option>
                        <option value="Senior Lecturer" <?php echo ($_POST['designation'] ?? '') == 'Senior Lecturer' ? 'selected' : ''; ?>>Senior Lecturer</option>
                        <option value="Lecturer" <?php echo ($_POST['designation'] ?? '') == 'Lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Department</label>
                    <select name="department_id" class="form-select" required>
                        <option value="0">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                <?php echo ($_POST['department_id'] ?? 0) == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?php echo ($_POST['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($_POST['status'] ?? 'Active') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Teacher
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>