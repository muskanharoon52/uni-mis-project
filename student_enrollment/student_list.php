<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

if ($section_id <= 0) {
    header("Location: index.php?error=Invalid section ID");
    exit;
}

// Get section details
$section_query = "SELECT * FROM sections WHERE section_id = ?";
$section_stmt = $conn->prepare($section_query);
if ($section_stmt === false) {
    die("Error preparing section query: " . $conn->error);
}

$section_stmt->bind_param("i", $section_id);
$section_stmt->execute();
$section_result = $section_stmt->get_result();
$section = $section_result->fetch_assoc();
$section_stmt->close();

if (!$section) {
    header("Location: index.php?error=Section not found");
    exit;
}

// Check if student_enrollments table exists
$table_check = $conn->query("SHOW TABLES LIKE 'student_enrollments'");
if ($table_check->num_rows == 0) {
    $students = [];
    $table_exists = false;
} else {
    $table_exists = true;
    // Fetch students in this section with COLLATE fix
    $students_query = "SELECT 
                        se.enrollment_id,
                        se.enrollment_date,
                        se.status as enrollment_status,
                        s.student_id,
                        s.roll_no,
                        u.full_name,
                        u.email,
                        u.phone,
                        p.program_name
                       FROM student_enrollments se
                       LEFT JOIN students s ON se.student_id COLLATE utf8mb4_general_ci = s.student_id
                       LEFT JOIN users u ON s.user_id = u.user_id
                       LEFT JOIN programs p ON s.program_id = p.program_id
                       WHERE se.section_id = ? AND se.status = 'Enrolled'
                       ORDER BY u.full_name";
    
    $students_stmt = $conn->prepare($students_query);
    if ($students_stmt === false) {
        die("Error preparing students query: " . $conn->error);
    }
    $students_stmt->bind_param("i", $section_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];
    $students_stmt->close();
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Section Students';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .student-list-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .section-header {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .stats-box {
        background: #f8f9fa;
        padding: 10px 20px;
        border-radius: 8px;
        display: inline-block;
        margin-right: 15px;
    }
    
    @media (max-width: 768px) {
        .student-list-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="student-list-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-graduate"></i> Students in Section</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sections
            </a>
        </div>

        <!-- Section Info -->
        <div class="section-header">
            <div class="row">
                <div class="col-md-4">
                    <h5>Section: <?= htmlspecialchars($section['section_name']) ?></h5>
                    <span class="badge bg-primary"><?= htmlspecialchars($section['status'] ?? 'Active') ?></span>
                </div>
                <div class="col-md-4">
                    <div class="stats-box">
                        <strong>Semester:</strong> <?= htmlspecialchars($section['semester_id'] ?? 'N/A') ?>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="stats-box">
                        <strong>Enrolled:</strong> <?= isset($students) ? count($students) : 0 ?> 
                        / <?= $section['capacity'] ?? 30 ?>
                    </div>
                    <a href="enroll_student.php?section=<?= $section_id ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus"></i> Add Student
                    </a>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h5>Students (<?= isset($students) ? count($students) : 0 ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!$table_exists): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Student enrollments table is not set up yet. Please run the SQL to create it.
                    </div>
                <?php elseif (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Enrollment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($student['roll_no'] ?? 'N/A') ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($student['full_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                                        <td><?= isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A' ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Remove this student from section?')">
                                                <i class="fas fa-user-minus"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p>No students enrolled in this section yet.</p>
                        <a href="enroll_student.php?section=<?= $section_id ?>" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Enroll Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>