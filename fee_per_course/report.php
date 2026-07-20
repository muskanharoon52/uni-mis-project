<?php
// fee_per_course/report.php - NO session_start() here (db.php handles it)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? '';
if (!in_array($role, ['admin', 'sso', 'account'])) {
    header('Location: ../dashboard.php');
    exit;
}

$conn = getConnection();

// Get filter parameters
$program_filter = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get programs for dropdown
$programs = [];
$prog_result = $conn->query("SELECT program_id, program_name FROM programs WHERE status = 'Active' ORDER BY program_name");
if ($prog_result) {
    while ($row = $prog_result->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Get semesters for dropdown
$semesters = [];
$sem_result = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
if ($sem_result) {
    while ($row = $sem_result->fetch_assoc()) {
        $semesters[] = $row;
    }
}

$report_data = [];
$error_message = '';

// ============================================
// CHECK: Which table has data?
// ============================================

// Check fee_records table first (your database has data here)
$table_check = $conn->query("SHOW TABLES LIKE 'fee_records'");
$has_fee_records = ($table_check && $table_check->num_rows > 0);

// Check student_course_fees table
$table_check2 = $conn->query("SHOW TABLES LIKE 'student_course_fees'");
$has_course_fees = ($table_check2 && $table_check2->num_rows > 0);

// Check if student_fee table exists
$table_check3 = $conn->query("SHOW TABLES LIKE 'student_fee'");
$has_student_fee = ($table_check3 && $table_check3->num_rows > 0);

// ============================================
// USE fee_records TABLE (Has data)
// ============================================

if ($has_fee_records) {
    
    $sql = "SELECT 
                student_id,
                total_fee as total_amount,
                paid_amount,
                (total_fee - paid_amount) as remaining,
                status,
                due_date,
                semester as semester_id
            FROM fee_records 
            WHERE 1=1";
    
    if ($semester_filter > 0) {
        $sql .= " AND semester = " . intval($semester_filter);
    }
    if (!empty($status_filter)) {
        $sql .= " AND status = '" . $conn->real_escape_string(strtolower($status_filter)) . "'";
    }
    if (!empty($date_from)) {
        $sql .= " AND due_date >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if (!empty($date_to)) {
        $sql .= " AND due_date <= '" . $conn->real_escape_string($date_to) . "'";
    }
    
    $sql .= " ORDER BY student_id";
    
    // DIRECT QUERY - NO prepare(), NO execute()
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $student_id = $row['student_id'];
            
            // Get student details
            $student_sql = "SELECT s.full_name, s.roll_no, s.program_id, p.program_name 
                            FROM students s
                            LEFT JOIN programs p ON s.program_id = p.program_id
                            WHERE s.student_id = '" . $conn->real_escape_string($student_id) . "'";
            $student_result = $conn->query($student_sql);
            $student = $student_result ? $student_result->fetch_assoc() : null;
            
            // Apply program filter
            if ($program_filter > 0 && ($student['program_id'] ?? 0) != $program_filter) {
                continue;
            }
            
            // Get semester name
            $sem_name = 'Semester ' . ($row['semester_id'] ?? 'N/A');
            if ($row['semester_id'] > 0) {
                $sem_sql = "SELECT semester_name FROM semesters WHERE semester_id = " . intval($row['semester_id']);
                $sem_result = $conn->query($sem_sql);
                if ($sem_result) {
                    $sem_row = $sem_result->fetch_assoc();
                    if ($sem_row) {
                        $sem_name = $sem_row['semester_name'];
                    }
                }
            }
            
            // Add course info (since fee_records doesn't have course_id)
            $report_data[] = [
                'student_id' => $student_id,
                'student_name' => $student['full_name'] ?? 'Unknown',
                'roll_no' => $student['roll_no'] ?? 'N/A',
                'program_name' => $student['program_name'] ?? 'N/A',
                'semester_name' => $sem_name,
                'course_code' => 'N/A',
                'course_name' => 'Fee Record',
                'credit_hours' => 0,
                'total_amount' => floatval($row['total_amount'] ?? 0),
                'paid_amount' => floatval($row['paid_amount'] ?? 0),
                'remaining' => floatval($row['remaining'] ?? 0),
                'status' => ucfirst($row['status'] ?? 'Unpaid'),
                'due_date' => $row['due_date'] ?? null
            ];
        }
    } else {
        $error_message = "Query error: " . $conn->error;
    }
    
} elseif ($has_course_fees) {
    // Use student_course_fees table
    $sql = "SELECT 
                scf.student_id,
                scf.fee_amount as total_amount,
                scf.paid_amount,
                (scf.fee_amount - scf.paid_amount) as remaining,
                scf.status,
                scf.due_date,
                scf.semester_id,
                scf.course_id
            FROM student_course_fees scf
            WHERE 1=1";
    
    if ($semester_filter > 0) {
        $sql .= " AND scf.semester_id = " . intval($semester_filter);
    }
    if (!empty($status_filter)) {
        $sql .= " AND scf.status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    if (!empty($date_from)) {
        $sql .= " AND scf.created_at >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if (!empty($date_to)) {
        $sql .= " AND scf.created_at <= '" . $conn->real_escape_string($date_to) . "'";
    }
    
    $sql .= " ORDER BY scf.student_id";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $student_id = $row['student_id'];
            
            // Get student details
            $student_sql = "SELECT s.full_name, s.roll_no, s.program_id, p.program_name 
                            FROM students s
                            LEFT JOIN programs p ON s.program_id = p.program_id
                            WHERE s.student_id = '" . $conn->real_escape_string($student_id) . "'";
            $student_result = $conn->query($student_sql);
            $student = $student_result ? $student_result->fetch_assoc() : null;
            
            // Apply program filter
            if ($program_filter > 0 && ($student['program_id'] ?? 0) != $program_filter) {
                continue;
            }
            
            // Get course details
            $course_name = 'N/A';
            $course_code = 'N/A';
            $credit_hours = 0;
            if ($row['course_id'] > 0) {
                $course_sql = "SELECT course_code, course_name, credit_hours FROM courses WHERE course_id = " . intval($row['course_id']);
                $course_result = $conn->query($course_sql);
                if ($course_result) {
                    $course_row = $course_result->fetch_assoc();
                    if ($course_row) {
                        $course_code = $course_row['course_code'];
                        $course_name = $course_row['course_name'];
                        $credit_hours = $course_row['credit_hours'];
                    }
                }
            }
            
            // Get semester name
            $sem_name = 'Semester ' . ($row['semester_id'] ?? 'N/A');
            if ($row['semester_id'] > 0) {
                $sem_sql = "SELECT semester_name FROM semesters WHERE semester_id = " . intval($row['semester_id']);
                $sem_result = $conn->query($sem_sql);
                if ($sem_result) {
                    $sem_row = $sem_result->fetch_assoc();
                    if ($sem_row) {
                        $sem_name = $sem_row['semester_name'];
                    }
                }
            }
            
            $report_data[] = [
                'student_id' => $student_id,
                'student_name' => $student['full_name'] ?? 'Unknown',
                'roll_no' => $student['roll_no'] ?? 'N/A',
                'program_name' => $student['program_name'] ?? 'N/A',
                'semester_name' => $sem_name,
                'course_code' => $course_code,
                'course_name' => $course_name,
                'credit_hours' => $credit_hours,
                'total_amount' => floatval($row['total_amount'] ?? 0),
                'paid_amount' => floatval($row['paid_amount'] ?? 0),
                'remaining' => floatval($row['remaining'] ?? 0),
                'status' => $row['status'] ?? 'Unpaid',
                'due_date' => $row['due_date'] ?? null
            ];
        }
    } else {
        $error_message = "Query error: " . $conn->error;
    }
    
} elseif ($has_student_fee) {
    // Use student_fee table
    $sql = "SELECT 
                student_id,
                total_amount,
                paid_amount,
                remaining_amount as remaining,
                status,
                due_date,
                semester_id
            FROM student_fee 
            WHERE 1=1";
    
    if ($semester_filter > 0) {
        $sql .= " AND semester_id = " . intval($semester_filter);
    }
    if (!empty($status_filter)) {
        $sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    
    $sql .= " ORDER BY student_id";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $student_id = $row['student_id'];
            
            $student_sql = "SELECT s.full_name, s.roll_no, s.program_id, p.program_name 
                            FROM students s
                            LEFT JOIN programs p ON s.program_id = p.program_id
                            WHERE s.student_id = " . intval($student_id);
            $student_result = $conn->query($student_sql);
            $student = $student_result ? $student_result->fetch_assoc() : null;
            
            if ($program_filter > 0 && ($student['program_id'] ?? 0) != $program_filter) {
                continue;
            }
            
            $sem_name = 'Semester ' . ($row['semester_id'] ?? 'N/A');
            if ($row['semester_id'] > 0) {
                $sem_sql = "SELECT semester_name FROM semesters WHERE semester_id = " . intval($row['semester_id']);
                $sem_result = $conn->query($sem_sql);
                if ($sem_result) {
                    $sem_row = $sem_result->fetch_assoc();
                    if ($sem_row) {
                        $sem_name = $sem_row['semester_name'];
                    }
                }
            }
            
            $report_data[] = [
                'student_id' => $student_id,
                'student_name' => $student['full_name'] ?? 'Unknown',
                'roll_no' => $student['roll_no'] ?? 'N/A',
                'program_name' => $student['program_name'] ?? 'N/A',
                'semester_name' => $sem_name,
                'course_code' => 'N/A',
                'course_name' => 'Student Fee',
                'credit_hours' => 0,
                'total_amount' => floatval($row['total_amount'] ?? 0),
                'paid_amount' => floatval($row['paid_amount'] ?? 0),
                'remaining' => floatval($row['remaining'] ?? 0),
                'status' => $row['status'] ?? 'Unpaid',
                'due_date' => $row['due_date'] ?? null
            ];
        }
    } else {
        $error_message = "Query error: " . $conn->error;
    }
    
} else {
    $error_message = "No fee table found! Please check your database.";
}

