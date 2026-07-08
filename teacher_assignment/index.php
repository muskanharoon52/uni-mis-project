<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();

// Get filter parameters
$teacher = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$session = isset($_GET['session']) ? (int)$_GET['session'] : 0;

// Build query with all joins
$query = "SELECT tc.*, 
          t.teacher_id, t.teacher_name, t.email as teacher_email, t.designation,
          c.course_id, c.course_code, c.course_title, c.credit_hours,
          d.department_id, d.department_name, d.department_code,
          s.semester_id, s.semester_name, s.semester_number,
          ses.session_id, ses.session_name
          FROM teacher_courses tc
          LEFT JOIN teachers t ON tc.teacher_id = t.teacher_id
          LEFT JOIN courses c ON tc.course_id = c.course_id
          LEFT JOIN departments d ON c.department_id = d.department_id
          LEFT JOIN semesters s ON tc.semester_id = s.semester_id
          LEFT JOIN sessions ses ON tc.session_id = ses.session_id
          WHERE 1=1";

$params = [];
$types = "";

if ($teacher > 0) {
    $query .= " AND tc.teacher_id = ?";
    $params[] = $teacher;
    $types .= "i";
}

if ($department > 0) {
    $query .= " AND c.department_id = ?";
    $params[] = $department;
    $types .= "i";
}

if ($semester > 0) {
    $query .= " AND tc.semester_id = ?";
    $params[] = $semester;
    $types .= "i";
}

if ($session > 0) {
    $query .= " AND tc.session_id = ?";
    $params[] = $session;
    $types .= "i";
}

$query .= " ORDER BY t.teacher_name, c.course_code";

// Prepare and execute
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get teachers for filter
$teacher_query = "SELECT teacher_id as id, teacher_name as name FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teacher_result = $conn->query($teacher_query);
$teachers = $teacher_result ? $teacher_result->fetch_all(MYSQLI_ASSOC) : [];

// Get departments for filter
$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters for filter
$semester_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Get sessions for filter
$session_query = "SELECT session_id as id, session_name as name FROM sessions WHERE status = 'Active' ORDER BY session_name";
$session_result = $conn->query($session_query);
$sessions = $session_result ? $session_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Teacher Assignment';

include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .section-badge {
        background: #e8f0fe;
        color: #1967d2;
        padding: 2px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .course-code-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #3498db;
        background: #e8f0fe;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .credit-badge {
        background: #f3e8ff;
        color: #7c3aed;
        padding: 2px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .teacher-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .btn-add-teacher {
        background: #27ae60;
        color: white;
    }
    
    .btn-add-teacher:hover {
        background: #229954;
        color: white;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Assignment</h4>
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
                <label class="form-label">Teacher</label>
                <select name="teacher" class="form-select">
                    <option value="0">All Teachers</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>" 
                            <?php echo $teacher == $t['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Department</label>
                <select name="department" class="form-select">
                    <option value="0">All</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>" 
                            <?php echo $department == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select">
                    <option value="0">All</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?php echo $s['id']; ?>" 
                            <?php echo $semester == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Session</label>
                <select name="session" class="form-select">
                    <option value="0">All</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?php echo $s['id']; ?>" 
                            <?php echo $session == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
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
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Teacher</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Session</th>
                                <th>Section</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="teacher-name"><?php echo htmlspecialchars($assignment['teacher_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($assignment['designation'] ?? 'N/A'); ?></small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($assignment['teacher_email'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="course-code-badge">
                                            <?php echo htmlspecialchars($assignment['course_code']); ?>
                                        </span>
                                        <br>
                                        <small><?php echo htmlspecialchars($assignment['course_title']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($assignment['department_name'] ?? 'N/A'); ?>
                                        <br>
                                        <span class="credit-badge"><?php echo htmlspecialchars($assignment['credit_hours']); ?> Credits</span>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['semester_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['session_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="section-badge">
                                            <?php echo htmlspecialchars($assignment['section'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <a href="edit.php?id=<?php echo $assignment['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $assignment['id']; ?>" 
                                           class="btn btn-danger btn-sm" title="Delete"
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
                        <a href="add_teacher.php" class="btn btn-success me-2">
                            <i class="fas fa-user-plus"></i> Add Teacher First
                        </a>
                        <a href="assign.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Create Assignment
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>