<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$message = '';
$message_type = '';

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_name = isset($_POST['teacher_name']) ? trim($_POST['teacher_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validation
    if (empty($teacher_name) || empty($email) || $department_id == 0) {
        $message = 'Please fill all required fields (Name, Email, Department)';
        $message_type = 'danger';
    } else {
        // Check if teacher already exists
        $check_sql = "SELECT teacher_id FROM teachers WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = 'A teacher with this email already exists!';
            $message_type = 'warning';
        } else {
            // Insert new teacher
            $insert_sql = "INSERT INTO teachers (teacher_name, email, designation, department_id, phone, status) 
                           VALUES (?, ?, ?, ?, ?, 'Active')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssis", $teacher_name, $email, $designation, $department_id, $phone);
            
            if ($insert_stmt->execute()) {
                $teacher_id = $insert_stmt->insert_id;
                $message = 'Teacher added successfully!';
                $message_type = 'success';
                
                // Redirect to assignment page with success
                header("Location: assign.php?teacher_id=" . $teacher_id . "&success=Teacher added successfully! You can now assign them to a course.");
                exit();
            } else {
                $message = 'Error adding teacher: ' . $insert_stmt->error;
                $message_type = 'danger';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Get departments for dropdown
$dept_query = "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// INCLUDE HEADER AND SIDEBAR
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add New Teacher';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-section h4 {
        margin-bottom: 25px;
        color: #2c3e50;
        border-bottom: 2px solid #e8f0fe;
        padding-bottom: 15px;
    }
    
    .required-field::after {
        content: ' *';
        color: #dc3545;
        font-weight: bold;
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

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : ($message_type == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Teacher Form -->
    <div class="form-section">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required-field">Full Name</label>
                    <input type="text" name="teacher_name" class="form-control" 
                           placeholder="Enter teacher's full name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label required-field">Email</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="teacher@university.edu" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label required-field">Department</label>
                    <select name="department_id" class="form-select" required>
                        <option value="0">-- Select Department --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d['department_id']; ?>">
                                <?php echo htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" 
                           placeholder="e.g., Assistant Professor, Lecturer">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" 
                           placeholder="0300-1234567">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> Add Teacher
                </button>
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>