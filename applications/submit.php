<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Get student ID for logged in user
$student_id = '';
$user_id = $_SESSION['user_id'] ?? 0;
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

// Check if user is student or admin
$role_id = $_SESSION['role_id'] ?? 0;
$is_student = ($role_id == 4);
$is_admin = in_array($role_id, [1, 2]);

// If not student and not admin, redirect
if (!$is_student && !$is_admin) {
    header("Location: index.php?error=Access denied");
    exit;
}

// For admin, allow selecting student
if ($is_admin && empty($student_id)) {
    $students_query = "SELECT s.student_id, u.full_name 
                       FROM students s 
                       LEFT JOIN users u ON s.user_id = u.user_id 
                       WHERE s.status = 'active' 
                       ORDER BY u.full_name";
    $students_result = $conn->query($students_query);
    $students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? $student_id;
    $application_type = $_POST['application_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $attachment = $_FILES['attachment'] ?? null;

    // Validation
    if (empty($student_id)) $error = "Please select a student";
    elseif (empty($application_type)) $error = "Please select application type";
    elseif (empty($subject)) $error = "Please enter a subject";
    elseif (empty($description)) $error = "Please enter a description";

    // Handle file upload
    $attachment_path = '';
    if ($attachment && $attachment['error'] == 0) {
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $error = "Invalid file type. Allowed: " . implode(', ', $allowed_types);
        } elseif ($attachment['size'] > 5242880) {
            $error = "File size too large. Maximum 5MB allowed.";
        } else {
            $upload_dir = __DIR__ . '/../uploads/applications/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $attachment_path = 'uploads/applications/' . time() . '_' . basename($attachment['name']);
            if (!move_uploaded_file($attachment['tmp_name'], __DIR__ . '/../' . $attachment_path)) {
                $error = "Failed to upload file.";
            }
        }
    }

    if (empty($error)) {
        $insert_query = "INSERT INTO applications 
                        (student_id, application_type, subject, description, attachment, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sssss", $student_id, $application_type, $subject, $description, $attachment_path);
        
        if ($insert_stmt->execute()) {
            header("Location: index.php?success=Application submitted successfully!");
            exit;
        } else {
            $error = "Error submitting application: " . $conn->error;
        }
        $insert_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Submit Application';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .submit-content {
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
    
    .file-upload-wrapper {
        border: 2px dashed #ced4da;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .file-upload-wrapper:hover {
        border-color: #3498db;
        background: #f8f9ff;
    }
    
    .file-upload-wrapper i {
        font-size: 2rem;
        color: #6c757d;
    }
    
    .btn-submit {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .submit-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="submit-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-plus-circle"></i> Submit Application</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Student Selection (Only for Admin) -->
                <?php if ($is_admin && isset($students)): ?>
                    <div class="mb-3">
                        <label class="form-label">Student <span class="required-star">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>">
                                    <?= htmlspecialchars($student['full_name']) ?> (<?= $student['student_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="student_id" value="<?= $student_id ?>">
                <?php endif; ?>

                <!-- Application Type -->
                <div class="mb-3">
                    <label class="form-label">Application Type <span class="required-star">*</span></label>
                    <select name="application_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Leave">Leave Application</option>
                        <option value="Bonafide Certificate">Bonafide Certificate</option>
                        <option value="Transcript">Transcript Request</option>
                        <option value="ID Card">ID Card Request</option>
                        <option value="Semester Freeze">Semester Freeze</option>
                        <option value="Course Withdrawal">Course Withdrawal</option>
                    </select>
                </div>

                <!-- Subject -->
                <div class="mb-3">
                    <label class="form-label">Subject <span class="required-star">*</span></label>
                    <input type="text" name="subject" class="form-control" placeholder="Brief subject of application" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label">Description <span class="required-star">*</span></label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Provide detailed description of your application..." required></textarea>
                </div>

                <!-- Attachment -->
                <div class="mb-3">
                    <label class="form-label">Attachment (Optional)</label>
                    <div class="file-upload-wrapper" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="mb-0 mt-2">Click to upload or drag and drop</p>
                        <small class="text-muted">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</small>
                        <input type="file" name="attachment" id="fileInput" class="d-none" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    </div>
                    <div id="fileInfo" class="mt-2 text-success" style="display: none;">
                        <i class="fas fa-check-circle"></i> <span id="fileName"></span>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('fileInfo').style.display = 'block';
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>