// Calculate summary
$total_fees = array_sum(array_column($report_data, 'total_amount'));
$total_paid = array_sum(array_column($report_data, 'paid_amount'));
$total_remaining = array_sum(array_column($report_data, 'remaining'));
$paid_count = count(array_filter($report_data, function($r) { return $r['status'] == 'Paid'; }));
$unpaid_count = count(array_filter($report_data, function($r) { return $r['status'] == 'Unpaid'; }));
$partial_count = count(array_filter($report_data, function($r) { return $r['status'] == 'Partially Paid'; }));

// ============================================
// HEADER
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Fee Reports';
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
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .stats-total .stats-number { color: #2c3e50; }
    .stats-paid .stats-number { color: #27ae60; }
    .stats-due .stats-number { color: #e74c3c; }
    .stats-count .stats-number { color: #3498db; }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.Paid {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Unpaid {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.Partially {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-badge.Overdue {
        background: #f8d7da;
        color: #721c24;
    }
    
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
    }
</style>

<div class="report-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-file-alt"></i> Fee Reports</h4>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Program</label>
                    <select name="program" class="form-select">
                        <option value="0">All Programs</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= $program['program_id'] ?>" 
                                <?= $program_filter == $program['program_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($program['program_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?= $semester['semester_id'] ?>" 
                                <?= $semester_filter == $semester['semester_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($semester['semester_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Paid" <?= $status_filter == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Unpaid" <?= $status_filter == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="Partially Paid" <?= $status_filter == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="Overdue" <?= $status_filter == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-file-alt"></i> Generate
                        </button>
                        <a href="report.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <div class="stats-number">Rs. <?= number_format($total_fees, 0) ?></div>
                    <div class="stats-label">Total Fees</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-paid">
                    <div class="stats-number">Rs. <?= number_format($total_paid, 0) ?></div>
                    <div class="stats-label">Total Paid</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-due">
                    <div class="stats-number">Rs. <?= number_format($total_remaining, 0) ?></div>
                    <div class="stats-label">Total Remaining</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-count">
                    <div class="stats-number"><?= count($report_data) ?></div>
                    <div class="stats-label">Total Records</div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card">
            <div class="card-header">
                <h5>Fee Details</h5>
                <small class="text-muted">
                    Paid: <?= $paid_count ?> | Unpaid: <?= $unpaid_count ?> | Partial: <?= $partial_count ?>
                </small>
            </div>
            <div class="card-body">
                <?php if (!empty($report_data)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Semester</th>
                                    <th>Total Fee</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($report_data as $report): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <?= htmlspecialchars($report['student_name'] ?? 'N/A') ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($report['roll_no'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($report['program_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($report['semester_name'] ?? 'N/A') ?></td>
                                        <td class="text-end">Rs. <?= number_format($report['total_amount'], 2) ?></td>
                                        <td class="text-end text-success">Rs. <?= number_format($report['paid_amount'], 2) ?></td>
                                        <td class="text-end text-danger">Rs. <?= number_format($report['remaining'], 2) ?></td>
                                        <td><?= $report['due_date'] ? date('d M Y', strtotime($report['due_date'])) : 'N/A' ?></td>
                                        <td>
                                            <span class="status-badge <?= $report['status'] ?>">
                                                <?= $report['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th class="text-end">Rs. <?= number_format($total_fees, 2) ?></th>
                                    <th class="text-end">Rs. <?= number_format($total_paid, 2) ?></th>
                                    <th class="text-end">Rs. <?= number_format($total_remaining, 2) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <p>No fee records found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>