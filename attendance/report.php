<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get filter parameters
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Fetch courses for dropdown
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");

// Get report data with correct column names
$sql = "SELECT 
            s.student_id,
            u.full_name as student_name,
            c.course_code,
            c.course_name,
            COUNT(a.attendance_id) as total_classes,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused,
            ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.attendance_id)) * 100, 2) as percentage
        FROM attendance a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN courses c ON a.course_id = c.course_id
        WHERE a.date BETWEEN ? AND ?";

$params = [$date_from, $date_to];
$types = "ss";

if ($course_filter > 0) {
    $sql .= " AND a.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

$sql .= " GROUP BY s.student_id, c.course_id 
          ORDER BY student_name, course_code";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get summary stats
$summary_sql = "SELECT 
                    COUNT(DISTINCT s.student_id) as total_students,
                    COUNT(DISTINCT c.course_id) as total_courses,
                    COUNT(a.attendance_id) as total_records,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as total_late,
                    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as total_excused
                FROM attendance a
                LEFT JOIN students s ON a.student_id = s.student_id
                LEFT JOIN courses c ON a.course_id = c.course_id
                WHERE a.date BETWEEN ? AND ?";

$summary_params = [$date_from, $date_to];
$summary_types = "ss";

if ($course_filter > 0) {
    $summary_sql .= " AND a.course_id = ?";
    $summary_params[] = $course_filter;
    $summary_types .= "i";
}

$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param($summary_types, ...$summary_params);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$summary = $summary_result->fetch_assoc();
$summary_stmt->close();

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Attendance Report';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .report-content {
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
    
    .stats-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stats-present .stats-number { color: #27ae60; }
    .stats-absent .stats-number { color: #e74c3c; }
    .stats-late .stats-number { color: #f39c12; }
    .stats-excused .stats-number { color: #3498db; }
    .stats-total .stats-number { color: #2c3e50; }
    
    .percentage-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 5px;
    }
    
    .percentage-bar .fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s;
    }
    
    .percentage-bar .fill.green { background: #27ae60; }
    .percentage-bar .fill.yellow { background: #f39c12; }
    .percentage-bar .fill.red { background: #e74c3c; }
    
    .status-badge {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .status-badge.present { background: #d4edda; color: #155724; }
    .status-badge.absent { background: #f8d7da; color: #721c24; }
    .status-badge.late { background: #fff3cd; color: #856404; }
    .status-badge.excused { background: #cce5ff; color: #004085; }
    
    .btn-print {
        border-radius: 20px;
        padding: 8px 20px;
    }
    
    @media (max-width: 768px) {
        .report-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
    
    @media print {
        .btn-print, .filter-section, .no-print {
            display: none !important;
        }
        .report-content {
            margin-left: 0 !important;
            padding: 10px !important;
        }
        .stats-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<div class="report-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-file-alt"></i> Attendance Report</h4>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Course</label>
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
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $summary['total_records'] ?? 0 ?></div>
                    <div class="stats-label">Total Records</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-present">
                    <div class="stats-number"><?= $summary['total_present'] ?? 0 ?></div>
                    <div class="stats-label">Present</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-absent">
                    <div class="stats-number"><?= $summary['total_absent'] ?? 0 ?></div>
                    <div class="stats-label">Absent</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-late">
                    <div class="stats-number"><?= $summary['total_late'] ?? 0 ?></div>
                    <div class="stats-label">Late</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-excused">
                    <div class="stats-number"><?= $summary['total_excused'] ?? 0 ?></div>
                    <div class="stats-label">Excused</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $summary['total_students'] ?? 0 ?></div>
                    <div class="stats-label">Students</div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card">
            <div class="card-header">
                <h5>Attendance Details</h5>
                <small class="text-muted">Period: <?= date('d M Y', strtotime($date_from)) ?> - <?= date('d M Y', strtotime($date_to)) ?></small>
            </div>
            <div class="card-body">
                <?php if (!empty($reports)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Total</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($reports as $report): ?>
                                    <?php 
                                    $percentage = $report['percentage'] ?? 0;
                                    $status_class = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                    $status_text = $percentage >= 75 ? 'Good' : ($percentage >= 50 ? 'Satisfactory' : 'Poor');
                                    ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($report['student_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($report['course_code']) ?></td>
                                        <td><?= $report['total_classes'] ?></td>
                                        <td class="text-success"><?= $report['present'] ?></td>
                                        <td class="text-danger"><?= $report['absent'] ?></td>
                                        <td class="text-warning"><?= $report['late'] ?></td>
                                        <td class="text-info"><?= $report['excused'] ?></td>
                                        <td>
                                            <strong><?= $percentage ?>%</strong>
                                            <div class="percentage-bar">
                                                <div class="fill <?= $status_class ?>" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <p>No attendance records found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>