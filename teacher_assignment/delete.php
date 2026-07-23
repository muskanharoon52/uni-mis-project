<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
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

<style>
    .delete-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .delete-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .delete-icon {
        font-size: 64px;
        color: #e74c3c;
        margin-bottom: 20px;
    }
    
    .delete-title {
        color: #2c3e50;
        margin-bottom: 15px;
    }
    
    .delete-message {
        color: #7f8c8d;
        margin-bottom: 20px;
    }
    
    .assignment-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    
    .assignment-details .detail-row {
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .assignment-details .detail-row:last-child {
        border-bottom: none;
    }
    
    .assignment-details .label {
        font-weight: 600;
        color: #495057;
        display: inline-block;
        min-width: 120px;
    }
    
    .assignment-details .value {
        color: #2c3e50;
    }
    
    .btn-danger {
        background-color: #e74c3c;
        border-color: #e74c3c;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
        border-color: #c0392b;
    }
    
    .btn-secondary {
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .warning-text {
        color: #e74c3c;
        font-weight: 600;
        margin: 15px 0;
    }
    
    .btn-group-delete {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .delete-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .delete-container {
            padding: 20px;
            margin: 10px;
        }
        
        .assignment-details .label {
            min-width: 100px;
        }
        
        .btn-group-delete {
            flex-direction: column;
            align-items: center;
        }
        
        .btn-group-delete .btn {
            width: 100%;
            max-width: 300px;
        }
    }
</style>

<div class="delete-content">
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>