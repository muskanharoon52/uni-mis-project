<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'View Application';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT a.*, 
           d.department_name,
           s.session_name,
           sem.semester_name,
           u.full_name as reviewer_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    LEFT JOIN sessions s ON a.session_id = s.session_id 
    LEFT JOIN semesters sem ON a.applied_semester_id = sem.semester_id 
    LEFT JOIN users u ON a.reviewed_by = u.user_id 
    WHERE a.application_id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    setFlash('error', 'Application not found');
    header('Location: index.php');
    exit();
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-file-alt"></i> Application Details</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
        <?php if(in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])): ?>
        <a href="review.php?id=<?= $app['application_id'] ?>" class="btn btn-primary"><i class="fas fa-check"></i> Review</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Personal Information</h3>
            <p>Applicant's basic profile details</p>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Application No</div>
                <div class="detail-value"><strong><?= htmlspecialchars($app['temp_application_no'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Full Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Father Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['father_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">CNIC/B-Form</div>
                <div class="detail-value"><?= htmlspecialchars($app['cnic_or_bform'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date of Birth</div>
                <div class="detail-value"><?= isset($app['dob']) ? date('d M Y', strtotime($app['dob'])) : 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Gender</div>
                <div class="detail-value"><?= htmlspecialchars($app['gender'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?= htmlspecialchars($app['email'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Contact No</div>
                <div class="detail-value"><?= htmlspecialchars($app['contact_no'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Address</div>
                <div class="detail-value"><?= htmlspecialchars($app['address'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Academic Information</h3>
            <p>Program and processing status</p>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Department</div>
                <div class="detail-value"><?= htmlspecialchars($app['department_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Session</div>
                <div class="detail-value"><?= htmlspecialchars($app['session_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Semester</div>
                <div class="detail-value"><?= htmlspecialchars($app['semester_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Submitted Date</div>
                <div class="detail-value"><?= isset($app['submitted_at']) ? date('d M Y, h:i A', strtotime($app['submitted_at'])) : 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php $status = $app['application_status'] ?? 'Submitted'; ?>
                    <span class="status-badge <?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></span>
                </div>
            </div>
            <?php if(!empty($app['reviewer_name'])): ?>
            <div class="detail-row">
                <div class="detail-label">Reviewed By</div>
                <div class="detail-value"><?= htmlspecialchars($app['reviewer_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Review Date</div>
                <div class="detail-value"><?= isset($app['reviewed_at']) ? date('d M Y, h:i A', strtotime($app['reviewed_at'])) : 'N/A' ?></div>
            </div>
            <?php endif; ?>
            <?php if(!empty($app['rejection_reason'])): ?>
            <div class="detail-row">
                <div class="detail-label">Rejection Reason</div>
                <div class="detail-value" style="color:var(--danger);"><?= htmlspecialchars($app['rejection_reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
