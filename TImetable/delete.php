<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid ID");
    exit;
}

// Fetch class details
$sql = "SELECT t.*, c.course_name, c.course_code, tch.teacher_name, s.semester_name 
        FROM timetable t
        LEFT JOIN courses c ON t.course_id = c.course_id
        LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id
        LEFT JOIN semesters s ON t.semester_id = s.semester_id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();

if (!$class) {
    header("Location: index.php?error=Record not found");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $delete_sql = "DELETE FROM timetable WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        header("Location: index.php?success=Class deleted successfully!");
        exit;
    } else {
        $error = "Error deleting class: " . $conn->error;
    }
    $delete_stmt->close();
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Class';
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
        max-width: 500px;
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
    
    .class-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    
    .class-info .label {
        font-weight: 600;
        color: #495057;
    }
    
    @media (max-width: 768px) {
        .delete-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .delete-container {
            padding: 20px;
        }
    }
</style>

<div class="delete-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-trash-alt text-danger"></i> Delete Class</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="delete-container">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h5>Are you sure you want to delete this class?</h5>
            <p class="text-muted">This action cannot be undone.</p>
            
            <div class="class-info">
                <div><span class="label">Course:</span> <?= htmlspecialchars($class['course_name']) ?></div>
                <div><span class="label">Code:</span> <?= htmlspecialchars($class['course_code']) ?></div>
                <div><span class="label">Teacher:</span> <?= htmlspecialchars($class['teacher_name']) ?></div>
                <div><span class="label">Day:</span> <?= htmlspecialchars($class['day_of_week']) ?></div>
                <div><span class="label">Time:</span> <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?></div>
                <div><span class="label">Room:</span> <?= htmlspecialchars($class['room_no']) ?></div>
                <div><span class="label">Section:</span> <?= htmlspecialchars($class['section']) ?></div>
            </div>
            
            <form method="POST" action="">
                <div class="d-flex gap-3 justify-content-center">
                    <button type="submit" name="confirm" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Delete
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>