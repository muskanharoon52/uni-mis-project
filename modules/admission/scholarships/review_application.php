<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Review Scholarship Application';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT sa.*, s.student_name, s.student_id, sch.scholarship_name, sch.min_marks_percentage
    FROM admission_scholarship_applications sa
    LEFT JOIN admission_students s ON sa.student_id = s.id
    LEFT JOIN admission_scholarships sch ON sa.scholarship_id = sch.id
    WHERE sa.id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app || $app['status'] != 'pending') {
    setFlash('error', 'Application cannot be reviewed');
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'];
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        if ($status == 'approved') {
            $min = $app['min_marks_percentage'] ?? 0;
            if ($app['percentage'] < $min) {
                setFlash('error', 'Student does not meet minimum percentage requirement: ' . $min . '%');
                header('Location: review_application.php?id=' . $id);
                exit();
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE admission_scholarship_applications 
            SET status = ?, remarks = ?, reviewed_by = ?, review_date = NOW() 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$status, $remarks, $_SESSION['user_id'], $id])) {
            setFlash('success', 'Scholarship application reviewed! Status: ' . ucfirst($status));
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Review Scholarship Application</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Application Details</h3>
                <p>Verify criteria before issuing decision</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Student Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Student ID</div>
                <div class="detail-value"><strong><?= htmlspecialchars($app['student_id'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Scholarship</div>
                <div class="detail-value"><?= htmlspecialchars($app['scholarship_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Marks Obtained</div>
                <div class="detail-value"><?= $app['marks_obtained'] ?? 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total Marks</div>
                <div class="detail-value"><?= $app['total_marks'] ?? 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Percentage</div>
                <div class="detail-value"><strong><?= number_format($app['percentage'] ?? 0, 2) ?>%</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Minimum Required</div>
                <div class="detail-value"><strong><?= $app['min_marks_percentage'] ?? 0 ?>%</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Eligibility Status</div>
                <div class="detail-value">
                    <?php 
                    $eligible = ($app['percentage'] ?? 0) >= ($app['min_marks_percentage'] ?? 0);
                    if ($eligible): ?>
                        <span class="status-badge Eligible">Eligible</span>
                    <?php else: ?>
                        <span class="status-badge Not Eligible">Not Eligible</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Applied Date</div>
                <div class="detail-value"><?= date('d M Y', strtotime($app['application_date'] ?? 'now')) ?></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Review Decision</h3>
                <p>Record official approval decision</p>
            </div>
        </div>
        <div class="card-content">
            <form method="POST">
                <div class="field" style="margin-bottom:16px;">
                    <label>Decision *</label>
                    <select name="status" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                    <?php if (($app['percentage'] ?? 0) < ($app['min_marks_percentage'] ?? 0)): ?>
                        <div style="margin-top:6px;font-size:.84rem;color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Warning: Student does not meet minimum percentage requirement!</div>
                    <?php endif; ?>
                </div>
                <div class="field" style="margin-bottom:16px;">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" placeholder="Enter optional review notes..."></textarea>
                </div>
                <div class="form-actions" style="border-top:1px solid var(--border);padding-top:16px;">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
