<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSSO();

global $conn;

$errors = [];
$success = '';

// Get programs for dropdown
$program_query = "SELECT program_id as id, program_name as name, program_code FROM programs ORDER BY program_name";
$program_result = $conn->query($program_query);
$programs = $program_result ? $program_result->fetch_all(MYSQLI_ASSOC) : [];

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
        $conn->begin_transaction();
        
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
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param("ii", $program_id, $batch_year);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count = $count_result->fetch_assoc();
            $student_number = ($count['count'] ?? 0) + 1;
            $count_stmt->close();
            
            // 3. Generate student ID
            $student_id = $program_code . '-' . $batch_year . '-' . str_pad($student_number, 3, '0', STR_PAD_LEFT);
            
            // If roll_no is empty, auto-generate
            if (empty($roll_no)) {
                $roll_no = $student_id;
            }
            
            // 4. Insert into users table - FIXED bind_param
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // SQL with 5 placeholders: email, password_hash, full_name, phone, plain_password
            $insert_user = "INSERT INTO users (email, password_hash, full_name, phone, role_id, is_active, plain_password) 
                            VALUES (?, ?, ?, ?, 4, 1, ?)";
            //                             1  2  3  4        5
            //                             ^  ^  ^  ^        ^
            //                           email, hash, name, phone, plain_password
            
            $stmt = $conn->prepare($insert_user);
            // 5 type characters for 5 values: "sssss" (all strings)
            $stmt->bind_param("sssss", $email, $password_hash, $full_name, $phone, $password);
            //               1      2      3      4      5
            //               ^      ^      ^      ^      ^
            //             email  hash   name   phone  plain_pass
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating user: " . $stmt->error);
            }
            $user_id = $conn->insert_id;
            $stmt->close();
            
            // 5. Insert into students table - 11 placeholders
            $insert_student = "INSERT INTO students (
                student_id, roll_no, user_id, program_id, section, 
                batch_year, semester, status, enrollment_date, father_name, session
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            //             1  2  3  4  5  6  7  8  9  10 11
            
            $stmt = $conn->prepare($insert_student);
            // 11 type characters for 11 values: "ssiisississ"
            // s = string, i = integer
            $stmt->bind_param(
                "ssiisississ",
                $student_id,    // 1 - string
                $roll_no,       // 2 - string
                $user_id,       // 3 - integer
                $program_id,    // 4 - integer
                $section,       // 5 - string
                $batch_year,    // 6 - integer
                $semester,      // 7 - integer
                $status,        // 8 - string
                $enrollment_date, // 9 - string
                $father_name,   // 10 - string
                $session        // 11 - string
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating student: " . $stmt->error);
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success = "✅ Student added successfully!<br><br>
                        <strong>Student ID:</strong> $student_id<br>
                        <strong>Roll No:</strong> $roll_no<br>
                        <strong>Login Email:</strong> $email<br>
                        <strong>Password:</strong> $password";
            
            // Clear form on success
            $_POST = [];
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
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
    
    .success-box {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        padding: 20px;
        color: #155724;
        margin-bottom: 20px;
    }
    
    .field-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
</style>

<div class="main-content">
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>