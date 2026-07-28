<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid application ID");
    exit;
}

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

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Application Details';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Application Details</h4>
    <div class="page-header-actions">
        <?php if ($application['status'] == 'Pending'): ?>
            <?php if (in_array($_SESSION['role_id'] ?? 0, [1, 2])): ?>
                <a href="review.php?id=<?= $id ?>" class="btn btn-primary">Review</a>
            <?php endif; ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-ghost">Back</a>
    </div>
</div>

<div class="card">
    <div class="detail-row">
        <span class="detail-label">Student</span>
        <span class="detail-value">
            <strong><?= htmlspecialchars($application['student_name'] ?? 'N/A') ?></strong>
            <div class="muted" style="font-size:12px;"><?= htmlspecialchars($application['student_id']) ?></div>
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
            <div class="muted" style="font-size:12px;"><?= htmlspecialchars($application['student_phone'] ?? 'N/A') ?></div>
        </span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Type</span>
        <span class="detail-value">
            <span class="badge badge-outline"><?= htmlspecialchars($application['application_type']) ?></span>
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
                <a href="../<?= $application['attachment'] ?>" target="_blank" class="btn btn-sm btn-outline">Download Attachment</a>
            <?php else: ?>
                <span class="muted">No attachment</span>
            <?php endif; ?>
        </span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Status</span>
        <span class="detail-value">
            <span class="status-badge <?= $application['status'] ?>"><?= $application['status'] ?></span>
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
            <div class="detail-row" style="border-bottom:none;">
                <span class="detail-label">Remarks</span>
                <span class="detail-value">
                    <div style="padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;color:var(--text);">
                        <?= nl2br(htmlspecialchars($application['remarks'])) ?>
                    </div>
                </span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.detail-row {
    display: flex; gap: 16px; padding: 12px 24px;
    border-bottom: 1px solid var(--border);
    align-items: flex-start;
}
.detail-row:last-child { border-bottom: none; }
.detail-label {
    flex: 0 0 120px; font-size: .78rem; font-weight: 600;
    color: var(--text-secondary); text-transform: uppercase;
    letter-spacing: .04em; padding-top: 2px;
}
.detail-value {
    flex: 1; font-size: .86rem; color: var(--text-strong);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
