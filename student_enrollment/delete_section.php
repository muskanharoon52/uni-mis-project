<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid section ID");
    exit;
}

// Fetch section
$query = "SELECT * FROM sections WHERE section_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();
$stmt->close();

if (!$section) {
    header("Location: index.php?error=Section not found");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $delete_sql = "DELETE FROM sections WHERE section_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        header("Location: index.php?success=Section deleted successfully!");
        exit;
    } else {
        $error = "Error deleting section: " . $conn->error;
    }
    $delete_stmt->close();
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Section';
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
            <h4><i class="fas fa-trash-alt text-danger"></i> Delete Section</h4>
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
            
            <h5>Are you sure you want to delete this section?</h5>
            <p class="text-muted">This action cannot be undone.</p>
            
            <div class="info-box text-start bg-light p-3 rounded">
                <p><strong>Section:</strong> <?= htmlspecialchars($section['section_name']) ?></p>
                <p><strong>Semester:</strong> <?= htmlspecialchars($section['semester_id']) ?></p>
                <p><strong>Status:</strong> <?= $section['status'] ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="d-flex gap-3 justify-content-center mt-3">
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