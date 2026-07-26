<?php
// teacher_assignment/index.php - Teacher Assignment Management (FIXED)

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '../config/db_connect.php';
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

// ============================================
// DELETE TEACHER
// ============================================
if (isset($_GET['delete_teacher']) && isset($_GET['teacher_id'])) {
    $teacher_id = (int)$_GET['teacher_id'];
    
    // Disable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // Delete from teacher_courses first
    mysqli_query($conn, "DELETE FROM teacher_courses WHERE teacher_id = $teacher_id");
    
    // Delete the teacher
    $delete_query = "DELETE FROM teachers WHERE teacher_id = $teacher_id";
    if (mysqli_query($conn, $delete_query)) {
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        header('Location: index.php?success=Teacher deleted successfully');
        exit;
    } else {
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        header('Location: index.php?error=Failed to delete teacher: ' . urlencode(mysqli_error($conn)));
        exit;
    }
}

// ============================================
// DELETE ASSIGNMENT
// ============================================
if (isset($_GET['delete_assignment']) && isset($_GET['assignment_id'])) {
    $assignment_id = (int)$_GET['assignment_id'];
    $delete_query = "DELETE FROM teacher_courses WHERE id = $assignment_id";
    if (mysqli_query($conn, $delete_query)) {
        header('Location: index.php?success=Assignment deleted successfully');
        exit;
    } else {
        header('Location: index.php?error=Failed to delete assignment');
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$teacher_filter = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;

// ============================================
// GET ALL TEACHERS
// ============================================
$teachers_query = "SELECT t.*, d.department_name 
                   FROM teachers t
                   LEFT JOIN departments d ON t.department_id = d.department_id
                   ORDER BY t.teacher_name";
$teachers_result = mysqli_query($conn, $teachers_query);
$all_teachers = [];
if ($teachers_result) {
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $all_teachers[] = $row;
    }
}

// Fetch assignments with all related data
$sql = "SELECT 
            tc.id as assignment_id,
            tc.section,
            tc.is_primary,
            tc.status as assignment_status,
            tc.assigned_date,
            t.teacher_id,
            t.teacher_code,
            t.teacher_name,
            t.email,
            t.phone,
            t.specialization,
            d.department_name,
            c.course_id,
            c.course_code,
            c.course_name,
            c.credit_hours,
            s.semester_id,
            s.semester_name,
            sess.session_id,
            sess.session_name
        FROM teacher_courses tc
        LEFT JOIN teachers t ON tc.teacher_id = t.teacher_id
        LEFT JOIN departments d ON t.department_id = d.department_id
        LEFT JOIN courses c ON tc.course_id = c.course_id
        LEFT JOIN semesters s ON tc.semester_id = s.semester_id
        LEFT JOIN sessions sess ON tc.session_id = sess.session_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (t.teacher_name LIKE ? OR t.teacher_code LIKE ? OR c.course_code LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

if ($teacher_filter > 0) {
    $sql .= " AND t.teacher_id = ?";
    $params[] = $teacher_filter;
    $types .= "i";
}

if ($department_filter > 0) {
    $sql .= " AND t.department_id = ?";
    $params[] = $department_filter;
    $types .= "i";
}

if ($semester_filter > 0) {
    $sql .= " AND tc.semester_id = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

if ($session_filter > 0) {
    $sql .= " AND tc.session_id = ?";
    $params[] = $session_filter;
    $types .= "i";
}

$sql .= " ORDER BY t.teacher_name, s.semester_name, c.course_code";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die("Error in query: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignments = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
    }
}
mysqli_stmt_close($stmt);

// Fetch departments for filter
$dept_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments ORDER BY department_name");
$departments = [];
if ($dept_result) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $departments[] = $row;
    }
}

// Fetch semesters for dropdown
$semesters_result = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$semesters = [];
if ($semesters_result) {
    while ($row = mysqli_fetch_assoc($semesters_result)) {
        $semesters[] = $row;
    }
}

