<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get student ID for logged in user
$student_id = '';
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id > 0) {
    $student_query = "SELECT student_id FROM students WHERE user_id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $user_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_row = $student_result->fetch_assoc();
    $student_id = $student_row['student_id'] ?? '';
    $student_stmt->close();
}

if (empty($student_id)) {
    header("Location: index.php?error=Student record not found");
    exit;
}

// Fetch student's applications
$sql = "SELECT 
            a.*,
            u.full_name as reviewer_name
        FROM applications a
        LEFT JOIN users u ON a.reviewed_by = u.user_id
        WHERE a.student_id = ?
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
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
                FROM applications
                WHERE student_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $student_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$stats_stmt->close();

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'My Applications';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .my-apps-content {
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
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stats-total .stats-number { color: #2c3e50; }
    .stats-pending .stats-number { color: #f39c12; }
    .stats-approved .stats-number { color: #27ae60; }
    .stats-rejected .stats-number { color: #e74c3c; }
    
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
    
    @media (max-width: 768px) {
        .my-apps-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="my-apps-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-file-alt"></i> My Applications</h4>
            <a href="submit.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> New Application
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stats-label">Total</div>
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

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h5>My Applications (<?= count($applications) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($applications)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Reviewer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($applications as $app): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
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
                                            <?php if ($app['status'] == 'Rejected' && $app['remarks']): ?>
                                                <br>
                                                <small class="text-muted">Reason: <?= htmlspecialchars($app['remarks']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('d M Y', strtotime($app['created_at'])) ?>
                                            <br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($app['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($app['reviewer_name'] ?? 'Pending Review') ?>
                                        </td>
                                        <td class="table-actions">
                                            <a href="view.php?id=<?= $app['application_id'] ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
                        <p class="text-muted">You haven't submitted any applications yet.</p>
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