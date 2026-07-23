<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// ============================================
// CHECK AND CREATE SESSIONS IF EMPTY
// ============================================
$check_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions");
if ($check_sessions) {
    $session_count = $check_sessions->fetch_assoc();
    
    if ($session_count['count'] == 0) {
        $default_sessions = [
            ['Fall 2024', '2024-09-01', '2024-12-31', 'Active'],
            ['Spring 2025', '2025-01-15', '2025-05-30', 'Active'],
            ['Summer 2025', '2025-06-01', '2025-08-31', 'Active'],
            ['Fall 2025', '2025-09-01', '2025-12-31', 'Inactive']
        ];
        
        foreach ($default_sessions as $session) {
            $insert_session = $conn->prepare("INSERT INTO sessions (session_name, start_date, end_date, status) VALUES (?, ?, ?, ?)");
            $insert_session->bind_param("ssss", $session[0], $session[1], $session[2], $session[3]);
            $insert_session->execute();
            $insert_session->close();
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$teacher_filter = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;

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

// Add search filter
if (!empty($search)) {
    $sql .= " AND (t.teacher_name LIKE ? OR t.teacher_code LIKE ? OR c.course_code LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

// Add teacher filter
if ($teacher_filter > 0) {
    $sql .= " AND t.teacher_id = ?";
    $params[] = $teacher_filter;
    $types .= "i";
}

// Add department filter
if ($department_filter > 0) {
    $sql .= " AND t.department_id = ?";
    $params[] = $department_filter;
    $types .= "i";
}

// Add semester filter
if ($semester_filter > 0) {
    $sql .= " AND tc.semester_id = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

// Add session filter
if ($session_filter > 0) {
    $sql .= " AND tc.session_id = ?";
    $params[] = $session_filter;
    $types .= "i";
}

$sql .= " ORDER BY t.teacher_name, s.semester_name, c.course_code";

// Prepare and execute
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$assignments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Fetch all teachers for dropdown
$teachers_query = "SELECT teacher_id, teacher_name, teacher_code FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teachers_result = $conn->query($teachers_query);
$teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all departments for filter
$dept_query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all semesters for dropdown
$semesters_result = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all sessions for dropdown - Get all sessions
$sessions_query = "SELECT session_id, session_name, status FROM sessions ORDER BY session_name DESC";
$sessions_result = $conn->query($sessions_query);
$sessions = $sessions_result ? $sessions_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Teacher Assignment Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .teacher-assignment-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .teacher-badge {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .course-code-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #3498db;
        background: #e8f0fe;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 13px;
    }
    
    .semester-badge {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 3px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.Active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .primary-badge {
        background: #fff3cd;
        color: #856404;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .empty-state {
        padding: 60px 0;
        text-align: center;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .session-badge {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .teacher-assignment-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="teacher-assignment-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Assignment Management</h4>
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
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
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
                        <?php foreach($teachers as $teacher): ?>
                            <option value="<?= $teacher['teacher_id'] ?>" 
                                <?php echo $teacher_filter == $teacher['teacher_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($teacher['teacher_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department" class="form-select">
                        <option value="0">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>" 
                                <?php echo $department_filter == $dept['department_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php foreach($semesters as $semester): ?>
                            <option value="<?= $semester['semester_id'] ?>" 
                                <?php echo $semester_filter == $semester['semester_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($semester['semester_name']) ?>
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

        <!-- Assignments Table -->
        <div class="card">
            <div class="card-header">
                <h5>All Assignments (<?php echo count($assignments); ?>)</h5>
            </div>
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
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <div class="teacher-badge">
                                                <?= htmlspecialchars($assignment['teacher_name']) ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($assignment['teacher_code']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="course-code-badge">
                                                <?= htmlspecialchars($assignment['course_code']) ?>
                                            </span>
                                            <br>
                                            <small><?= htmlspecialchars($assignment['course_name']) ?></small>
                                            <br>
                                            <span class="badge bg-info">
                                                <?= $assignment['credit_hours'] ?> Credits
                                            </span>
                                        </td>
                                        <td>
                                            <span class="semester-badge">
                                                <?= htmlspecialchars($assignment['semester_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($assignment['section']): ?>
                                                <span class="badge bg-secondary">
                                                    Section: <?= htmlspecialchars($assignment['section']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="session-badge">
                                                <?= htmlspecialchars($assignment['session_name'] ?? 'N/A') ?>
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
                                            <span class="status-badge <?= $assignment['assignment_status'] ?? 'Active' ?>">
                                                <?= $assignment['assignment_status'] ?? 'Active' ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <!-- FIXED: Using correct parameter name 'id' for edit -->
                                            <a href="edit.php?id=<?= $assignment['assignment_id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- FIXED: Using correct parameter name 'id' for delete -->
                                            <a href="delete.php?id=<?= $assignment['assignment_id'] ?>" 
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
                            <?php if(empty($teachers)): ?>
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