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
<div class="page-header d-flex justify-content-between align-items-center">
    <h5><i class="fas fa-file-alt"></i> Application Details</h5>
    <div>
        <a href="index.php" class="btn btn-secondary">Back</a>
        <?php if(in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])): ?>
        <a href="review.php?id=<?= $app['application_id'] ?>" class="btn btn-primary">Review</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white"><h6 class="mb-0">Personal Information</h6></div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Application No</th><td><strong><?= $app['temp_application_no'] ?? 'N/A' ?></strong></td></tr>
                    <tr><th>Full Name</th><td><?= $app['full_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Father Name</th><td><?= $app['father_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>CNIC/B-Form</th><td><?= $app['cnic_or_bform'] ?? 'N/A' ?></td></tr>
                    <tr><th>Date of Birth</th><td><?= isset($app['dob']) ? date('d M Y', strtotime($app['dob'])) : 'N/A' ?></td></tr>
                    <tr><th>Gender</th><td><?= $app['gender'] ?? 'N/A' ?></td></tr>
                    <tr><th>Email</th><td><?= $app['email'] ?? 'N/A' ?></td></tr>
                    <tr><th>Contact No</th><td><?= $app['contact_no'] ?? 'N/A' ?></td></tr>
                    <tr><th>Address</th><td><?= $app['address'] ?? 'N/A' ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white"><h6 class="mb-0">Academic Information</h6></div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Department</th><td><?= $app['department_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Session</th><td><?= $app['session_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Semester</th><td><?= $app['semester_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Submitted Date</th><td><?= isset($app['submitted_at']) ? date('d M Y, h:i A', strtotime($app['submitted_at'])) : 'N/A' ?></td></tr>
                    <tr><th>Status</th>
                        <td>
                            <?php 
                            $status = $app['application_status'] ?? 'Submitted';
                            $badge_color = match($status) {
                                'Submitted' => 'warning',
                                'Under Review' => 'info',
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                'Admitted' => 'primary',
                                'Cancelled' => 'secondary',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge_color ?>"><?= $status ?></span>
                        </td>
                    </tr>
                    <?php if($app['reviewer_name']): ?>
                    <tr><th>Reviewed By</th><td><?= $app['reviewer_name'] ?></td></tr>
                    <tr><th>Review Date</th><td><?= isset($app['reviewed_at']) ? date('d M Y, h:i A', strtotime($app['reviewed_at'])) : 'N/A' ?></td></tr>
                    <?php endif; ?>
                    <?php if($app['rejection_reason']): ?>
                    <tr><th>Rejection Reason</th><td class="text-danger"><?= $app['rejection_reason'] ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>