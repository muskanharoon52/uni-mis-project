<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid application ID");
    exit;
}

// Fetch application
$sql = "SELECT a.*, u.full_name as student_name 
        FROM applications a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        WHERE a.application_id = ?";
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

// Check if user can delete
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

$can_delete = false;
if ($application['status'] == 'Pending') {
    if ($role_id == 4 && $application['student_id'] == $student_id) {
        $can_delete = true;
    } elseif (in_array($role_id, [1, 2])) {
        $can_delete = true;
    }
}

if (!$can_delete) {
    header("Location: index.php?error=You cannot delete this application");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Delete attachment file if exists
    if ($application['attachment']) {
        $file_path = __DIR__ . '/../' . $application['attachment'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $delete_sql = "DELETE FROM applications WHERE application_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        header("Location: index.php?success=Application deleted successfully!");
        exit;
    } else {
        $error = "Error deleting application: " . $conn->error;
    }
    $delete_stmt->close();
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Application';
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
    
    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    
    .status-badge {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.Pending {
        background: #fff3cd;
        color: #856404;
    }
    
    @media (max-width: 768px) {
        .delete-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="delete-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-trash-alt text-danger"></i> Delete Application</h4>
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
            
            <h5>Are you sure you want to delete this application?</h5>
            <p class="text-muted">This action cannot be undone.</p>
            
            <div class="info-box">
                <div><strong>Student:</strong> <?= htmlspecialchars($application['student_name'] ?? 'N/A') ?></div>
                <div><strong>Type:</strong> <?= htmlspecialchars($application['application_type']) ?></div>
                <div><strong>Subject:</strong> <?= htmlspecialchars($application['subject']) ?></div>
                <div><strong>Status:</strong> <span class="status-badge <?= $application['status'] ?>"><?= $application['status'] ?></span></div>
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