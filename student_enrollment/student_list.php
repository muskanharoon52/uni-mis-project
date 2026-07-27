<?php
// student_enrollment/student_list.php - View Students in Section

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

if ($section_id <= 0) {
    header("Location: index.php?error=Invalid section ID");
    exit;
}

// Get section details
$section_query = "SELECT s.*, p.program_name, sm.semester_name 
                  FROM sections s
                  LEFT JOIN programs p ON s.program_id = p.program_id
                  LEFT JOIN semesters sm ON s.semester_id = sm.semester_id
                  WHERE s.section_id = " . intval($section_id);
$section_result = mysqli_query($conn, $section_query);
$section = mysqli_fetch_assoc($section_result);

if (!$section) {
    header("Location: index.php?error=Section not found");
    exit;
}

// ============================================
// FIX: Get students WITHOUT JOIN to avoid collation issues
// ============================================

// Step 1: Get student IDs from student_enrollments
$enrollment_query = "SELECT student_id, enrollment_date, status as enrollment_status 
                     FROM student_enrollments 
                     WHERE section_id = " . intval($section_id);
$enrollment_result = mysqli_query($conn, $enrollment_query);
$enrolled_students = [];
if ($enrollment_result) {
    while ($row = mysqli_fetch_assoc($enrollment_result)) {
        $enrolled_students[$row['student_id']] = $row;
    }
}

// Step 2: Get student details from admission_students
$students = [];
if (!empty($enrolled_students)) {
    $student_ids = array_keys($enrolled_students);
    
    // Build IN clause safely
    $escaped_ids = array_map(function($id) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $id) . "'";
    }, $student_ids);
    $student_ids_str = implode(', ', $escaped_ids);
    
    $students_query = "SELECT 
                        s.student_id,
                        s.full_name,
                        s.student_name,
                        s.father_name,
                        s.email,
                        s.contact_no,
                        s.status as student_status,
                        s.program_id
                       FROM admission_students s
                       WHERE s.student_id IN ($student_ids_str)
                       ORDER BY s.full_name";
    
    $students_result = mysqli_query($conn, $students_query);
    if ($students_result) {
        while ($row = mysqli_fetch_assoc($students_result)) {
            // Merge with enrollment data
            if (isset($enrolled_students[$row['student_id']])) {
                $row['enrollment_date'] = $enrolled_students[$row['student_id']]['enrollment_date'];
                $row['enrollment_status'] = $enrolled_students[$row['student_id']]['enrollment_status'];
            }
            $students[] = $row;
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
include __DIR__ . '/../includes/header.php';
$page_title = 'Section Students';
include __DIR__ . '/../includes/sidebar.php';

$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
.student-list-content { margin-left: 250px; padding: 20px; min-height: 100vh; background: #f5f6fa; }
.section-header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
.stats-box { background: #f8f9fa; padding: 10px 20px; border-radius: 8px; display: inline-block; margin-right: 15px; }
@media (max-width: 768px) { .student-list-content { margin-left: 0; padding: 15px; } }
.table-actions { display: flex; gap: 5px; flex-wrap: wrap; }
.btn-sm { padding: 4px 8px; font-size: 12px; }
</style>

<div class="student-list-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-graduate"></i> Students in Section</h4>
            <div>
                <a href="enroll_student.php?section=<?php echo $section_id; ?>" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Enroll Student
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Sections
                </a>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Section Info -->
        <div class="section-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5>Section: <?php echo htmlspecialchars($section['section_name']); ?></h5>
                    <span class="badge bg-<?php echo ($section['status'] ?? 'Active') == 'Active' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($section['status'] ?? 'Active'); ?>
                    </span>
                    <span class="badge bg-info"><?php echo htmlspecialchars($section['program_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="col-md-4">
                    <div class="stats-box">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Semester:</strong> <?php echo htmlspecialchars($section['semester_name'] ?? $section['semester_id'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="stats-box">
                        <i class="fas fa-users"></i>
                        <strong>Enrolled:</strong> <?php echo count($students); ?> 
                        / <?php echo $section['capacity'] ?? 30; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Students (<?php echo count($students); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Enrollment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['full_name'] ?? $student['student_name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['contact_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $status = $student['student_status'] ?? 'active';
                                            $badge_class = match($status) {
                                                'active' => 'success',
                                                'confirmed' => 'info',
                                                'pending' => 'warning',
                                                'inactive' => 'danger',
                                                'graduated' => 'primary',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="../students/view.php?id=<?php echo urlencode($student['student_id']); ?>" 
                                                   class="btn btn-sm btn-info" title="View Student">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="remove_from_section.php?student_id=<?php echo urlencode($student['student_id']); ?>&section_id=<?php echo $section_id; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to remove this student from this section?')" title="Remove from Section">
                                                    <i class="fas fa-user-minus"></i>
                                                </a>
                                            </div>
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
                        <a href="enroll_student.php?section=<?php echo $section_id; ?>" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Enroll Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>