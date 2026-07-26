<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Check if user is SSO or Admin
$role_id = $_SESSION['role_id'] ?? 0;
if (!in_array($role_id, [1, 2])) {
    header("Location: index.php?error=Access denied. Only SSO officers can review applications.");
    exit;
}

$conn = getConnection();
$error = '';
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
            p.program_name
        FROM applications a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN programs p ON s.program_id = p.program_id
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

if ($application['status'] != 'Pending') {
    header("Location: index.php?error=This application has already been reviewed");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $reviewed_by = $_SESSION['user_id'] ?? 0;

    if (empty($action)) {
        $error = "Please select an action (Approve or Reject)";
    } elseif (empty($remarks)) {
        $error = "Please provide remarks";
    } else {
        $status = ($action == 'approve') ? 'Approved' : 'Rejected';
        
        $update_query = "UPDATE applications SET 
                         status = ?, 
                         remarks = ?, 
                         reviewed_by = ?, 
                         review_date = NOW() 
                         WHERE application_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssii", $status, $remarks, $reviewed_by, $id);
        
        if ($update_stmt->execute()) {
            header("Location: index.php?success=Application {$status} successfully!");
            exit;
        } else {
            $error = "Error updating application: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Review Application';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .review-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .review-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .app-details {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .detail-row {
        display: flex;
        padding: 10px 0;
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
    
    .review-form {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    
    .btn-review {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .btn-approve {
        background: #27ae60;
        border-color: #27ae60;
        color: white;
    }
    
    .btn-approve:hover {
        background: #219a52;
        border-color: #219a52;
        color: white;
    }
    
    .btn-reject {
        background: #e74c3c;
        border-color: #e74c3c;
        color: white;
    }
    
    .btn-reject:hover {
        background: #c0392b;
        border-color: #c0392b;
        color: white;
    }
    
    @media (max-width: 768px) {
        .review-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .detail-row {
            flex-direction: column;
            padding: 8px 0;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 3px;
        }
    }
</style>

<div class="review-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-check-double"></i> Review Application</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="review-container">
            <!-- Application Details -->
            <div class="app-details">
                <h5 class="mb-3">Application Details</h5>
                
                <div class="detail-row">
                    <span class="detail-label">Student</span>
                    <span class="detail-value">
                        <strong><?= htmlspecialchars($application['student_name']) ?></strong>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($application['student_id']) ?></small>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Program</span>
                    <span class="detail-value"><?= htmlspecialchars($application['program_name'] ?? 'N/A') ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Type</span>
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
                                <i class="fas fa-file"></i> View Attachment
                            </a>
                        <?php else: ?>
                            <span class="text-muted">No attachment</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span class="detail-value"><?= date('d M Y h:i A', strtotime($application['created_at'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge <?= $application['status'] ?>">
                            <?= $application['status'] ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Review Form -->
            <div class="review-form">
                <h5 class="mb-3">Review Decision</h5>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Decision <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" value="approve" id="approve">
                                <label class="form-check-label text-success" for="approve">
                                    <i class="fas fa-check-circle"></i> Approve
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" value="reject" id="reject">
                                <label class="form-check-label text-danger" for="reject">
                                    <i class="fas fa-times-circle"></i> Reject
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="4" 
                                  placeholder="Provide detailed remarks for your decision..." required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-approve btn-review">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="submit" class="btn btn-reject btn-review" onclick="this.form.action.value='reject'">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

<script>
    // Auto-submit based on which button is clicked
    document.querySelector('.btn-approve').addEventListener('click', function(e) {
        document.querySelector('input[name="action"][value="approve"]').checked = true;
    });
    
    document.querySelector('.btn-reject').addEventListener('click', function(e) {
        document.querySelector('input[name="action"][value="reject"]').checked = true;
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>