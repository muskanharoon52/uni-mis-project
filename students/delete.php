<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSSO();

// ============================================
// ALL PROCESSING FIRST
// ============================================

global $conn; // Use global connection from db.php

$id = isset($_GET['id']) ? $_GET['id'] : '';

// ID Check - student_id is VARCHAR
if (empty($id)) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

// Check if student exists - using VARCHAR for student_id
$query = "SELECT s.student_id, s.roll_no, s.father_name, u.full_name 
          FROM students s
          LEFT JOIN users u ON s.user_id = u.user_id
          WHERE s.student_id = ?";
$stmt = $conn->prepare($query);

// Check if prepare was successful
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("s", $id); // "s" for string, not "i" for integer
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: index.php?error=Student not found");
    exit;
}

// If confirm deletion
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // First, delete related records (if any foreign key constraints)
    // Delete from student_courses
    $delete_courses = "DELETE FROM student_courses WHERE student_id = ?";
    $stmt = $conn->prepare($delete_courses);
    if ($stmt) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Delete from attendance
    $delete_attendance = "DELETE FROM attendance WHERE student_id = ?";
    $stmt = $conn->prepare($delete_attendance);
    if ($stmt) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Delete from applications
    $delete_applications = "DELETE FROM applications WHERE student_id = ?";
    $stmt = $conn->prepare($delete_applications);
    if ($stmt) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Now delete the student
    $delete_query = "DELETE FROM students WHERE student_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if ($delete_stmt === false) {
        die("Error in delete query: " . $conn->error);
    }
    
    $delete_stmt->bind_param("s", $id); // "s" for string
    
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        header("Location: index.php?success=Student deleted successfully");
        exit;
    } else {
        $delete_stmt->close();
        header("Location: index.php?error=Error deleting student: " . $conn->error);
        exit;
    }
}

// ============================================
// NOW INCLUDE HEADER AND SIDEBAR
// ============================================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 50px auto;
    }
    
    .delete-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .delete-icon {
        font-size: 64px;
        color: #e74c3c;
        margin-bottom: 20px;
    }
    
    .student-name {
        font-size: 20px;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .student-roll {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .warning-text {
        background: #fef3cd;
        padding: 15px;
        border-radius: 8px;
        color: #856404;
        margin: 20px 0;
        text-align: left;
    }
    
    .btn-group-delete {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    
    .alert-danger-custom {
        background: #f8d7da;
        padding: 15px;
        border-radius: 8px;
        color: #721c24;
        margin: 20px 0;
        border: 1px solid #f5c6cb;
    }
    
    .student-info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        border: 1px solid #dee2e6;
    }
    
    .student-info-box .label {
        color: #6c757d;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .student-info-box .value {
        font-size: 18px;
        font-weight: 500;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="delete-container">
            <div class="delete-card">
                <div class="delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Delete Student</h4>
                <p class="text-muted">You are about to delete the following student:</p>
                
                <div class="student-info-box">
                    <div class="label">Student Name</div>
                    <div class="student-name">
                        <?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="student-info-box">
                    <div class="label">Roll Number</div>
                    <div class="student-roll" style="font-size:18px; font-weight:500;">
                        <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="student-info-box">
                    <div class="label">Father's Name</div>
                    <div style="font-size:16px; font-weight:400;">
                        <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="alert-danger-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Warning!</strong> This action cannot be undone!
                </div>
                
                <div class="warning-text">
                    <strong><i class="fas fa-info-circle"></i> Note:</strong>
                    <p class="mb-0 mt-1">Deleting this student will also remove all associated records including:</p>
                    <ul class="mb-0 mt-1" style="text-align: left;">
                        <li>Attendance records</li>
                        <li>Application records</li>
                        <li>Fee records (if any)</li>
                        <li>Result records (if any)</li>
                        <li>Course enrollment records</li>
                    </ul>
                </div>
                
                <div class="btn-group-delete">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="?id=<?php echo urlencode($id); ?>&confirm=yes" 
                       class="btn btn-danger"
                       onclick="return confirm('Are you absolutely sure you want to delete this student? This action cannot be undone!')">
                        <i class="fas fa-trash"></i> Yes, Delete Student
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>