// Fetch sessions
$sessions_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions ORDER BY session_name DESC");
$sessions = [];
if ($sessions_result) {
    while ($row = mysqli_fetch_assoc($sessions_result)) {
        $sessions[] = $row;
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
include __DIR__ . '/../includes/header.php';
$page_title = 'Teacher Assignment Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .teacher-assignment-content { margin-left: 250px; padding: 20px; min-height: 100vh; background: #f5f6fa; }
    .filter-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .teacher-badge { font-weight: 600; color: #2c3e50; }
    .course-code-badge { font-family: 'Courier New', monospace; font-weight: 700; color: #3498db; background: #e8f0fe; padding: 3px 10px; border-radius: 5px; font-size: 13px; }
    .semester-badge { background: #e8f5e9; color: #2e7d32; padding: 3px 12px; border-radius: 15px; font-size: 12px; font-weight: 500; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .status-badge.Active { background: #d4edda; color: #155724; }
    .status-badge.Inactive { background: #f8d7da; color: #721c24; }
    .primary-badge { background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
    .table-actions .btn { padding: 4px 8px; font-size: 12px; margin: 0 2px; }
    .empty-state { padding: 40px 0; text-align: center; color: #95a5a6; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
    .teacher-card { background: white; border-radius: 10px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 10px; border-left: 4px solid #3498db; }
    .teacher-card .teacher-name { font-weight: 600; color: #2c3e50; }
    .teacher-card .teacher-code { color: #7f8c8d; font-size: 13px; }
    .teacher-card .teacher-dept { color: #3498db; font-size: 13px; }
    .teacher-card .teacher-actions { margin-top: 8px; }
    .section-title { background: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    @media (max-width: 768px) { .teacher-assignment-content { margin-left: 0; padding: 15px; } }
</style>

<div class="teacher-assignment-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Management</h4>
            <div>
                <a href="add_teacher.php" class="btn btn-success me-2">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </a>
                <a href="assign.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> New Assignment
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TEACHERS LIST -->
        <!-- ============================================ -->
        <div class="section-title">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users text-primary"></i> All Teachers (<?php echo count($all_teachers); ?>)</h5>
                <a href="add_teacher.php" class="btn btn-sm btn-success">
                    <i class="fas fa-plus"></i> Add Teacher
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php if (!empty($all_teachers)): ?>
                <?php foreach ($all_teachers as $teacher): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="teacher-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="teacher-name">
                                        <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                                    </div>
                                    <div class="teacher-code">
                                        <i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($teacher['teacher_code'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="teacher-dept">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($teacher['department_name'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="teacher-dept">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher['email'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="teacher-dept">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($teacher['phone'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <span class="badge bg-<?php echo ($teacher['status'] ?? 'Active') == 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $teacher['status'] ?? 'Active'; ?>
                                </span>
                            </div>
                            <div class="teacher-actions">
                                <a href="edit_teacher.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?delete_teacher=1&teacher_id=<?php echo $teacher['teacher_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this teacher? This will also remove all their assignments.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <a href="assign.php?teacher_id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Assign Course
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h5>No Teachers Found</h5>
                        <p class="text-muted">Add your first teacher to get started.</p>
                        <a href="add_teacher.php" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Add Teacher
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- FILTER SECTION -->
        <!-- ============================================ -->
        <div class="section-title mt-4">
            <h5><i class="fas fa-filter text-primary"></i> Filter Assignments</h5>
        </div>

        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search teacher or course..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="teacher" class="form-select">
                        <option value="0">All Teachers</option>
                        <?php foreach($all_teachers as $teacher): ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>" 
                                <?php echo $teacher_filter == $teacher['teacher_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department" class="form-select">
                        <option value="0">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" 
                                <?php echo $department_filter == $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php foreach($semesters as $semester): ?>
                            <option value="<?php echo $semester['semester_id']; ?>" 
                                <?php echo $semester_filter == $semester['semester_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester['semester_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- ASSIGNMENTS TABLE -->
        <!-- ============================================ -->
        <div class="section-title mt-4">
            <h5><i class="fas fa-list text-primary"></i> Course Assignments (<?php echo count($assignments); ?>)</h5>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (!empty($assignments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable" id="assignmentsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Teacher</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Section</th>
                                    <th>Session</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <div class="teacher-badge">
                                                <?php echo htmlspecialchars($assignment['teacher_name']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($assignment['teacher_code']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="course-code-badge">
                                                <?php echo htmlspecialchars($assignment['course_code']); ?>
                                            </span>
                                            <br>
                                            <small><?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                            <br>
                                            <span class="badge bg-info">
                                                <?php echo $assignment['credit_hours']; ?> Credits
                                            </span>
                                        </td>
                                        <td>
                                            <span class="semester-badge">
                                                <?php echo htmlspecialchars($assignment['semester_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($assignment['section']): ?>
                                                <span class="badge bg-secondary">
                                                    Section: <?php echo htmlspecialchars($assignment['section']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="session-badge" style="background:#e3f2fd;color:#0d47a1;padding:3px 10px;border-radius:12px;font-size:11px;">
                                                <?php echo htmlspecialchars($assignment['session_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($assignment['is_primary']): ?>
                                                <span class="primary-badge">
                                                    <i class="fas fa-star"></i> Primary
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Secondary</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $assignment['assignment_status'] ?? 'Active'; ?>">
                                                <?php echo $assignment['assignment_status'] ?? 'Active'; ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="edit.php?id=<?php echo $assignment['assignment_id']; ?>" 
                                               class="btn btn-warning btn-sm" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?delete_assignment=1&assignment_id=<?php echo $assignment['assignment_id']; ?>" 
                                               class="btn btn-danger btn-sm" title="Delete Assignment"
                                               onclick="return confirm('Are you sure you want to delete this assignment?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h5>No Assignments Found</h5>
                        <p class="text-muted">No teacher-course assignments found.</p>
                        <div class="mt-3">
                            <?php if(empty($all_teachers)): ?>
                                <a href="add_teacher.php" class="btn btn-success me-2">
                                    <i class="fas fa-user-plus"></i> Add Teacher First
                                </a>
                            <?php endif; ?>
                            <a href="assign.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Create Assignment
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>