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

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Check if user is SSO or Admin
$user_id = $_SESSION['user_id'] ?? 0;
$role_id = $_SESSION['role_id'] ?? 0;
$is_sso = in_array($role_id, [1, 2]); // Admin or SSO

// Check which columns exist in applications table
$appColumns = getTableColumns($conn, 'applications');
$hasSubject = in_array('subject', $appColumns);
$hasDescription = in_array('description', $appColumns);
$hasAttachment = in_array('attachment', $appColumns);
$hasRemarks = in_array('remarks', $appColumns);
$hasReviewedBy = in_array('reviewed_by', $appColumns);
$hasReviewDate = in_array('review_date', $appColumns);
$hasStudentId = in_array('student_id', $appColumns);
$hasApplicationType = in_array('application_type', $appColumns);
$hasStatus = in_array('status', $appColumns);
$hasCreatedAt = in_array('created_at', $appColumns);

// Check which columns exist in students table
$studentColumns = getTableColumns($conn, 'students');
$hasStudentUserId = in_array('user_id', $studentColumns);
$hasStudentRollNo = in_array('roll_no', $studentColumns);
$hasStudentProgramId = in_array('program_id', $studentColumns);
$hasStudentFullName = in_array('full_name', $studentColumns);

// Check which columns exist in users table
$userColumns = getTableColumns($conn, 'users');
$hasUserPhone = in_array('phone', $userColumns);
$hasUserEmail = in_array('email', $userColumns);
$hasUserFullName = in_array('full_name', $userColumns);

// Build SELECT query based on available columns
$selectFields = [
    'a.application_id'
];

if ($hasApplicationType) {
    $selectFields[] = 'a.application_type';
} else {
    $selectFields[] = "'N/A' as application_type";
}

if ($hasSubject) {
    $selectFields[] = 'a.subject';
} else {
    $selectFields[] = "'' as subject";
}

if ($hasDescription) {
    $selectFields[] = 'a.description';
} else {
    $selectFields[] = "'' as description";
}

if ($hasAttachment) {
    $selectFields[] = 'a.attachment';
} else {
    $selectFields[] = "NULL as attachment";
}

if ($hasStatus) {
    $selectFields[] = 'a.status';
} else {
    $selectFields[] = "'Pending' as status";
}

if ($hasRemarks) {
    $selectFields[] = 'a.remarks';
} else {
    $selectFields[] = "NULL as remarks";
}

if ($hasCreatedAt) {
    $selectFields[] = 'a.created_at';
} else {
    $selectFields[] = "NOW() as created_at";
}

if ($hasReviewDate) {
    $selectFields[] = 'a.review_date';
} else {
    $selectFields[] = "NULL as review_date";
}

if ($hasReviewedBy) {
    $selectFields[] = 'a.reviewed_by';
} else {
    $selectFields[] = "NULL as reviewed_by";
}

// Add student fields - check what's available
if ($hasStudentId) {
    $selectFields[] = 'a.student_id';
} else {
    $selectFields[] = "NULL as student_id";
}

if ($hasStudentRollNo) {
    $selectFields[] = 's.roll_no';
} else {
    $selectFields[] = "'N/A' as roll_no";
}

// Check if we can get student name from students table or users table
if ($hasStudentFullName) {
    $selectFields[] = 's.full_name as student_name';
} elseif ($hasUserFullName && $hasStudentUserId) {
    $selectFields[] = 'u.full_name as student_name';
} else {
    $selectFields[] = "'N/A' as student_name";
}

if ($hasUserEmail && $hasStudentUserId) {
    $selectFields[] = 'u.email as student_email';
} else {
    $selectFields[] = "'N/A' as student_email";
}

if ($hasUserPhone && $hasStudentUserId) {
    $selectFields[] = 'u.phone as student_phone';
} else {
    $selectFields[] = "'N/A' as student_phone";
}

// Program name - only if students table has program_id and programs table exists
if ($hasStudentProgramId) {
    $selectFields[] = 'p.program_name';
} else {
    $selectFields[] = "'N/A' as program_name";
}

// Reviewer name
$selectFields[] = 'u2.full_name as reviewer_name';

// Build the SQL query
$sql = "SELECT \n            " . implode(",\n            ", $selectFields);
$sql .= "\n        FROM applications a";

// Join students - only if student_id exists in applications
if ($hasStudentId) {
    $sql .= "\n        LEFT JOIN students s ON a.student_id = s.student_id";
    
    // Join users - only if user_id exists in students
    if ($hasStudentUserId) {
        $sql .= "\n        LEFT JOIN users u ON s.user_id = u.user_id";
    }
    
    // Join programs - only if program_id exists in students
    if ($hasStudentProgramId) {
        $sql .= "\n        LEFT JOIN programs p ON s.program_id = p.program_id";
    }
} else {
    // If no student_id, just add empty joins
    $sql .= "\n        LEFT JOIN students s ON 1=0";
    $sql .= "\n        LEFT JOIN users u ON 1=0";
    $sql .= "\n        LEFT JOIN programs p ON 1=0";
}

// Join users for reviewer
if ($hasReviewedBy) {
    $sql .= "\n        LEFT JOIN users u2 ON a.reviewed_by = u2.user_id";
} else {
    $sql .= "\n        LEFT JOIN users u2 ON 1=0";
}

$sql .= "\n        WHERE 1=1";

$params = [];
$types = "";

