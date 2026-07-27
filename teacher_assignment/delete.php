<?php
// teacher_assignment/delete_teacher.php - Delete Teacher

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid teacher ID");
    exit;
}

// Get teacher data
$query = "SELECT t.*, d.department_name 
          FROM teachers t
          LEFT JOIN departments d ON t.department_id = d.department_id
          WHERE t.teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header("Location: index.php?error=Teacher not found");
    exit;
}

// Handle deletion confirmation
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete from question_papers
        $check_table = "SHOW TABLES LIKE 'question_papers'";
        $table_exists = mysqli_query($conn, $check_table);
        if (mysqli_num_rows($table_exists) > 0) {
            $delete_qp = "DELETE FROM question_papers WHERE teacher_id = ?";
            $stmt = $conn->prepare($delete_qp);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete from sbe_question_bank
        $check_table = "SHOW TABLES LIKE 'sbe_question_bank'";
        $table_exists = mysqli_query($conn, $check_table);
        if (mysqli_num_rows($table_exists) > 0) {
            $delete_qb = "DELETE FROM sbe_question_bank WHERE teacher_id = ?";
            $stmt = $conn->prepare($delete_qb);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete from teacher_courses
        $delete_assignments = "DELETE FROM teacher_courses WHERE teacher_id = ?";
        $stmt = $conn->prepare($delete_assignments);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Delete from exam_schedules
        $check_table = "SHOW TABLES LIKE 'exam_schedules'";
        if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
            $check_column = "SHOW COLUMNS FROM exam_schedules LIKE 'teacher_id'";
            if (mysqli_num_rows(mysqli_query($conn, $check_column)) > 0) {
                $delete_exam = "DELETE FROM exam_schedules WHERE teacher_id = ?";
                $stmt = $conn->prepare($delete_exam);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Now delete the teacher
        $delete_query = "DELETE FROM teachers WHERE teacher_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        mysqli_commit($conn);
        
        header("Location: index.php?success=Teacher deleted successfully");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit;
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
$page_title = 'Delete Teacher';
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
    
    .teacher-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    
    .teacher-details .detail-row {
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .teacher-details .detail-row:last-child {
        border-bottom: none;
    }
    
    .teacher-details .label {
        font-weight: 600;
        color: #495057;
        display: inline-block;
        min-width: 120px;
    }
    
    .teacher-details .value {
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
    }
</style>

<div class="delete-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-trash-alt text-danger"></i> Delete Teacher</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="delete-container">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h4 class="delete-title">Are you sure you want to delete this teacher?</h4>
            <p class="delete-message">This action cannot be undone. All related records will also be removed.</p>
            
            <div class="teacher-details">
                <div class="detail-row">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($teacher['teacher_name'] ?? $teacher['full_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Department:</span>
                    <span class="value"><?php echo htmlspecialchars($teacher['department_name'] ?? 'N/A'); ?></span>
                </div>
                <?php if (isset($teacher['email']) && !empty($teacher['email'])): ?>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($teacher['email']); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value"><?php echo htmlspecialchars($teacher['status'] ?? 'Active'); ?></span>
                </div>
            </div>
            
            <p class="warning-text">
                <i class="fas fa-exclamation-circle"></i> 
                This will permanently remove this teacher and all associated records.
            </p>
            
            <form method="POST" action="" class="mt-4">
                <div class="btn-group-delete">
                    <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Delete Teacher
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