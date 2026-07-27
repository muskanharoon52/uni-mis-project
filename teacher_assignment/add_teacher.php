<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// ============================================
// FIX: Check what columns exist in teachers table
// ============================================
$check_columns = "SHOW COLUMNS FROM teachers";
$cols_result = mysqli_query($conn, $check_columns);
$teacher_columns = [];
if ($cols_result) {
    while ($col = mysqli_fetch_assoc($cols_result)) {
        $teacher_columns[] = $col['Field'];
    }
}

// Determine available columns
$has_teacher_code = in_array('teacher_code', $teacher_columns);
$has_teacher_name = in_array('teacher_name', $teacher_columns) || in_array('full_name', $teacher_columns) || in_array('name', $teacher_columns);
$has_email = in_array('email', $teacher_columns);
$has_phone = in_array('phone', $teacher_columns) || in_array('contact_no', $teacher_columns);
$has_department_id = in_array('department_id', $teacher_columns);
$has_specialization = in_array('specialization', $teacher_columns);
$has_status = in_array('status', $teacher_columns);
$has_employee_id = in_array('employee_id', $teacher_columns);

// Get the correct name column
$name_column = 'teacher_name';
if (in_array('full_name', $teacher_columns)) {
    $name_column = 'full_name';
} elseif (in_array('name', $teacher_columns)) {
    $name_column = 'name';
}

// Get the correct phone column
$phone_column = 'phone';
if (in_array('contact_no', $teacher_columns)) {
    $phone_column = 'contact_no';
}

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
    if (empty($teacher_code) && $has_teacher_code) {
        $error = 'Teacher code is required.';
    } elseif (empty($teacher_name)) {
        $error = 'Teacher name is required.';
    } elseif ($department_id <= 0 && $has_department_id) {
        $error = 'Please select a department.';
    } else {
        // Build insert query based on existing columns
        $insert_fields = [];
        $insert_values = [];
        $bind_types = "";
        $bind_params = [];

        if ($has_teacher_code && !empty($teacher_code)) {
            // Check if teacher code already exists
            $check_sql = "SELECT teacher_id FROM teachers WHERE teacher_code = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $teacher_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'Teacher code already exists. Please use a unique code.';
            }
            $check_stmt->close();
            
            if (empty($error)) {
                $insert_fields[] = 'teacher_code';
                $insert_values[] = '?';
                $bind_params[] = $teacher_code;
                $bind_types .= "s";
            }
        }

        if (empty($error)) {
            // Teacher name
            if ($has_teacher_name) {
                $insert_fields[] = $name_column;
                $insert_values[] = '?';
                $bind_params[] = $teacher_name;
                $bind_types .= "s";
            }

            // Email
            if ($has_email && !empty($email)) {
                // Check if email already exists
                $email_check_sql = "SELECT teacher_id FROM teachers WHERE email = ?";
                $email_check_stmt = $conn->prepare($email_check_sql);
                $email_check_stmt->bind_param("s", $email);
                $email_check_stmt->execute();
                $email_check_result = $email_check_stmt->get_result();
                
                if ($email_check_result->num_rows > 0) {
                    $error = 'Email already exists. Please use a unique email.';
                }
                $email_check_stmt->close();
                
                if (empty($error)) {
                    $insert_fields[] = 'email';
                    $insert_values[] = '?';
                    $bind_params[] = $email;
                    $bind_types .= "s";
                }
            }

            // Phone
            if ($has_phone && !empty($phone)) {
                $insert_fields[] = $phone_column;
                $insert_values[] = '?';
                $bind_params[] = $phone;
                $bind_types .= "s";
            }

            // Department
            if ($has_department_id && $department_id > 0) {
                $insert_fields[] = 'department_id';
                $insert_values[] = '?';
                $bind_params[] = $department_id;
                $bind_types .= "i";
            }

            // Specialization
            if ($has_specialization && !empty($specialization)) {
                $insert_fields[] = 'specialization';
                $insert_values[] = '?';
                $bind_params[] = $specialization;
                $bind_types .= "s";
            }

            // Status
            if ($has_status) {
                $insert_fields[] = 'status';
                $insert_values[] = '?';
                $bind_params[] = $status;
                $bind_types .= "s";
            }

            // Created at (if exists)
            if (in_array('created_at', $teacher_columns)) {
                $insert_fields[] = 'created_at';
                $insert_values[] = 'NOW()';
            }

            if (empty($error) && !empty($insert_fields)) {
                $insert_sql = "INSERT INTO teachers (" . implode(', ', $insert_fields) . ") 
                               VALUES (" . implode(', ', $insert_values) . ")";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if ($insert_stmt === false) {
                    $error = "Error preparing insert: " . $conn->error;
                } else {
                    if (!empty($bind_params)) {
                        $insert_stmt->bind_param($bind_types, ...$bind_params);
                    }
                    
                    if ($insert_stmt->execute()) {
                        header("Location: index.php?success=Teacher added successfully!");
                        exit();
                    } else {
                        $error = "Error adding teacher: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
        }
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
                    <?php if ($has_teacher_code): ?>
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
                    <?php endif; ?>
                    
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
                    <?php if ($has_email): ?>
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
                    <?php endif; ?>
                    
                    <!-- Phone -->
                    <?php if ($has_phone): ?>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               placeholder="e.g., 0300-1234567"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <!-- Department -->
                    <?php if ($has_department_id): ?>
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
                    <?php endif; ?>
                    
                    <!-- Specialization -->
                    <?php if ($has_specialization): ?>
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
                    <?php endif; ?>
                </div>

                <!-- Status -->
                <?php if ($has_status): ?>
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
                <?php endif; ?>

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