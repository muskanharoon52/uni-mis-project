<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Fetch departments for dropdown
$dept_query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_code = trim($_POST['teacher_code'] ?? '');
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($teacher_code) || empty($teacher_name)) {
        $error = 'Teacher code and name are required.';
    } elseif ($department_id <= 0) {
        $error = 'Please select a department.';
    } else {
        // Check if teacher code already exists
        $check_sql = "SELECT teacher_id FROM teachers WHERE teacher_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $teacher_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Teacher code already exists. Please use a unique code.';
        } else {
            // Check if email already exists (if provided)
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
                // Insert teacher
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

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Teacher';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .teacher-form-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-container .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-container .text-danger {
        color: #e74c3c !important;
    }
    
    .form-container .form-control:focus,
    .form-container .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    .btn-submit {
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    @media (max-width: 768px) {
        .teacher-form-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="teacher-form-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-plus"></i> Add New Teacher</h4>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> 
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Teacher Form -->
        <div class="form-container">
            <form method="POST" action="" id="teacherForm">
                <div class="row">
                    <!-- Teacher Code -->
                    <div class="col-md-6 mb-3">
                        <label for="teacher_code" class="form-label">
                            Teacher Code <span class="required-star">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="teacher_code" 
                               name="teacher_code" 
                               placeholder="e.g., TCH001" 
                               required
                               value="<?= htmlspecialchars($_POST['teacher_code'] ?? '') ?>">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Unique code for the teacher
                        </small>
                    </div>
                    
                    <!-- Teacher Name -->
                    <div class="col-md-6 mb-3">
                        <label for="teacher_name" class="form-label">
                            Teacher Name <span class="required-star">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="teacher_name" 
                               name="teacher_name" 
                               placeholder="Full name" 
                               required
                               value="<?= htmlspecialchars($_POST['teacher_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="teacher@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Must be unique
                        </small>
                    </div>
                    
                    <!-- Phone -->
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               placeholder="e.g., 0300-1234567"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- Department -->
                    <div class="col-md-6 mb-3">
                        <label for="department_id" class="form-label">
                            Department <span class="required-star">*</span>
                        </label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php if (!empty($departments)): ?>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>" 
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No departments found. Please add departments first.</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($departments)): ?>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                No departments available. <a href="../departments/add.php">Add a department</a> first.
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Specialization -->
                    <div class="col-md-6 mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" 
                               class="form-control" 
                               id="specialization" 
                               name="specialization" 
                               placeholder="e.g., Computer Science, Mathematics"
                               value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Area of expertise
                        </small>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Active teachers can be assigned to courses
                    </small>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> Add Teacher
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>