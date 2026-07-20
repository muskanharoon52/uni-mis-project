<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSSO();

// ============================================
// ALL PROCESSING FIRST
// ============================================

global $conn;

$id = isset($_GET['id']) ? $_GET['id'] : '';
$errors = [];
$success = '';

// ID Check
if (empty($id)) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

// Get student data with user info
$query = "SELECT s.*, u.user_id, u.full_name, u.email, u.phone 
          FROM students s
          LEFT JOIN users u ON s.user_id = u.user_id
          WHERE s.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Student exists?
if (!$student) {
    header("Location: index.php?error=Student not found");
    exit;
}

// Get programs for dropdown
$program_query = "SELECT program_id as id, program_name as name FROM programs ORDER BY program_name";
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
    $batch_year = (int)($_POST['batch_year'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $session = trim($_POST['session'] ?? '');

    // Validation
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($father_name)) $errors[] = "Father's name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if ($program_id <= 0) $errors[] = "Program is required";
    if ($batch_year <= 0) $errors[] = "Batch year is required";

    // Update student and user
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Update users table
            $update_user = "UPDATE users SET 
                full_name = ?, 
                email = ?, 
                phone = ? 
                WHERE user_id = ?";
            
            $stmt = $conn->prepare($update_user);
            $stmt->bind_param("sssi", $full_name, $email, $phone, $student['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating user: " . $stmt->error);
            }
            $stmt->close();
            
            // 2. Update students table
            $update_student = "UPDATE students SET 
                father_name = ?, 
                program_id = ?, 
                section = ?, 
                batch_year = ?, 
                semester = ?, 
                status = ?, 
                enrollment_date = ?, 
                roll_no = ?,
                session = ?
                WHERE student_id = ?";

            $stmt = $conn->prepare($update_student);
            $stmt->bind_param(
                "sisiisssss",
                $father_name,
                $program_id,
                $section,
                $batch_year,
                $semester,
                $status,
                $enrollment_date,
                $roll_no,
                $session,
                $id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating student: " . $stmt->error);
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Refresh student data
            $query = "SELECT s.*, u.user_id, u.full_name, u.email, u.phone 
                      FROM students s
                      LEFT JOIN users u ON s.user_id = u.user_id
                      WHERE s.student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            
            // Redirect with success
            header("Location: view.php?id=$id&success=updated");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// ============================================
// NOW INCLUDE HEADER AND SIDEBAR
// ============================================
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

    .student-id-badge {
        font-family: 'Courier New', monospace;
        background: #f8f9fa;
        padding: 5px 15px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        font-weight: bold;
    }

    .form-control:disabled {
        background: #e9ecef;
        cursor: not-allowed;
    }

    .field-note {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit"></i> Edit Student</h4>
            <div>
                <span class="student-id-badge me-3">
                    <i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($student['student_id']); ?>
                </span>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
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
                               value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" required>
                        <div class="field-note">Student's full name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                        <div class="field-note">Valid email address for login</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                        <div class="field-note">Contact number</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required>
                        <div class="field-note">Student's father/guardian name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Roll Number</label>
                        <input type="text" name="roll_no" class="form-control" 
                               value="<?php echo htmlspecialchars($student['roll_no'] ?? ''); ?>"
                               placeholder="e.g., CS-2026-001">
                        <div class="field-note">Format: Program-Batch-Year-Sequence</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Student ID</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
                        <div class="field-note">Auto-generated, cannot be changed</div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-graduation-cap text-success"></i> Academic Information
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Program</label>
                        <select name="program_id" class="form-select" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" 
                                    <?php echo ($student['program_id'] ?? '') == $prog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Section</label>
                        <input type="text" name="section" class="form-control" 
                               value="<?php echo htmlspecialchars($student['section'] ?? ''); ?>" 
                               placeholder="e.g., A, B, C">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" 
                                    <?php echo ($student['semester'] ?? '') == $i ? 'selected' : ''; ?>>
                                    <?php 
                                    $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
                                    echo $ordinal[$i-1] . ' Semester'; 
                                    ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Batch Year</label>
                        <input type="number" name="batch_year" class="form-control" 
                               value="<?php echo htmlspecialchars($student['batch_year'] ?? date('Y')); ?>" 
                               min="2000" max="2030" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Session</label>
                        <input type="text" name="session" class="form-control" 
                               value="<?php echo htmlspecialchars($student['session'] ?? ''); ?>" 
                               placeholder="e.g., Fall 2024">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Enrollment Date</label>
                        <input type="date" name="enrollment_date" class="form-control" 
                               value="<?php echo htmlspecialchars($student['enrollment_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($student['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="confirmed" <?php echo ($student['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="pending" <?php echo ($student['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="inactive" <?php echo ($student['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="graduated" <?php echo ($student['status'] ?? '') == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Fields with <span class="text-danger">*</span> are required
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Student
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>