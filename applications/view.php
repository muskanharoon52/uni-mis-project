<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid application ID");
    exit;
}

// Fetch application details
$sql = "SELECT 
            a.*,
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
        WHERE a.application_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application) {
    header("Location: index.php?error=Application not found");
    exit;
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Application Details';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .view-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .view-container {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .detail-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f2f5;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #2c3e50;
        width: 150px;
        flex-shrink: 0;
    }
    
    .detail-value {
        color: #555;
        flex: 1;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
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
    
    .remarks-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        margin-top: 10px;
    }
    
    @media (max-width: 768px) {
        .view-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .view-container {
            padding: 20px;
        }
        
        .detail-row {
            flex-direction: column;
            padding: 10px 0;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>

<div class="view-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-eye"></i> Application Details</h4>
            <div>
                <?php if ($application['status'] == 'Pending'): ?>
                    <?php if (in_array($_SESSION['role_id'] ?? 0, [1, 2])): ?>
                        <a href="review.php?id=<?= $id ?>" class="btn btn-warning">
                            <i class="fas fa-check-double"></i> Review
                        </a>
                    <?php endif; ?>
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="view-container">
            <div class="detail-row">
                <span class="detail-label">Student</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($application['student_name'] ?? 'N/A') ?></strong>
                    <br>
                    <small class="text-muted"><?= htmlspecialchars($application['student_id']) ?></small>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Roll No</span>
                <span class="detail-value"><?= htmlspecialchars($application['roll_no'] ?? 'N/A') ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Program</span>
                <span class="detail-value"><?= htmlspecialchars($application['program_name'] ?? 'N/A') ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Contact</span>
                <span class="detail-value">
                    <?= htmlspecialchars($application['student_email'] ?? 'N/A') ?>
                    <br>
                    <?= htmlspecialchars($application['student_phone'] ?? 'N/A') ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Application Type</span>
                <span class="detail-value">
                    <span class="badge bg-secondary"><?= htmlspecialchars($application['application_type']) ?></span>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Subject</span>
                <span class="detail-value"><?= htmlspecialchars($application['subject']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Description</span>
                <span class="detail-value"><?= nl2br(htmlspecialchars($application['description'])) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Attachment</span>
                <span class="detail-value">
                    <?php if ($application['attachment']): ?>
                        <a href="../<?= $application['attachment'] ?>" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-file"></i> Download Attachment
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No attachment</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="status-badge <?= $application['status'] ?>">
                        <?= $application['status'] ?>
                    </span>
                </span>
            </div>
            
            <?php if ($application['status'] != 'Pending'): ?>
                <div class="detail-row">
                    <span class="detail-label">Reviewed By</span>
                    <span class="detail-value"><?= htmlspecialchars($application['reviewer_name'] ?? 'N/A') ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Review Date</span>
                    <span class="detail-value"><?= date('d M Y h:i A', strtotime($application['review_date'])) ?></span>
                </div>
                
                <?php if ($application['remarks']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Remarks</span>
                        <span class="detail-value">
                            <div class="remarks-box">
                                <?= nl2br(htmlspecialchars($application['remarks'])) ?>
                            </div>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>