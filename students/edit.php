<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// ID Check
if ($id <= 0) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

// Get student data
$query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
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
$program_query = "SELECT program_id as id, program_name as name FROM programs WHERE status = 'Active' ORDER BY program_name";
$program_result = $conn->query($program_query);
$programs = $program_result ? $program_result->fetch_all(MYSQLI_ASSOC) : [];

// Get sessions for dropdown
$session_query = "SELECT session_id as id, session_name as name FROM sessions WHERE status = 'Active' ORDER BY session_name";
$session_result = $conn->query($session_query);
$sessions = $session_result ? $session_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters for dropdown
$semester_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $cnic_or_bform = trim($_POST['cnic_or_bform'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contact_no = trim($_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $program_id = (int)($_POST['program_id'] ?? 0);
    $current_session_id = (int)($_POST['current_session_id'] ?? 0);
    $current_semester_id = (int)($_POST['current_semester_id'] ?? 0);
    $batch_year = (int)($_POST['batch_year'] ?? 0);
    $admission_date = $_POST['admission_date'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($father_name)) $errors[] = "Father's name is required";
    if (empty($program_id) || $program_id == 0) $errors[] = "Program is required";
    if (empty($current_session_id) || $current_session_id == 0) $errors[] = "Session is required";
    if (empty($current_semester_id) || $current_semester_id == 0) $errors[] = "Semester is required";
    if (empty($batch_year)) $errors[] = "Batch year is required";

    // Update student
    if (empty($errors)) {
        $update_query = "UPDATE students SET 
            full_name = ?, 
            father_name = ?, 
            cnic_or_bform = ?, 
            dob = ?, 
            gender = ?, 
            contact_no = ?, 
            email = ?, 
            address = ?, 
            program_id = ?, 
            current_session_id = ?, 
            current_semester_id = ?, 
            batch_year = ?, 
            admission_date = ?, 
            status = ?
            WHERE student_id = ?";

        $stmt = $conn->prepare($update_query);
        $stmt->bind_param(
            "ssssssssiiisssi",
            $full_name,
            $father_name,
            $cnic_or_bform,
            $dob,
            $gender,
            $contact_no,
            $email,
            $address,
            $program_id,
            $current_session_id,
            $current_semester_id,
            $batch_year,
            $admission_date,
            $status,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            // ============================================
            // HEADER CALL - YEH PEHLE HI PROCESSING MEIN HAI
            // ============================================
            header("Location: view.php?id=$id&success=1&action=updated");
            exit;
        } else {
            $errors[] = "Error updating student: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN (SARI PROCESSING KE BAAD)
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Edit Student';
include __DIR__ . '/../includes/navbar.php';
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
        <h4><i class="fas fa-edit"></i> Edit Student</h4>
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
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Father's Name</label>
                    <input type="text" name="father_name" class="form-control" 
                           value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>CNIC / B-Form</label>
                    <input type="text" name="cnic_or_bform" class="form-control" 
                           value="<?php echo htmlspecialchars($student['cnic_or_bform'] ?? ''); ?>">
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
                <div class="col-md-6 mb-3">
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" class="form-control" 
                           value="<?php echo htmlspecialchars($student['contact_no'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                </div>
                <div class="col-12 mb-3">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
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
                    <label class="required-field">Current Session</label>
                    <select name="current_session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" 
                                <?php echo ($student['current_session_id'] ?? '') == $session['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Current Semester</label>
                    <select name="current_semester_id" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester['id']; ?>" 
                                <?php echo ($student['current_semester_id'] ?? '') == $semester['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Batch Year</label>
                    <input type="number" name="batch_year" class="form-control" 
                           value="<?php echo htmlspecialchars($student['batch_year'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" 
                           value="<?php echo htmlspecialchars($student['admission_date'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?php echo ($student['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Freeze" <?php echo ($student['status'] ?? '') == 'Freeze' ? 'selected' : ''; ?>>Freeze</option>
                        <option value="Graduated" <?php echo ($student['status'] ?? '') == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                        <option value="Dropped" <?php echo ($student['status'] ?? '') == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                        <option value="Suspended" <?php echo ($student['status'] ?? '') == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Student
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>