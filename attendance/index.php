<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Fetch attendance records with correct column names
$sql = "SELECT 
            a.attendance_id,
            a.date as attendance_date,
            a.status,
            a.remark,
            s.student_id,
            s.roll_no,
            u.full_name as student_name,
            c.course_id,
            c.course_code,
            c.course_name,
            f.faculty_id,
            u2.full_name as faculty_name
        FROM attendance a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN courses c ON a.course_id = c.course_id
        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id
        LEFT JOIN users u2 ON f.user_id = u2.user_id
        WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR s.student_id LIKE ? OR c.course_code LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

// Add course filter
if ($course_filter > 0) {
    $sql .= " AND a.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

// Add status filter
if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add date range filter
if (!empty($date_from)) {
    $sql .= " AND a.date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $sql .= " AND a.date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY a.date DESC, a.attendance_id DESC";

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

// Get stats with correct column names
$total_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
                FROM attendance";
$stats_result = $conn->query($total_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

// Fetch dropdown data
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");

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
                        <?php while($row = $courses->fetch_assoc()): ?>
                            <option value="<?= $row['course_id'] ?>" 
                                <?= $course_filter == $row['course_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['course_code']) ?>
                            </option>
                        <?php endwhile; ?>
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
                                        <td><?= date('d M Y', strtotime($att['attendance_date'])) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($att['student_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($att['student_id']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($att['course_code']) ?>
                                            <br>
                                            <small><?= htmlspecialchars($att['course_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $att['status'] ?>">
                                                <?= ucfirst($att['status']) ?>
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