<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch course fees with student counts
$sql = "SELECT 
            cf.fee_id,
            cf.course_id,
            cf.semester_id,
            cf.fee_amount,
            cf.fee_type,
            cf.description,
            cf.is_active,
            cf.created_at,
            c.course_code,
            c.course_name,
            c.credit_hours,
            s.semester_name,
            COUNT(DISTINCT scf.student_id) as total_students,
            COUNT(DISTINCT scf.id) as total_fees,
            SUM(CASE WHEN scf.status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN scf.status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN scf.status = 'Partially Paid' THEN 1 ELSE 0 END) as partial_count,
            SUM(scf.paid_amount) as total_collected
        FROM course_fees cf
        LEFT JOIN courses c ON cf.course_id = c.course_id
        LEFT JOIN semesters s ON cf.semester_id = s.semester_id
        LEFT JOIN student_course_fees scf ON cf.course_id = scf.course_id AND cf.semester_id = scf.semester_id
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

// Add semester filter
if ($semester_filter > 0) {
    $sql .= " AND cf.semester_id = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

$sql .= " GROUP BY cf.fee_id ORDER BY c.course_code";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$course_fees = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get stats
$stats_query = "SELECT 
                    COUNT(DISTINCT cf.fee_id) as total_fees_configured,
                    COUNT(DISTINCT cf.course_id) as total_courses,
                    SUM(scf.fee_amount) as total_amount,
                    SUM(scf.paid_amount) as total_paid,
                    SUM(scf.fee_amount - scf.paid_amount) as total_due
                FROM course_fees cf
                LEFT JOIN student_course_fees scf ON cf.course_id = scf.course_id AND cf.semester_id = scf.semester_id";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_fees_configured' => 0, 
    'total_courses' => 0, 
    'total_amount' => 0, 
    'total_paid' => 0, 
    'total_due' => 0
];

// Fetch semesters for dropdown
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Course Fee Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .fee-content {
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
    .stats-configured .stats-number { color: #3498db; }
    .stats-amount .stats-number { color: #27ae60; }
    .stats-due .stats-number { color: #e74c3c; }
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .fee-amount {
        font-weight: 700;
        color: #27ae60;
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
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .btn-fee {
        border-radius: 20px;
        padding: 8px 20px;
    }
    
    @media (max-width: 768px) {
        .fee-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="fee-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-money-bill-wave"></i> Course Fee Management</h4>
            <div>
                <a href="set_fees.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Set Course Fee
                </a>
                <a href="report.php" class="btn btn-info">
                    <i class="fas fa-file-alt"></i> Reports
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
                <div class="stats-card stats-configured">
                    <div class="stats-number"><?= number_format($stats['total_fees_configured'] ?? 0) ?></div>
                    <div class="stats-label">Fees Configured</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= number_format($stats['total_courses'] ?? 0) ?></div>
                    <div class="stats-label">Total Courses</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-amount">
                    <div class="stats-number">Rs. <?= number_format($stats['total_paid'] ?? 0, 0) ?></div>
                    <div class="stats-label">Total Collected</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-due">
                    <div class="stats-number">Rs. <?= number_format($stats['total_due'] ?? 0, 0) ?></div>
                    <div class="stats-label">Total Due</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by course code or name..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php while($row = $semesters->fetch_assoc()): ?>
                            <option value="<?= $row['semester_id'] ?>" 
                                <?= $semester_filter == $row['semester_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['semester_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h5>Course Fees (<?= count($course_fees) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($course_fees)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="feeTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Credit Hours</th>
                                    <th>Fee Amount</th>
                                    <th>Fee Type</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($course_fees as $fee): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($fee['course_code']) ?></strong>
                                            <br>
                                            <small><?= htmlspecialchars($fee['course_name']) ?></small>
                                        </td>
                                        <td><?= $fee['credit_hours'] ?></td>
                                        <td>
                                            <span class="fee-amount">Rs. <?= number_format($fee['fee_amount'] ?? 0, 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $fee['fee_type'] ?? 'Fixed' ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($fee['semester_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="status-badge <?= ($fee['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>">
                                                <?= ($fee['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $fee['total_students'] ?? 0 ?>
                                            <br>
                                            <small>
                                                Paid: <?= $fee['paid_count'] ?? 0 ?> | 
                                                Unpaid: <?= $fee['unpaid_count'] ?? 0 ?>
                                            </small>
                                        </td>
                                        <td class="table-actions">
                                            <a href="view_fees.php?id=<?= $fee['fee_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="set_fees.php?edit=<?= $fee['fee_id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="assign_fees.php?course=<?= $fee['course_id'] ?>&semester=<?= $fee['semester_id'] ?>" 
                                               class="btn btn-success btn-sm" title="Assign to Students">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                        <h5>No Course Fees Configured</h5>
                        <p class="text-muted">Configure fees for courses to start collecting payments.</p>
                        <a href="set_fees.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Set Course Fee
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>