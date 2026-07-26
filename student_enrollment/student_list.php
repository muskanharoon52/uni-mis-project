<?php
// student_enrollment/student_list.php - View Students in Section

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

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
                  WHERE s.section_id = $section_id";
$section_result = mysqli_query($conn, $section_query);
$section = mysqli_fetch_assoc($section_result);

if (!$section) {
    header("Location: index.php?error=Section not found");
    exit;
}

// Get students from students table using section_id
$students_query = "SELECT 
                    s.student_id,
                    s.roll_no,
                    s.enrollment_date,
                    u.full_name,
                    u.email,
                    u.phone,
                    p.program_name
                   FROM students s
                   LEFT JOIN users u ON s.user_id = u.user_id
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   WHERE s.section_id = $section_id
                   ORDER BY u.full_name";

$students_result = mysqli_query($conn, $students_query);
$students = [];
if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
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
.sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; overflow-y: auto; padding-bottom: 20px; z-index: 1000; }
.sidebar .brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar .brand h4 { font-weight: 700; margin: 0; }
.sidebar .brand small { color: #a8a8b3; }
.sidebar .nav-link { color: #a8a8b3; padding: 12px 20px; border-radius: 0; transition: all 0.3s; }
.sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
.sidebar .nav-link.active { color: white; background: rgba(102, 126, 234, 0.3); border-left: 3px solid #667eea; }
.sidebar .nav-link i { width: 20px; margin-right: 10px; }
.topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
.topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
</style>

<div class="student-list-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-graduate"></i> Students in Section</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sections
            </a>
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
                    <a href="enroll_student.php?section=<?php echo $section_id; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus"></i> Add Student
                    </a>
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
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="remove_from_section.php?student_id=<?php echo urlencode($student['student_id']); ?>&section_id=<?php echo $section_id; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to remove this student from this section?')">
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