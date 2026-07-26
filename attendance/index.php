<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

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

// Helper function to get table columns
if (!function_exists('getTableColumns')) {
    function getTableColumns($conn, $table) {
        try {
            $columns = [];
            $query = "SHOW COLUMNS FROM $table";
            $result = mysqli_query($conn, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row['Field'];
                }
            }
            return $columns;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Check which columns exist in attendance table
$attColumns = getTableColumns($conn, 'attendance');
$hasDate = in_array('date', $attColumns) || in_array('attendance_date', $attColumns) || in_array('class_date', $attColumns);
$hasStatus = in_array('status', $attColumns);
$hasRemark = in_array('remark', $attColumns) || in_array('remarks', $attColumns);
$hasStudentId = in_array('student_id', $attColumns);
$hasCourseId = in_array('course_id', $attColumns);
$hasFacultyId = in_array('faculty_id', $attColumns);

// Determine the correct date column name
$dateColumn = 'date';
if (in_array('attendance_date', $attColumns)) {
    $dateColumn = 'attendance_date';
} elseif (in_array('class_date', $attColumns)) {
    $dateColumn = 'class_date';
} elseif (in_array('date', $attColumns)) {
    $dateColumn = 'date';
} else {
    $dateColumn = 'date';
}

// Determine the correct remark column name
$remarkColumn = 'remark';
if (in_array('remarks', $attColumns)) {
    $remarkColumn = 'remarks';
} elseif (in_array('remark', $attColumns)) {
    $remarkColumn = 'remark';
} else {
    $remarkColumn = 'remark';
}

// Check which columns exist in courses table
$courseColumns = getTableColumns($conn, 'courses');
$hasCourseCode = in_array('course_code', $courseColumns);
$hasCourseName = in_array('course_name', $courseColumns);
$hasCourseTitle = in_array('course_title', $courseColumns);
$hasCourseIdCol = in_array('course_id', $courseColumns);

// Determine the correct course name column
$courseNameColumn = 'course_name';
if (in_array('course_title', $courseColumns)) {
    $courseNameColumn = 'course_title';
} elseif (in_array('name', $courseColumns)) {
    $courseNameColumn = 'name';
} elseif (in_array('course_name', $courseColumns)) {
    $courseNameColumn = 'course_name';
} else {
    $courseNameColumn = 'course_name';
}

// Check which columns exist in students table
$studentColumns = getTableColumns($conn, 'students');
$hasStudentRollNo = in_array('roll_no', $studentColumns);
$hasStudentUserId = in_array('user_id', $studentColumns);

// Check which columns exist in users table
$userColumns = getTableColumns($conn, 'users');
$hasUserFullName = in_array('full_name', $userColumns);

// Check which columns exist in faculty table
$facultyColumns = getTableColumns($conn, 'faculty');
$hasFacultyUserId = in_array('user_id', $facultyColumns);
$hasFacultyName = in_array('faculty_name', $facultyColumns) || in_array('name', $facultyColumns);
$hasFacultyIdCol = in_array('faculty_id', $facultyColumns);

// Determine the correct faculty name column
$facultyNameColumn = 'faculty_name';
if (in_array('name', $facultyColumns)) {
    $facultyNameColumn = 'name';
} elseif (in_array('faculty_name', $facultyColumns)) {
    $facultyNameColumn = 'faculty_name';
} else {
    $facultyNameColumn = 'faculty_name';
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build SELECT query based on available columns
$selectFields = [
    'a.attendance_id'
];

if ($hasDate) {
    $selectFields[] = "a.$dateColumn as attendance_date";
} else {
    $selectFields[] = "NOW() as attendance_date";
}

if ($hasStatus) {
    $selectFields[] = 'a.status';
} else {
    $selectFields[] = "'present' as status";
}

if ($hasRemark) {
    $selectFields[] = "a.$remarkColumn as remark";
} else {
    $selectFields[] = "NULL as remark";
}

if ($hasStudentId) {
    $selectFields[] = 'a.student_id';
} else {
    $selectFields[] = "NULL as student_id";
}

if ($hasCourseId) {
    $selectFields[] = 'a.course_id';
} else {
    $selectFields[] = "NULL as course_id";
}

if ($hasFacultyId) {
    $selectFields[] = 'a.faculty_id';
} else {
    $selectFields[] = "NULL as faculty_id";
}

// Add student fields
if ($hasStudentRollNo) {
    $selectFields[] = 's.roll_no';
} else {
    $selectFields[] = "'N/A' as roll_no";
}

if ($hasUserFullName && $hasStudentUserId) {
    $selectFields[] = 'u.full_name as student_name';
} else {
    $selectFields[] = "'N/A' as student_name";
}

// Add course fields
if ($hasCourseCode) {
    $selectFields[] = 'c.course_code';
} else {
    $selectFields[] = "'N/A' as course_code";
}

if ($hasCourseName || $hasCourseTitle) {
    $selectFields[] = "c.$courseNameColumn as course_name";
} else {
    $selectFields[] = "'N/A' as course_name";
}

// Add faculty fields - check what's available
if ($hasFacultyName) {
    $selectFields[] = "f.$facultyNameColumn as faculty_name";
} elseif ($hasUserFullName && $hasFacultyUserId) {
    $selectFields[] = "u2.full_name as faculty_name";
} else {
    $selectFields[] = "'N/A' as faculty_name";
}

// Build the SQL query
$sql = "SELECT \n            " . implode(",\n            ", $selectFields);
$sql .= "\n        FROM attendance a";

// Join students - only if student_id exists
if ($hasStudentId) {
    $sql .= "\n        LEFT JOIN students s ON a.student_id = s.student_id";
    if ($hasStudentUserId) {
        $sql .= "\n        LEFT JOIN users u ON s.user_id = u.user_id";
    } else {
        $sql .= "\n        LEFT JOIN users u ON 1=0";
    }
} else {
    $sql .= "\n        LEFT JOIN students s ON 1=0";
    $sql .= "\n        LEFT JOIN users u ON 1=0";
}

// Join courses - only if course_id exists
if ($hasCourseId) {
    $sql .= "\n        LEFT JOIN courses c ON a.course_id = c.course_id";
} else {
    $sql .= "\n        LEFT JOIN courses c ON 1=0";
}

// Join faculty - only if faculty_id exists
if ($hasFacultyId && $hasFacultyIdCol) {
    $sql .= "\n        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id";
    // Only join users if faculty has user_id and users table has full_name
    if ($hasFacultyUserId && $hasUserFullName) {
        $sql .= "\n        LEFT JOIN users u2 ON f.user_id = u2.user_id";
    } else {
        $sql .= "\n        LEFT JOIN users u2 ON 1=0";
    }
} else {
    $sql .= "\n        LEFT JOIN faculty f ON 1=0";
    $sql .= "\n        LEFT JOIN users u2 ON 1=0";
}

$sql .= "\n        WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $searchConditions = [];
    
    if ($hasUserFullName && $hasStudentUserId) {
        $searchConditions[] = "u.full_name LIKE ?";
    }
    
    if ($hasStudentId) {
        $searchConditions[] = "s.student_id LIKE ?";
    }
    
    if ($hasCourseCode) {
        $searchConditions[] = "c.course_code LIKE ?";
    }
    
    if ($hasCourseName || $hasCourseTitle) {
        $searchConditions[] = "c.$courseNameColumn LIKE ?";
    }
    
    if ($hasFacultyName) {
        $searchConditions[] = "f.$facultyNameColumn LIKE ?";
    }
    
    // If no search conditions available, search by attendance_id
    if (empty($searchConditions)) {
        $searchConditions[] = "a.attendance_id LIKE ?";
    }
    
    $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $searchParam = "%$search%";
    
    foreach ($searchConditions as $condition) {
        $params[] = $searchParam;
        $types .= "s";
    }
}

// Add course filter
if ($course_filter > 0 && $hasCourseId) {
    $sql .= " AND a.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

// Add status filter
if (!empty($status_filter) && $hasStatus) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add date range filter
if (!empty($date_from) && $hasDate) {
    $sql .= " AND a.$dateColumn >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to) && $hasDate) {
    $sql .= " AND a.$dateColumn <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY a.attendance_id DESC";

// Debug: Uncomment to see the query
// echo "<pre>$sql</pre>";
// print_r($params);
// exit;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$attendances = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get stats - simplified query
$stats_query = "SELECT 
                    COUNT(*) as total";
if ($hasStatus) {
    $stats_query .= ",\n                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused";
} else {
    $stats_query .= ",\n                    0 as present,
                    0 as absent,
                    0 as late,
                    0 as excused";
}
$stats_query .= "\n                FROM attendance";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

// Fetch dropdown data - only if course_id and course_code exist
$courses = [];
if ($hasCourseId && $hasCourseCode) {
    $courseQuery = "SELECT course_id, course_code";
    if ($hasCourseName || $hasCourseTitle) {
        $courseQuery .= ", $courseNameColumn as course_name";
    }
    $courseQuery .= " FROM courses ORDER BY course_code";
    $courses_result = $conn->query($courseQuery);
    if ($courses_result) {
        while ($row = $courses_result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Attendance Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .attendance-content {
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
        font-size: 2.5rem;
        font-weight: 700;
    }
    
    .stats-label {
        font-size: 0.9rem;
        color: #7f8c8d;
        margin-top: 5px;
    }
    
    .stats-present .stats-number { color: #27ae60; }
    .stats-absent .stats-number { color: #e74c3c; }
    .stats-late .stats-number { color: #f39c12; }
    .stats-excused .stats-number { color: #3498db; }
    .stats-total .stats-number { color: #2c3e50; }
    
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
        text-transform: capitalize;
    }
    
    .status-badge.present {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.absent {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.late {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-badge.excused {
        background: #cce5ff;
        color: #004085;
    }
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .btn-mark {
        border-radius: 20px;
        padding: 8px 20px;
    }
    
    @media (max-width: 768px) {
        .attendance-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="attendance-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-clipboard-list"></i> Attendance Management</h4>
            <a href="mark.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Mark Attendance
            </a>
        </div>

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
            <div class="col-md-2">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stats-label">Total</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-present">
                    <div class="stats-number"><?= $stats['present'] ?? 0 ?></div>
                    <div class="stats-label">Present</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-absent">
                    <div class="stats-number"><?= $stats['absent'] ?? 0 ?></div>
                    <div class="stats-label">Absent</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-late">
                    <div class="stats-number"><?= $stats['late'] ?? 0 ?></div>
                    <div class="stats-label">Late</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-excused">
                    <div class="stats-number"><?= $stats['excused'] ?? 0 ?></div>
                    <div class="stats-label">Excused</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search student/course..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="course" class="form-select">
                        <option value="0">All Courses</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" 
                                <?= $course_filter == $course['course_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="present" <?= $status_filter == 'present' ? 'selected' : '' ?>>Present</option>
                        <option value="absent" <?= $status_filter == 'absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="late" <?= $status_filter == 'late' ? 'selected' : '' ?>>Late</option>
                        <option value="excused" <?= $status_filter == 'excused' ? 'selected' : '' ?>>Excused</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="Date From" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="Date To" value="<?= $date_to ?>">
                </div>
                <div class="col-md-1">
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Attendance Records (<?= count($attendances) ?>)</h5>
                <div>
                    <a href="report.php" class="btn btn-info btn-sm">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($attendances)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Remark</th>
                                    <th>Faculty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($attendances as $att): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <?php 
                                            $date_value = $att['attendance_date'] ?? null;
                                            if ($date_value && $date_value != '0000-00-00' && $date_value != 'NULL') {
                                                echo date('d M Y', strtotime($date_value));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($att['student_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($att['student_id'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($att['course_code'] ?? 'N/A') ?>
                                            <?php if (!empty($att['course_name']) && $att['course_name'] != 'N/A'): ?>
                                                <br>
                                                <small><?= htmlspecialchars($att['course_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $att['status'] ?? 'present' ?>">
                                                <?= ucfirst($att['status'] ?? 'Present') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($att['remark'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($att['faculty_name'] ?? 'N/A') ?></td>
                                        <td class="table-actions">
                                            <a href="edit.php?id=<?= $att['attendance_id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?= $att['attendance_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $att['attendance_id'] ?>" 
                                               class="btn btn-danger btn-sm" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this record?')">
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
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <h5>No Attendance Records Found</h5>
                        <p class="text-muted">Start by marking attendance for today.</p>
                        <a href="mark.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Mark Attendance
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>