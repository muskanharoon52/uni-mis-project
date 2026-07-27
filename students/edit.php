<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
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

// Use the correct table name
$table_name = 'admission_students';

// Check what columns exist in admission_students table
$check_columns = "SHOW COLUMNS FROM $table_name";
$cols_result = mysqli_query($conn, $check_columns);
$student_columns = [];
if ($cols_result) {
    while ($col = mysqli_fetch_assoc($cols_result)) {
        $student_columns[] = $col['Field'];
    }
}

// Get student data
$query = "SELECT s.*, p.program_name, p.program_code 
          FROM $table_name s
          LEFT JOIN programs p ON s.program_id = p.program_id
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
    $student_name = trim($_POST['student_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $program_id = (int)($_POST['program_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $cnic = trim($_POST['cnic'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($father_name)) $errors[] = "Father's name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if ($program_id <= 0) $errors[] = "Program is required";

    // Update student
    if (empty($errors)) {
        try {
            // Build update query based on existing columns
            $update_fields = [];
            $update_params = [];
            $types = "";
            
            if (in_array('full_name', $student_columns)) {
                $update_fields[] = "full_name = ?";
                $update_params[] = $full_name;
                $types .= "s";
            }
            
            if (in_array('student_name', $student_columns)) {
                $update_fields[] = "student_name = ?";
                $update_params[] = !empty($student_name) ? $student_name : $full_name;
                $types .= "s";
            }
            
            if (in_array('email', $student_columns)) {
                $update_fields[] = "email = ?";
                $update_params[] = $email;
                $types .= "s";
            }
            
            if (in_array('contact_no', $student_columns)) {
                $update_fields[] = "contact_no = ?";
                $update_params[] = $phone;
                $types .= "s";
            }
            
            if (in_array('father_name', $student_columns)) {
                $update_fields[] = "father_name = ?";
                $update_params[] = $father_name;
                $types .= "s";
            }
            
            if (in_array('program_id', $student_columns)) {
                $update_fields[] = "program_id = ?";
                $update_params[] = $program_id;
                $types .= "i";
            }
            
            if (in_array('status', $student_columns)) {
                $update_fields[] = "status = ?";
                $update_params[] = $status;
                $types .= "s";
            }
            
            if (in_array('cnic_or_bform', $student_columns)) {
                $update_fields[] = "cnic_or_bform = ?";
                $update_params[] = $cnic;
                $types .= "s";
            }
            
            if (in_array('dob', $student_columns)) {
                $update_fields[] = "dob = ?";
                $update_params[] = $dob;
                $types .= "s";
            }
            
            if (in_array('gender', $student_columns)) {
                $update_fields[] = "gender = ?";
                $update_params[] = $gender;
                $types .= "s";
            }
            
            if (in_array('address', $student_columns)) {
                $update_fields[] = "address = ?";
                $update_params[] = $address;
                $types .= "s";
            }
            
            if (in_array('updated_at', $student_columns)) {
                $update_fields[] = "updated_at = NOW()";
            }
            
            // Add student_id to params
            $update_params[] = $id;
            $types .= "s";
            
            $update_student = "UPDATE $table_name SET " . implode(", ", $update_fields) . " WHERE student_id = ?";
            
            $stmt = $conn->prepare($update_student);
            if ($stmt === false) {
                throw new Exception("Error preparing update: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$update_params);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating student: " . $stmt->error);
            }
            $stmt->close();
            
            // Redirect with success
            header("Location: view.php?id=" . urlencode($id) . "&success=updated");
            exit;
            
        } catch (Exception $e) {
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
    .main-content { margin-left: 250px; padding: 20px; }
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
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
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
                               value="<?php echo htmlspecialchars($student['full_name'] ?? $student['student_name'] ?? ''); ?>" required>
                        <div class="field-note">Student's full name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Student Name (Alternate)</label>
                        <input type="text" name="student_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['student_name'] ?? ''); ?>">
                        <div class="field-note">Alternate name if different from full name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                        <div class="field-note">Valid email address</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['contact_no'] ?? ''); ?>">
                        <div class="field-note">Contact number</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required>
                        <div class="field-note">Student's father/guardian name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>CNIC/B-Form</label>
                        <input type="text" name="cnic" class="form-control" 
                               value="<?php echo htmlspecialchars($student['cnic_or_bform'] ?? ''); ?>"
                               placeholder="XXXXX-XXXXXXX-X">
                        <div class="field-note">CNIC or B-Form number</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control" 
                               value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($student['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($student['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
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
                        <label>Program Code</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?>" disabled>
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