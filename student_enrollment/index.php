<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

// Helper function to check if table exists
if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

// Helper function to check if column exists
if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $query = "SHOW COLUMNS FROM $table LIKE '$column'";
            $result = mysqli_query($conn, $query);
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) {
            return false;
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program_filter = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Check if tables exist
$sectionCoursesExists = tableExists($conn, 'section_courses');
$semestersExists = tableExists($conn, 'semesters');
$sessionsExists = tableExists($conn, 'sessions');
$programsExists = tableExists($conn, 'programs');
$studentEnrollmentsExists = tableExists($conn, 'student_enrollments');

// Check columns in sections table
$hasProgramId = columnExists($conn, 'sections', 'program_id');
$hasSemesterId = columnExists($conn, 'sections', 'semester_id');
$hasSessionId = columnExists($conn, 'sections', 'session_id');
$hasEnrolledCount = columnExists($conn, 'sections', 'enrolled_count');
$hasCapacity = columnExists($conn, 'sections', 'capacity');
$hasStatus = columnExists($conn, 'sections', 'status');
$hasCreatedAt = columnExists($conn, 'sections', 'created_at');

// Start building the SELECT query
$selectFields = array(
    "s.section_id",
    "s.section_name"
);

// Only include columns if they exist
if ($hasCapacity) {
    $selectFields[] = "s.capacity";
} else {
    $selectFields[] = "0 as capacity";
}

if ($hasEnrolledCount) {
    $selectFields[] = "s.enrolled_count";
} else {
    $selectFields[] = "0 as enrolled_count";
}

if ($hasStatus) {
    $selectFields[] = "s.status";
} else {
    $selectFields[] = "'Active' as status";
}

if ($hasCreatedAt) {
    $selectFields[] = "s.created_at";
} else {
    $selectFields[] = "NULL as created_at";
}

// Add program fields if programs table exists and sections has program_id
if ($programsExists && $hasProgramId) {
    $selectFields[] = "p.program_name";
    $selectFields[] = "p.program_code";
} else {
    $selectFields[] = "NULL as program_name";
    $selectFields[] = "NULL as program_code";
}

// Add semester fields if semesters table exists and sections has semester_id
if ($semestersExists && $hasSemesterId) {
    $selectFields[] = "sm.semester_name";
} else {
    $selectFields[] = "NULL as semester_name";
}

// Add session fields if sessions table exists and sections has session_id
if ($sessionsExists && $hasSessionId) {
    $selectFields[] = "ses.session_name";
} else {
    $selectFields[] = "NULL as session_name";
}

// Count students and courses only if tables exist
if ($studentEnrollmentsExists) {
    $selectFields[] = "COUNT(DISTINCT se.student_id) as total_students";
} else {
    $selectFields[] = "0 as total_students";
}

if ($sectionCoursesExists) {
    $selectFields[] = "COUNT(DISTINCT sc.course_id) as total_courses";
} else {
    $selectFields[] = "0 as total_courses";
}

// Build the complete SQL query
$sql = "SELECT \n            " . implode(",\n            ", $selectFields);
$sql .= "\n        FROM sections s";

// Add LEFT JOINs only if tables and required columns exist
if ($programsExists && $hasProgramId) {
    $sql .= "\n        LEFT JOIN programs p ON s.program_id = p.program_id";
}

if ($semestersExists && $hasSemesterId) {
    $sql .= "\n        LEFT JOIN semesters sm ON s.semester_id = sm.semester_id";
}

if ($sessionsExists && $hasSessionId) {
    $sql .= "\n        LEFT JOIN sessions ses ON s.session_id = ses.session_id";
}

if ($studentEnrollmentsExists && $hasProgramId) {
    $sql .= "\n        LEFT JOIN student_enrollments se ON s.section_id = se.section_id AND se.status = 'Enrolled'";
}

if ($sectionCoursesExists && $hasProgramId) {
    $sql .= "\n        LEFT JOIN section_courses sc ON s.section_id = sc.section_id";
}

$sql .= "\n        WHERE 1=1";

$params = [];
$types = "";

// Build WHERE clause
if (!empty($search)) {
    if ($programsExists && $hasProgramId) {
        $sql .= " AND (s.section_name LIKE ? OR p.program_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    } else {
        $sql .= " AND s.section_name LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $types .= "s";
    }
}

if ($program_filter > 0 && $programsExists && $hasProgramId) {
    $sql .= " AND s.program_id = ?";
    $params[] = $program_filter;
    $types .= "i";
}

if ($semester_filter > 0 && $semestersExists && $hasSemesterId) {
    $sql .= " AND s.semester_id = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

if (!empty($status_filter) && $hasStatus) {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " GROUP BY s.section_id ORDER BY s.section_name";

// Execute query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$sections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get stats - Simplified query with column checking
$stats_query = "SELECT \n                    COUNT(*) as total_sections";
if ($hasStatus) {
    $stats_query .= ",\n                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_sections";
} else {
    $stats_query .= ",\n                    0 as active_sections";
}
if ($hasEnrolledCount) {
    $stats_query .= ",\n                    SUM(enrolled_count) as total_enrolled";
} else {
    $stats_query .= ",\n                    0 as total_enrolled";
}
if ($hasCapacity) {
    $stats_query .= ",\n                    SUM(capacity) as total_capacity";
} else {
    $stats_query .= ",\n                    0 as total_capacity";
}
$stats_query .= "\n                FROM sections";

$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_sections' => 0,
    'active_sections' => 0,
    'total_enrolled' => 0,
    'total_capacity' => 0
];

// Fetch dropdown data - Only if tables exist
$programs = [];
if ($programsExists) {
    $programs_result = $conn->query("SELECT program_id, program_name, program_code FROM programs WHERE status = 'Active' ORDER BY program_name");
    if ($programs_result) {
        while ($row = $programs_result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
}

$semesters = [];
if ($semestersExists) {
    $semesters_result = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
    if ($semesters_result) {
        while ($row = $semesters_result->fetch_assoc()) {
            $semesters[] = $row;
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Student Enrollment Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .enrollment-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .stats-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stats-label {
        font-size: 0.9rem;
        color: #7f8c8d;
        margin-top: 5px;
    }
    
    .stats-total .stats-number { color: #2c3e50; }
    .stats-active .stats-number { color: #27ae60; }
    .stats-enrolled .stats-number { color: #3498db; }
    .stats-capacity .stats-number { color: #9b59b6; }
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    
    .capacity-bar {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 5px;
    }
    
    .capacity-bar .fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s;
    }
    
    .capacity-bar .fill.green { background: #27ae60; }
    .capacity-bar .fill.yellow { background: #f39c12; }
    .capacity-bar .fill.red { background: #e74c3c; }
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    @media (max-width: 768px) {
        .enrollment-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="enrollment-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-users"></i> Student Enrollment Management</h4>
            <div>
                <a href="add_section.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create Section
                </a>
                <a href="enroll_student.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Enroll Student
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $stats['total_sections'] ?? 0 ?></div>
                    <div class="stats-label">Total Sections</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-active">
                    <div class="stats-number"><?= $stats['active_sections'] ?? 0 ?></div>
                    <div class="stats-label">Active Sections</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-enrolled">
                    <div class="stats-number"><?= $stats['total_enrolled'] ?? 0 ?></div>
                    <div class="stats-label">Total Enrolled</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-capacity">
                    <div class="stats-number"><?= $stats['total_capacity'] ?? 0 ?></div>
                    <div class="stats-label">Total Capacity</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search section or program..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="program" class="form-select">
                        <option value="0">All Programs</option>
                        <?php foreach($programs as $row): ?>
                            <option value="<?= $row['program_id'] ?>" 
                                <?= $program_filter == $row['program_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['program_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php foreach($semesters as $row): ?>
                            <option value="<?= $row['semester_id'] ?>" 
                                <?= $semester_filter == $row['semester_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['semester_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h5>Sections (<?= count($sections) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($sections)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="sectionsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Section</th>
                                    <th>Program</th>
                                    <th>Semester</th>
                                    <th>Students</th>
                                    <th>Capacity</th>
                                    <th>Courses</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($sections as $section): ?>
                                    <?php 
                                    $capacity = $section['capacity'] ?? 0;
                                    $enrolled = $section['enrolled_count'] ?? 0;
                                    $percentage = $capacity > 0 ? ($enrolled / $capacity) * 100 : 0;
                                    $color = $percentage >= 90 ? 'red' : ($percentage >= 70 ? 'yellow' : 'green');
                                    ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($section['section_name'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($section['program_name'] ?? 'N/A') ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($section['program_code'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($section['semester_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= $section['total_students'] ?? 0 ?>
                                            <div class="capacity-bar">
                                                <div class="fill <?= $color ?>" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <small><?= round($percentage) ?>% filled</small>
                                        </td>
                                        <td><?= $capacity ?></td>
                                        <td><?= $section['total_courses'] ?? 0 ?></td>
                                        <td>
                                            <span class="status-badge <?= $section['status'] ?? 'Active' ?>">
                                                <?= $section['status'] ?? 'Active' ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="student_list.php?section=<?= $section['section_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View Students">
                                                <i class="fas fa-user-graduate"></i>
                                            </a>
                                            <a href="assign_course.php?section=<?= $section['section_id'] ?>" 
                                               class="btn btn-primary btn-sm" title="Assign Course">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <a href="edit_section.php?id=<?= $section['section_id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_section.php?id=<?= $section['section_id'] ?>" 
                                               class="btn btn-danger btn-sm" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this section?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Sections Found</h5>
                        <p class="text-muted">Create a section to start enrolling students.</p>
                        <a href="add_section.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Create Section
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>