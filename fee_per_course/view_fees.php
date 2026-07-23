<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Set collation for connection
$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = utf8mb4_unicode_ci");

// Get filter parameters
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Fetch semesters for dropdown
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");

// Check if table exists first
$table_check = $conn->query("SHOW TABLES LIKE 'student_course_fees'");
if ($table_check->num_rows == 0) {
    // Table doesn't exist - show message
    $error_message = "Table 'student_course_fees' doesn't exist. Please create it first.";
    $reports = [];
    $total_fees = $total_paid = $total_due = $paid_count = $unpaid_count = $partial_count = 0;
} else {
    // Build report query with COLLATE to fix collation mismatch
    $sql = "SELECT 
                scf.id,
                s.student_id,
                s.roll_no,
                u.full_name as student_name,
                c.course_code,
                c.course_name,
                c.credit_hours,
                scf.fee_amount,
                scf.paid_amount,
                (scf.fee_amount - scf.paid_amount) as remaining,
                scf.status,
                scf.due_date,
                scf.payment_date,
                sem.semester_name,
                scf.created_at
            FROM student_course_fees scf
            LEFT JOIN students s ON scf.student_id COLLATE utf8mb4_unicode_ci = s.student_id COLLATE utf8mb4_unicode_ci
            LEFT JOIN users u ON s.user_id = u.user_id
            LEFT JOIN courses c ON scf.course_id = c.course_id
            LEFT JOIN semesters sem ON scf.semester_id = sem.semester_id
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($semester_filter > 0) {
        $sql .= " AND scf.semester_id = ?";
        $params[] = $semester_filter;
        $types .= "i";
    }

    if (!empty($status_filter)) {
        $sql .= " AND scf.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if (!empty($date_from)) {
        $sql .= " AND DATE(scf.created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }

    if (!empty($date_to)) {
        $sql .= " AND DATE(scf.created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }

    $sql .= " ORDER BY scf.created_at DESC";

    // Prepare and execute with error checking
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        // Query preparation failed
        $error_message = "SQL Error: " . $conn->error;
        $reports = [];
        $total_fees = $total_paid = $total_due = $paid_count = $unpaid_count = $partial_count = 0;
    } else {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $reports = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $error_message = "Execution Error: " . $stmt->error;
            $reports = [];
        }
        $stmt->close();
    }

    // Summary statistics
    $total_fees = 0;
    $total_paid = 0;
    $total_due = 0;
    $paid_count = 0;
    $unpaid_count = 0;
    $partial_count = 0;

    foreach ($reports as $report) {
        $total_fees += $report['fee_amount'];
        $total_paid += $report['paid_amount'];
        $total_due += ($report['fee_amount'] - $report['paid_amount']);
        
        if ($report['status'] == 'Paid') $paid_count++;
        elseif ($report['status'] == 'Unpaid') $unpaid_count++;
        elseif ($report['status'] == 'Partially Paid') $partial_count++;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM student_course_fees WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $id);
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = 'Fee record deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting record: ' . $delete_stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $delete_stmt->close();
    } else {
        $_SESSION['message'] = 'Error: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: view_fees.php');
    exit();
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'View Fees';
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
        transition: transform 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-number {
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .stats-total .stats-number { color: #2c3e50; }
    .stats-paid .stats-number { color: #27ae60; }
    .stats-due .stats-number { color: #e74c3c; }
    .stats-count .stats-number { color: #3498db; }
    
    .stats-label {
        color: #7f8c8d;
        font-size: 14px;
        margin-top: 5px;
        text-transform: uppercase;
        font-weight: 500;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    .status-badge.Paid {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Unpaid {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.Partially\ Paid {
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
    
    .action-btns .btn {
        padding: 4px 10px;
        font-size: 12px;
        border-radius: 20px;
        margin: 0 2px;
    }
    
    .table th {
        background: #2c3e50;
        color: white;
        border: none;
        padding: 12px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 14px;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 60px;
        color: #bdc3c7;
        margin-bottom: 15px;
    }
    
    .empty-state h5 {
        color: #2c3e50;
        font-weight: 600;
    }
    
    .error-box {
        background: #f8d7da;
        color: #721c24;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #e74c3c;
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
        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<div class="report-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4><i class="fas fa-money-bill-wave text-primary"></i> Fee Management</h4>
                <p class="text-muted mb-0">View and manage all student fee records</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary btn-print">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="add_fee.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Fee
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Display Session Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Show Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="error-box mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php if ($semesters && $semesters->num_rows > 0): ?>
                            <?php while($row = $semesters->fetch_assoc()): ?>
                                <option value="<?= $row['semester_id'] ?>" 
                                    <?= $semester_filter == $row['semester_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['semester_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Paid" <?= $status_filter == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Unpaid" <?= $status_filter == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="Partially Paid" <?= $status_filter == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="Overdue" <?= $status_filter == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="view_fees.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card stats-total">
                    <div class="stats-number">Rs. <?= number_format($total_fees ?? 0, 0) ?></div>
                    <div class="stats-label">Total Fees</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card stats-paid">
                    <div class="stats-number">Rs. <?= number_format($total_paid ?? 0, 0) ?></div>
                    <div class="stats-label">Total Paid</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card stats-due">
                    <div class="stats-number">Rs. <?= number_format($total_due ?? 0, 0) ?></div>
                    <div class="stats-label">Total Due</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card stats-count">
                    <div class="stats-number"><?= count($reports ?? []) ?></div>
                    <div class="stats-label">Total Records</div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Fee Records</h5>
                <span class="text-muted small">
                    <i class="fas fa-check-circle text-success"></i> Paid: <?= $paid_count ?? 0 ?> | 
                    <i class="fas fa-times-circle text-danger"></i> Unpaid: <?= $unpaid_count ?? 0 ?> | 
                    <i class="fas fa-clock text-warning"></i> Partial: <?= $partial_count ?? 0 ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (!empty($reports)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="feeTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Total Fee</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($reports as $report): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($report['student_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($report['roll_no'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($report['course_code'] ?? 'N/A') ?>
                                            <br>
                                            <small><?= htmlspecialchars($report['course_name'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($report['semester_name'] ?? 'N/A') ?></td>
                                        <td class="text-end fw-bold">Rs. <?= number_format($report['fee_amount'] ?? 0, 2) ?></td>
                                        <td class="text-end text-success">Rs. <?= number_format($report['paid_amount'] ?? 0, 2) ?></td>
                                        <td class="text-end text-danger">Rs. <?= number_format($report['remaining'] ?? 0, 2) ?></td>
                                        <td>
                                            <span class="status-badge <?= $report['status'] ?? 'Unpaid' ?>">
                                                <?= $report['status'] ?? 'Unpaid' ?>
                                            </span>
                                        </td>
                                        <td class="no-print">
                                            <div class="action-btns d-flex">
                                                <a href="view_fee.php?id=<?= $report['id'] ?? $count ?>" 
                                                   class="btn btn-primary btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_fee.php?id=<?= $report['id'] ?? $count ?>" 
                                                   class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_fees.php?delete=<?= $report['id'] ?? $count ?>" 
                                                   class="btn btn-danger btn-sm" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th class="text-end">Rs. <?= number_format($total_fees ?? 0, 2) ?></th>
                                    <th class="text-end">Rs. <?= number_format($total_paid ?? 0, 2) ?></th>
                                    <th class="text-end">Rs. <?= number_format($total_due ?? 0, 2) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No Fee Records Found</h5>
                        <p class="text-muted">No fee records match your filter criteria.</p>
                        <a href="add_fee.php" class="btn btn-success mt-2">
                            <i class="fas fa-plus me-2"></i>Add First Record
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer Info -->
        <div class="mt-3 text-center text-muted small">
            <i class="fas fa-database me-1"></i> 
            Showing <?= count($reports ?? []) ?> records | 
            Last updated: <?= date('d M Y, h:i A') ?>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Auto-hide alerts after 5 seconds -->
<script>
setTimeout(function() {
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        let bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>