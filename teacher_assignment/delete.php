<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

// Get ID from URL - supports both 'id' and 'assignment_id'
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && isset($_GET['assignment_id'])) {
    $id = (int)$_GET['assignment_id'];
}

if ($id <= 0) {
    header("Location: index.php?error=Invalid assignment ID");
    exit;
}

// Get assignment data to display confirmation
$query = "SELECT tc.*, 
          t.teacher_name, 
          t.teacher_code,
          c.course_code, 
          c.course_name,
          s.semester_name,
          sess.session_name
          FROM teacher_courses tc
          LEFT JOIN teachers t ON tc.teacher_id = t.teacher_id
          LEFT JOIN courses c ON tc.course_id = c.course_id
          LEFT JOIN semesters s ON tc.semester_id = s.semester_id
          LEFT JOIN sessions sess ON tc.session_id = sess.session_id
          WHERE tc.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: index.php?error=Assignment not found");
    exit;
}

// Handle deletion confirmation
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $delete_query = "DELETE FROM teacher_courses WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        header("Location: index.php?success=Assignment deleted successfully");
        exit;
    } else {
        $error = "Error deleting assignment: " . $delete_stmt->error;
        $delete_stmt->close();
    }
}

// Handle cancellation
if (isset($_POST['cancel'])) {
    header("Location: index.php");
    exit;
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Teacher Assignment';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-trash-alt text-danger"></i> Delete Teacher Assignment</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="delete-container">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h4 class="delete-title">Are you sure you want to delete this assignment?</h4>
            <p class="delete-message">This action cannot be undone. Please confirm before proceeding.</p>
            
            <div class="assignment-details">
                <div class="detail-row">
                    <span class="label">Teacher:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($assignment['teacher_name'] ?? 'N/A'); ?>
                        (<?php echo htmlspecialchars($assignment['teacher_code'] ?? 'N/A'); ?>)
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Course:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($assignment['course_code'] ?? 'N/A'); ?>
                        - <?php echo htmlspecialchars($assignment['course_name'] ?? 'N/A'); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Semester:</span>
                    <span class="value"><?php echo htmlspecialchars($assignment['semester_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Session:</span>
                    <span class="value"><?php echo htmlspecialchars($assignment['session_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Section:</span>
                    <span class="value"><?php echo htmlspecialchars($assignment['section'] ?? 'N/A'); ?></span>
                </div>
            </div>
            
            <p class="warning-text">
                <i class="fas fa-exclamation-circle"></i> 
                This will permanently remove this teacher-course assignment from the system.
            </p>
            
            <form method="POST" action="" class="mt-4">
                <div class="btn-group-delete">
                    <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Delete Assignment
                    </button>
                    <button type="submit" name="cancel" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>