// Add search filter - adjust based on available columns
if (!empty($search)) {
    $searchConditions = [];
    
    if ($hasStudentId) {
        $searchConditions[] = "a.student_id LIKE ?";
    }
    
    if ($hasStudentRollNo) {
        $searchConditions[] = "s.roll_no LIKE ?";
    }
    
    if ($hasSubject) {
        $searchConditions[] = "a.subject LIKE ?";
    }
    
    if ($hasUserFullName && $hasStudentUserId) {
        $searchConditions[] = "u.full_name LIKE ?";
    }
    
    if ($hasUserEmail && $hasStudentUserId) {
        $searchConditions[] = "u.email LIKE ?";
    }
    
    // If no search conditions available, search by application_id
    if (empty($searchConditions)) {
        $searchConditions[] = "a.application_id LIKE ?";
    }
    
    $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $searchParam = "%$search%";
    
    // Add params for each condition
    foreach ($searchConditions as $condition) {
        $params[] = $searchParam;
        $types .= "s";
    }
}

// Add type filter
if (!empty($type_filter) && $hasApplicationType) {
    $sql .= " AND a.application_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add status filter
if (!empty($status_filter) && $hasStatus) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add date range filter
if (!empty($date_from) && $hasCreatedAt) {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to) && $hasCreatedAt) {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY a.application_id DESC";

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
$applications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get stats - simplified query
$stats_query = "SELECT 
                    COUNT(*) as total";
if ($hasStatus) {
    $stats_query .= ",\n                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected";
} else {
    $stats_query .= ",\n                    0 as pending,
                    0 as approved,
                    0 as rejected";
}
$stats_query .= "\n                FROM applications";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Applications Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .applications-content {
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
    
    .stats-total .stats-number { color: #2c3e50; }
    .stats-pending .stats-number { color: #f39c12; }
    .stats-approved .stats-number { color: #27ae60; }
    .stats-rejected .stats-number { color: #e74c3c; }
    
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
    
    .status-badge.Pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-badge.Approved {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Rejected {
        background: #f8d7da;
        color: #721c24;
    }
    
    .type-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        background: #e9ecef;
        color: #495057;
    }
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .btn-submit {
        border-radius: 20px;
        padding: 8px 20px;
    }
    
    .attachment-link {
        color: #3498db;
        text-decoration: none;
    }
    
    .attachment-link:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        .applications-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="applications-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-file-alt"></i> Applications Management</h4>
            <div>
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4): ?>
                    <!-- Student View -->
                    <a href="my_applications.php" class="btn btn-info me-2">
                        <i class="fas fa-list"></i> My Applications
                    </a>
                    <a href="submit.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> New Application
                    </a>
                <?php else: ?>
                    <!-- SSO/Admin View -->
                    <a href="submit.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> New Application
                    </a>
                <?php endif; ?>
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
                    <div class="stats-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stats-label">Total Applications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-pending">
                    <div class="stats-number"><?= $stats['pending'] ?? 0 ?></div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-approved">
                    <div class="stats-number"><?= $stats['approved'] ?? 0 ?></div>
                    <div class="stats-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-rejected">
                    <div class="stats-number"><?= $stats['rejected'] ?? 0 ?></div>
                    <div class="stats-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search student or subject..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Leave" <?= $type_filter == 'Leave' ? 'selected' : '' ?>>Leave</option>
                        <option value="Bonafide Certificate" <?= $type_filter == 'Bonafide Certificate' ? 'selected' : '' ?>>Bonafide Certificate</option>
                        <option value="Transcript" <?= $type_filter == 'Transcript' ? 'selected' : '' ?>>Transcript</option>
                        <option value="ID Card" <?= $type_filter == 'ID Card' ? 'selected' : '' ?>>ID Card</option>
                        <option value="Semester Freeze" <?= $type_filter == 'Semester Freeze' ? 'selected' : '' ?>>Semester Freeze</option>
                        <option value="Course Withdrawal" <?= $type_filter == 'Course Withdrawal' ? 'selected' : '' ?>>Course Withdrawal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $status_filter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To" value="<?= $date_to ?>">
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
            <div class="card-header">
                <h5>Applications (<?= count($applications) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($applications)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($applications as $app): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small class="text-muted">ID: <?= htmlspecialchars($app['student_id'] ?? 'N/A') ?></small>
                                            <?php if (!empty($app['roll_no']) && $app['roll_no'] != 'N/A'): ?>
                                                <br>
                                                <small class="text-muted">Roll: <?= htmlspecialchars($app['roll_no']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($app['student_phone']) && $app['student_phone'] != 'N/A'): ?>
                                                <br>
                                                <small class="text-muted"><i class="fas fa-phone"></i> <?= htmlspecialchars($app['student_phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="type-badge"><?= htmlspecialchars($app['application_type'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($app['subject'] ?? 'N/A') ?>
                                            <?php if (!empty($app['description'])): ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars(substr($app['description'], 0, 50)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $app['status'] ?? 'Pending' ?>">
                                                <?= $app['status'] ?? 'Pending' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= isset($app['created_at']) && $app['created_at'] != 'NULL' && !empty($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A' ?>
                                            <?php if (isset($app['created_at']) && $app['created_at'] != 'NULL' && !empty($app['created_at'])): ?>
                                                <br>
                                                <small class="text-muted"><?= date('h:i A', strtotime($app['created_at'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-actions">
                                            <a href="view.php?id=<?= $app['application_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (($app['status'] ?? '') == 'Pending' && in_array($role_id, [1, 2])): ?>
                                                <a href="review.php?id=<?= $app['application_id'] ?>" 
                                                   class="btn btn-warning btn-sm" title="Review">
                                                    <i class="fas fa-check-double"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (($app['status'] ?? '') == 'Pending'): ?>
                                                <a href="edit.php?id=<?= $app['application_id'] ?>" 
                                                   class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $app['application_id'] ?>" 
                                                   class="btn btn-danger btn-sm" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this application?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5>No Applications Found</h5>
                        <p class="text-muted">No applications have been submitted yet.</p>
                        <a href="submit.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Submit Application
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>