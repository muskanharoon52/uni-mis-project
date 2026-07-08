<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid assignment ID");
    exit;
}

// Get assignment data
$query = "SELECT tc.*, t.teacher_name, c.course_code, c.course_title,
          s.semester_name, ses.session_name
          FROM teacher_courses tc
          LEFT JOIN teachers t ON tc.teacher_id = t.teacher_id
          LEFT JOIN courses c ON tc.course_id = c.course_id
          LEFT JOIN semesters s ON tc.semester_id = s.semester_id
          LEFT JOIN sessions ses ON tc.session_id = ses.session_id
          WHERE tc.id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: index.php?error=Assignment not found");
    exit;
}

// If confirm deletion
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $delete_query = "DELETE FROM teacher_courses WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if ($delete_stmt === false) {
        die("Error in delete query: " . $conn->error);
    }
    
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        header("Location: index.php?success=Assignment deleted successfully");
        exit;
    } else {
        $delete_stmt->close();
        header("Location: index.php?error=Error deleting assignment");
        exit;
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Delete Teacher Assignment';
include __DIR__ . '/../includes/navbar.php';
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
    
    .assignment-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        text-align: left;
    }
    
    .assignment-details .label {
        font-weight: 600;
        color: #7f8c8d;
        font-size: 13px;
    }
    
    .assignment-details .value {
        color: #2c3e50;
        font-size: 15px;
        margin-bottom: 5px;
    }
    
    .alert-danger-custom {
        background: #f8d7da;
        padding: 15px;
        border-radius: 8px;
        color: #721c24;
        margin: 20px 0;
    }
    
    .btn-group-delete {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
</style>

<div class="container-fluid">
    <div class="delete-container">
        <div class="delete-card">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4>Delete Assignment</h4>
            <p class="text-muted">You are about to delete the following assignment:</p>
            
            <div class="assignment-details">
                <div>
                    <span class="label">Teacher:</span>
                    <div class="value"><?php echo htmlspecialchars($assignment['teacher_name']); ?></div>
                </div>
                <div>
                    <span class="label">Course:</span>
                    <div class="value"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_title']); ?></div>
                </div>
                <div>
                    <span class="label">Semester:</span>
                    <div class="value"><?php echo htmlspecialchars($assignment['semester_name'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <span class="label">Session:</span>
                    <div class="value"><?php echo htmlspecialchars($assignment['session_name'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <span class="label">Section:</span>
                    <div class="value"><?php echo htmlspecialchars($assignment['section'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <div class="alert-danger-custom">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Warning!</strong> This action cannot be undone!
            </div>
            
            <div class="btn-group-delete">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="?id=<?php echo $id; ?>&confirm=yes" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you absolutely sure you want to delete this assignment?')">
                    <i class="fas fa-trash"></i> Yes, Delete Assignment
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>