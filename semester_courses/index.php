<?php
// semester_courses/index.php

// Include database (session is already started in db.php)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch courses with their assignment status and department info
$sql = "SELECT c.*, 
        d.department_name,
        GROUP_CONCAT(CONCAT(s.semester_name, ' (', s.semester_id, ')') SEPARATOR ', ') as assigned_semesters
        FROM courses c
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN semester_courses sc ON c.course_id = sc.course_id
        LEFT JOIN semesters s ON sc.semester_id = s.semester_id
        WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $sql .= " AND (c.course_code LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Add department filter
if ($department > 0) {
    $sql .= " AND c.department_id = ?";
    $params[] = $department;
    $types .= "i";
}

$sql .= " GROUP BY c.course_id ORDER BY c.course_code";

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
$courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Fetch all semesters for dropdown
$semesters_result = $conn->query("SELECT * FROM semesters ORDER BY semester_name");
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all departments for filter
$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Semester Courses Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    /* ========================================== */
    /* MAIN CONTENT STYLING - SAME AS COURSES */
    /* ========================================== */
    .semester-courses-content {
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
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.assigned {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.unassigned {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.partial {
        background: #fff3cd;
        color: #856404;
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
    
    .assigned-semesters-list {
        font-size: 12px;
        line-height: 1.4;
    }
    
    .assigned-semesters-list .badge {
        margin: 2px;
        font-size: 11px;
    }
    
    .btn-action {
        border-radius: 20px;
        padding: 5px 15px;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .semester-courses-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="semester-courses-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-layer-group"></i> Semester Courses Management</h4>
            <div>
                <a href="assign.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Assign New Course
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

        <!-- Filter Section - Same as Courses page -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by course code or title..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="department" class="form-select">
                        <option value="0">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" 
                                <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($dept['name']) ?>
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

        <!-- Courses Table -->
        <div class="card">
            <div class="card-header">
                <h5>All Courses (<?php echo count($courses); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Credit Hours</th>
                                    <th>Department</th>
                                    <th>Assigned Semesters</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($courses as $course): ?>
                                    <?php 
                                    // Determine status
                                    $isAssigned = !empty($course['assigned_semesters']);
                                    $statusClass = $isAssigned ? 'assigned' : 'unassigned';
                                    $statusText = $isAssigned ? '✅ Assigned' : '❌ Unassigned';
                                    ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <span class="course-code-badge">
                                                <?= htmlspecialchars($course['course_code']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                                        <td>
                                            <span class="credit-badge">
                                                <?= $course['credit_hours'] ?> Credits
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if($isAssigned): ?>
                                                <div class="assigned-semesters-list">
                                                    <?php 
                                                    $semestersList = explode(', ', $course['assigned_semesters']);
                                                    foreach($semestersList as $sem): 
                                                    ?>
                                                        <span class="badge bg-success">
                                                            <?= htmlspecialchars(trim($sem)) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="assign.php?course_id=<?= $course['course_id'] ?>" 
                                               class="btn btn-info btn-sm" title="Assign to Semester">
                                                <i class="fas fa-plus-circle"></i> Assign
                                            </a>
                                            <?php if($isAssigned): ?>
                                                <a href="view.php?course_id=<?= $course['course_id'] ?>" 
                                                   class="btn btn-success btn-sm" title="View Assignments">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <h5>No Courses Found</h5>
                        <p class="text-muted">No courses available. Please add courses first.</p>
                        <a href="../Courses/add.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Course
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>