<?php
require_once '../../config/database.php';
$page_title = 'Review Scholarship Application';
include '../../includes/header.php';

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
        
        // Auto-approve if percentage meets criteria
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
<div class="page-header"><h5><i class="fas fa-check"></i> Review Scholarship Application</h5></div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white"><h6 class="mb-0">Application Details</h6></div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Student</th><td><?= $app['student_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Student ID</th><td><?= $app['student_id'] ?? 'N/A' ?></td></tr>
                    <tr><th>Scholarship</th><td><?= $app['scholarship_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Marks Obtained</th><td><?= $app['marks_obtained'] ?? 'N/A' ?></td></tr>
                    <tr><th>Total Marks</th><td><?= $app['total_marks'] ?? 'N/A' ?></td></tr>
                    <tr><th>Percentage</th><td><strong><?= number_format($app['percentage'] ?? 0, 2) ?>%</strong></td></tr>
                    <tr><th>Minimum Required</th><td><strong><?= $app['min_marks_percentage'] ?? 0 ?>%</strong></td></tr>
                    <tr><th>Eligibility</th>
                        <td>
                            <?php 
                            $eligible = ($app['percentage'] ?? 0) >= ($app['min_marks_percentage'] ?? 0);
                            echo $eligible ? '✅ <span class="text-success">Eligible</span>' : '❌ <span class="text-danger">Not Eligible</span>';
                            ?>
                        </td>
                    </tr>
                    <tr><th>Applied Date</th><td><?= date('d M Y', strtotime($app['application_date'] ?? 'now')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning"><h6 class="mb-0">Review Decision</h6></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Decision *</label>
                        <select name="status" class="form-select" required>
                            <option value="approved">✅ Approve</option>
                            <option value="rejected">❌ Reject</option>
                        </select>
                        <small class="text-muted">
                            <?php if (($app['percentage'] ?? 0) < ($app['min_marks_percentage'] ?? 0)): ?>
                            <span class="text-danger">Warning: Student does not meet minimum percentage requirement!</span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>