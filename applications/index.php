<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

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

// Fetch applications
$sql = "SELECT 
            a.application_id,
            a.application_type,
            a.subject,
            a.description,
            a.attachment,
            a.status,
            a.remarks,
            a.created_at,
            a.review_date,
            s.student_id,
            s.roll_no,
            u.full_name as student_name,
            u.email as student_email,
            u.phone as student_phone,
            p.program_name,
            u2.full_name as reviewer_name
        FROM applications a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN users u2 ON a.reviewed_by = u2.user_id
        WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR s.student_id LIKE ? OR a.subject LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

// Add type filter
if (!empty($type_filter)) {
    $sql .= " AND a.application_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add status filter
if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add date range filter
if (!empty($date_from)) {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY a.created_at DESC";

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

// Get stats
$stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM applications";
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
                                            <small class="text-muted"><?= htmlspecialchars($app['student_id']) ?></small>
                                        </td>
                                        <td>
                                            <span class="type-badge"><?= htmlspecialchars($app['application_type']) ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($app['subject']) ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($app['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $app['status'] ?>">
                                                <?= $app['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d M Y', strtotime($app['created_at'])) ?>
                                            <br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($app['created_at'])) ?></small>
                                        </td>
                                        <td class="table-actions">
                                            <a href="view.php?id=<?= $app['application_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($app['status'] == 'Pending' && in_array($role_id, [1, 2])): ?>
                                                <a href="review.php?id=<?= $app['application_id'] ?>" 
                                                   class="btn btn-warning btn-sm" title="Review">
                                                    <i class="fas fa-check-double"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($app['status'] == 'Pending'): ?>
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