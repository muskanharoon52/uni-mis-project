<?php
// students/add.php - Add Student (COMPLETE FIXED)

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: ../modules/sso/login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

// Only SSO and Admin can add students
if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ../dashboard.php');
    exit;
}

$conn = getConnection();
$errors = [];
$success = '';

// ============================================
// FIX: Add getRow() function if not exists
// ============================================
if (!function_exists('getRow')) {
    function getRow($sql, $params = []) {
        $conn = getConnection();
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
}

// Get programs for dropdown
$program_query = "SELECT program_id as id, program_name as name, program_code FROM programs ORDER BY program_name";
$program_result = mysqli_query($conn, $program_query);
$programs = [];
if ($program_result) {
    while ($row = mysqli_fetch_assoc($program_result)) {
        $programs[] = $row;
    }
}

// Get semesters for dropdown
$semesters_query = "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name";
$semesters_result = mysqli_query($conn, $semesters_query);
$semesters = [];
if ($semesters_result) {
    while ($row = mysqli_fetch_assoc($semesters_result)) {
        $semesters[] = $row;
    }
}

// Get sessions for dropdown
$sessions_query = "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name";
$sessions_result = mysqli_query($conn, $sessions_query);
$sessions = [];
if ($sessions_result) {
    while ($row = mysqli_fetch_assoc($sessions_result)) {
        $sessions[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $program_id = (int)($_POST['program_id'] ?? 0);
    $section = trim($_POST['section'] ?? '');
    $batch_year = (int)($_POST['batch_year'] ?? date('Y'));
    $semester = (int)($_POST['semester'] ?? 1);
    $status = $_POST['status'] ?? 'active';
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
    $session = trim($_POST['session'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');

    // Validation
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($father_name)) $errors[] = "Father's name is required";
    if ($program_id <= 0) $errors[] = "Program is required";
    if ($batch_year <= 0) $errors[] = "Batch year is required";
    if (empty($password)) $errors[] = "Password is required";

    // Check if email already exists
    $check_email = getRow("SELECT user_id FROM users WHERE email = ?", [$email]);
    if ($check_email) {
        $errors[] = "Email already exists in the system";
    }

    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Get program code
            $program_code = '';
            foreach ($programs as $prog) {
                if ($prog['id'] == $program_id) {
                    $program_code = $prog['program_code'];
                    break;
                }
            }
            
            // 2. Count existing students for auto ID
            $count_query = "SELECT COUNT(*) as count FROM students WHERE program_id = ? AND batch_year = ?";
            $count_stmt = mysqli_prepare($conn, $count_query);
            mysqli_stmt_bind_param($count_stmt, "ii", $program_id, $batch_year);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count = mysqli_fetch_assoc($count_result);
            $student_number = ($count['count'] ?? 0) + 1;
            mysqli_stmt_close($count_stmt);
            
            // 3. Generate student ID
            $student_id = $program_code . '-' . $batch_year . '-' . str_pad($student_number, 3, '0', STR_PAD_LEFT);
            
            // If roll_no is empty, auto-generate
            if (empty($roll_no)) {
                $roll_no = $student_id;
            }
            
            // 4. Insert into users table
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Check what columns exist in users table
            $check_columns = "SHOW COLUMNS FROM users";
            $columns_result = mysqli_query($conn, $check_columns);
            $user_columns = [];
            if ($columns_result) {
                while ($col = mysqli_fetch_assoc($columns_result)) {
                    $user_columns[] = $col['Field'];
                }
            }
            
            // Build insert query based on existing columns
            $user_fields = ['email', 'password_hash', 'full_name', 'phone', 'role_id'];
            $user_values = ['?', '?', '?', '?', '4'];
            $bind_params = [$email, $password_hash, $full_name, $phone];
            
            // Check if status column exists
            if (in_array('status', $user_columns)) {
                $user_fields[] = 'status';
                $user_values[] = '?';
                $bind_params[] = 'Active';
            }
            
            // Check if is_active column exists
            if (in_array('is_active', $user_columns)) {
                $user_fields[] = 'is_active';
                $user_values[] = '?';
                $bind_params[] = 1;
            }
            
            // Check if created_at column exists
            if (in_array('created_at', $user_columns)) {
                $user_fields[] = 'created_at';
                $user_values[] = 'NOW()';
            }
            
            $insert_user = "INSERT INTO users (" . implode(', ', $user_fields) . ") 
                            VALUES (" . implode(', ', $user_values) . ")";
            
            $stmt = mysqli_prepare($conn, $insert_user);
            if ($stmt === false) {
                throw new Exception("Error preparing user insert: " . mysqli_error($conn));
            }
            
            // Build types string
            $types = str_repeat('s', count($bind_params));
            mysqli_stmt_bind_param($stmt, $types, ...$bind_params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error creating user: " . mysqli_stmt_error($stmt));
            }
            $user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // 5. Insert into students table
            // Check what columns exist in students table
            $check_student_columns = "SHOW COLUMNS FROM students";
            $student_cols_result = mysqli_query($conn, $check_student_columns);
            $student_columns = [];
            if ($student_cols_result) {
                while ($col = mysqli_fetch_assoc($student_cols_result)) {
                    $student_columns[] = $col['Field'];
                }
            }
            
            $student_fields = ['student_id', 'roll_no', 'user_id', 'program_id', 'batch_year', 'semester', 'status', 'father_name'];
            $student_values = ['?', '?', '?', '?', '?', '?', '?', '?'];
            $student_params = [$student_id, $roll_no, $user_id, $program_id, $batch_year, $semester, $status, $father_name];
            
            // Check if section exists
            if (in_array('section', $student_columns)) {
                $student_fields[] = 'section';
                $student_values[] = '?';
                $student_params[] = $section;
            }
            
            // Check if session exists
            if (in_array('session', $student_columns)) {
                $student_fields[] = 'session';
                $student_values[] = '?';
                $student_params[] = $session;
            }
            
            // Check if enrollment_date exists
            if (in_array('enrollment_date', $student_columns)) {
                $student_fields[] = 'enrollment_date';
                $student_values[] = '?';
                $student_params[] = $enrollment_date;
            }
            
            // Check if created_at exists
            if (in_array('created_at', $student_columns)) {
                $student_fields[] = 'created_at';
                $student_values[] = 'NOW()';
            }
            
            $insert_student = "INSERT INTO students (" . implode(', ', $student_fields) . ") 
                              VALUES (" . implode(', ', $student_values) . ")";
            
            $stmt = mysqli_prepare($conn, $insert_student);
            if ($stmt === false) {
                throw new Exception("Error preparing student insert: " . mysqli_error($conn));
            }
            
            // Build types string for student
            $student_types = '';
            foreach ($student_params as $param) {
                if (is_int($param)) {
                    $student_types .= 'i';
                } else {
                    $student_types .= 's';
                }
            }
            
            mysqli_stmt_bind_param($stmt, $student_types, ...$student_params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error creating student: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success = "✅ Student added successfully!<br><br>
                        <strong>Student ID:</strong> $student_id<br>
                        <strong>Roll No:</strong> $roll_no<br>
                        <strong>Login Email:</strong> $email<br>
                        <strong>Password:</strong> $password";
            
            // Clear form on success
            $_POST = [];
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-plus"></i> Add New Student</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <strong>Success!</strong><br>
                <?php echo $success; ?>
                <br><br>
                <a href="add.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Another Student
                </a>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list"></i> View All Students
                </a>
            </div>
        <?php endif; ?>

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
            <!-- Personal Information -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-user text-primary"></i> Personal Information
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Full Name</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <div class="field-hint">Student's login email</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Temporary Password</label>
                        <input type="text" name="password" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required>
                        <div class="field-hint">Student can change this after login</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Roll Number</label>
                        <input type="text" name="roll_no" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['roll_no'] ?? ''); ?>"
                               placeholder="Leave blank to auto-generate">
                        <div class="field-hint">Auto-generated if left blank</div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-graduation-cap text-success"></i> Academic Information
                </h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Program</label>
                        <select name="program_id" class="form-select" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" 
                                    <?php echo ($_POST['program_id'] ?? '') == $prog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Section</label>
                        <input type="text" name="section" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['section'] ?? ''); ?>" 
                               placeholder="A, B, C">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="">Select</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" 
                                    <?php echo ($_POST['semester'] ?? '') == $i ? 'selected' : ''; ?>>
                                    <?php 
                                    $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
                                    echo $ordinal[$i-1] . ' Semester'; 
                                    ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Batch Year</label>
                        <input type="number" name="batch_year" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['batch_year'] ?? date('Y')); ?>" 
                               min="2000" max="2030" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Session</label>
                        <input type="text" name="session" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['session'] ?? ''); ?>" 
                               placeholder="Fall 2026">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Enrollment Date</label>
                        <input type="date" name="enrollment_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['enrollment_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($_POST['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="confirmed" <?php echo ($_POST['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="pending" <?php echo ($_POST['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Student
                </button>
            </div>
        </form>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>