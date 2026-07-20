<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid application ID");
    exit;
}

// Fetch application
$sql = "SELECT * FROM applications WHERE application_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application) {
    header("Location: index.php?error=Application not found");
    exit;
}

// Check if user can edit (only if pending and is the student who submitted)
$role_id = $_SESSION['role_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Get student ID for logged in user
$student_id = '';
if ($user_id > 0) {
    $student_query = "SELECT student_id FROM students WHERE user_id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $user_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_row = $student_result->fetch_assoc();
    $student_id = $student_row['student_id'] ?? '';
    $student_stmt->close();
}

// Check permissions
$can_edit = false;
if ($application['status'] == 'Pending') {
    if ($role_id == 4 && $application['student_id'] == $student_id) {
        $can_edit = true; // Student can edit own pending application
    } elseif (in_array($role_id, [1, 2])) {
        $can_edit = true; // Admin/SSO can edit any pending application
    }
}

if (!$can_edit) {
    header("Location: index.php?error=You cannot edit this application");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_type = $_POST['application_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($application_type)) $error = "Please select application type";
    elseif (empty($subject)) $error = "Please enter a subject";
    elseif (empty($description)) $error = "Please enter a description";

    if (empty($error)) {
        $update_query = "UPDATE applications SET 
                         application_type = ?, 
                         subject = ?, 
                         description = ? 
                         WHERE application_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssi", $application_type, $subject, $description, $id);
        
        if ($update_stmt->execute()) {
            header("Location: index.php?success=Application updated successfully!");
            exit;
        } else {
            $error = "Error updating application: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Edit Application';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .edit-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 700px;
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
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    .btn-submit {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .edit-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="edit-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit"></i> Edit Application</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Application Type <span class="required-star">*</span></label>
                    <select name="application_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Leave" <?= $application['application_type'] == 'Leave' ? 'selected' : '' ?>>Leave Application</option>
                        <option value="Bonafide Certificate" <?= $application['application_type'] == 'Bonafide Certificate' ? 'selected' : '' ?>>Bonafide Certificate</option>
                        <option value="Transcript" <?= $application['application_type'] == 'Transcript' ? 'selected' : '' ?>>Transcript Request</option>
                        <option value="ID Card" <?= $application['application_type'] == 'ID Card' ? 'selected' : '' ?>>ID Card Request</option>
                        <option value="Semester Freeze" <?= $application['application_type'] == 'Semester Freeze' ? 'selected' : '' ?>>Semester Freeze</option>
                        <option value="Course Withdrawal" <?= $application['application_type'] == 'Course Withdrawal' ? 'selected' : '' ?>>Course Withdrawal</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subject <span class="required-star">*</span></label>
                    <input type="text" name="subject" class="form-control" 
                           value="<?= htmlspecialchars($application['subject']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <span class="required-star">*</span></label>
                    <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($application['description']) ?></textarea>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    You can only edit pending applications. Once reviewed, applications cannot be modified.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> Update Application
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>