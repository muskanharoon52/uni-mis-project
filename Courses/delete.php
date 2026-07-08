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
    header("Location: index.php?error=Invalid course ID");
    exit;
}

// Check if course exists
$query = "SELECT * FROM courses WHERE course_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: index.php?error=Course not found");
    exit;
}

// Check if course is being used in other tables (optional)
// Check in semester_courses
$check_query = "SELECT COUNT(*) as count FROM semester_courses WHERE course_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_data = $check_result->fetch_assoc();
$check_stmt->close();

$is_used = $check_data['count'] > 0;

// Handle delete confirmation
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // If course is used, prevent deletion or cascade delete
    if ($is_used) {
        // Option 1: Prevent deletion (safer)
        header("Location: index.php?error=Cannot delete this course because it is assigned to semesters");
        exit;
        
        // Option 2: If you want to allow deletion with cascade
        // Uncomment this if you want to delete related records too
        /*
        $conn->begin_transaction();
        try {
            // Delete from semester_courses first
            $delete_semester_courses = "DELETE FROM semester_courses WHERE course_id = ?";
            $stmt = $conn->prepare($delete_semester_courses);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the course
            $delete_course = "DELETE FROM courses WHERE course_id = ?";
            $stmt = $conn->prepare($delete_course);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            header("Location: index.php?success=Course deleted successfully");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: index.php?error=Error deleting course: " . $e->getMessage());
            exit;
        }
        */
    } else {
        // Course is not used, safe to delete
        $delete_query = "DELETE FROM courses WHERE course_id = ?";
        $stmt = $conn->prepare($delete_query);
        
        if ($stmt === false) {
            die("Error in delete query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Course deleted successfully");
            exit;
        } else {
            $errors[] = "Error deleting course: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle cancel
if (isset($_POST['cancel'])) {
    header("Location: index.php");
    exit;
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Delete Course';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .course-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin: 15px 0;
    }
    
    .course-details dt {
        font-weight: 600;
        color: #495057;
    }
    
    .course-details dd {
        margin-bottom: 10px;
        color: #212529;
    }
    
    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }
    
    .warning-box i {
        font-size: 24px;
        margin-right: 10px;
    }
</style>

<div class="container-fluid">
    <div class="delete-container">
        <div class="text-center mb-4">
            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 48px;"></i>
            <h4 class="mt-3">Delete Course</h4>
            <p class="text-muted">Are you sure you want to delete this course?</p>
        </div>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Course Details -->
        <div class="course-details">
            <dl>
                <dt>Course Code</dt>
                <dd><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></dd>
                
                <dt>Course Title</dt>
                <dd><?php echo htmlspecialchars($course['course_title']); ?></dd>
                
                <dt>Credit Hours</dt>
                <dd><?php echo $course['credit_hours']; ?></dd>
                
                <dt>Status</dt>
                <dd>
                    <span class="badge <?php echo $course['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $course['status']; ?>
                    </span>
                </dd>
            </dl>
        </div>

        <!-- Warning if course is used -->
        <?php if ($is_used): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Warning!</strong> This course is currently assigned to semesters.
                <br><br>
                <small>
                    <i class="fas fa-info-circle"></i>
                    You cannot delete this course because it is being used in 
                    <strong><?php echo $check_data['count']; ?></strong> semester(s).
                    Please remove it from all semesters first.
                </small>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                This course is not assigned to any semester. It is safe to delete.
            </div>
        <?php endif; ?>

        <!-- Confirmation Form -->
        <form method="POST" action="">
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="submit" name="cancel" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                
                <?php if (!$is_used): ?>
                    <button type="submit" name="confirm" value="yes" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Yes, Delete Course
                    </button>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
// Double confirmation for safety
document.querySelector('form')?.addEventListener('submit', function(e) {
    if (this.querySelector('[name="confirm"]')?.value === 'yes') {
        if (!confirm('⚠️ Are you absolutely sure you want to delete this course?\n\nThis action cannot be undone!')) {
            e.preventDefault();
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>