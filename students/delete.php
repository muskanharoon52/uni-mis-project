<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ID Check
if ($id <= 0) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

// Check if student exists - WITH ERROR HANDLING
$query = "SELECT student_id, full_name, roll_no FROM students WHERE student_id = ?";
$stmt = $conn->prepare($query);

// Check if prepare was successful
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
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
    $delete_query = "DELETE FROM students WHERE student_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    // Check if prepare was successful
    if ($delete_stmt === false) {
        die("Error in delete query: " . $conn->error);
    }
    
    $delete_stmt->bind_param("i", $id);
    
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
// AB HEADER.PHP INCLUDE KAREIN (SARI PROCESSING KE BAAD)
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Delete Student';
include __DIR__ . '/../includes/navbar.php';
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
    }
</style>

<div class="container-fluid">
    <div class="delete-container">
        <div class="delete-card">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4>Delete Student</h4>
            <p class="text-muted">You are about to delete the following student:</p>
            
            <div class="student-name">
                <?php echo htmlspecialchars($student['full_name']); ?>
            </div>
            <div class="student-roll">
                Roll No: <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?>
            </div>
            
            <div class="alert-danger-custom">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Warning!</strong> This action cannot be undone!
            </div>
            
            <div class="warning-text">
                <strong><i class="fas fa-info-circle"></i> Note:</strong>
                <p class="mb-0 mt-1">Deleting this student will also remove all associated records including:</p>
                <ul class="mb-0 mt-1">
                    <li>Attendance records</li>
                    <li>Application records</li>
                    <li>Fee records (if any)</li>
                    <li>Result records (if any)</li>
                </ul>
            </div>
            
            <div class="btn-group-delete">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="?id=<?php echo $id; ?>&confirm=yes" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you absolutely sure you want to delete this student? This action cannot be undone!')">
                    <i class="fas fa-trash"></i> Yes, Delete Student
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>