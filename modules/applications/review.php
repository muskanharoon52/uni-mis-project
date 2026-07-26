<?php
require_once '../../config/database.php';
$page_title = 'Review Application';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT a.*, d.department_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    WHERE a.application_id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app || !in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])) {
    setFlash('error', 'Application cannot be reviewed');
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'];
        $rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update application status
        $stmt = $pdo->prepare("
            UPDATE admission_applications SET 
                application_status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                rejection_reason = ?
            WHERE application_id = ?
        ");
        
        if ($stmt->execute([$status, $_SESSION['user_id'], $rejection_reason, $id])) {
            
            // =============================================
            // AUTO CREATE STUDENT IF APPROVED OR ADMITTED
            // =============================================
            if (in_array($status, ['Approved', 'Admitted'])) {
                
                // Check if student already exists for this application
                $check = $pdo->prepare("SELECT id FROM admission_students WHERE application_id = ?");
                $check->execute([$id]);
                $existing = $check->fetch();
                
                if (!$existing) {
                    // Generate student ID with new format
                    $student_id = generateStudentId();
                    
                    // Insert student record
                    $student_stmt = $pdo->prepare("
                        INSERT INTO admission_students 
                        (student_id, application_id, student_name, father_name, cnic_or_bform, 
                         dob, gender, contact_no, email, address, program_id, enrollment_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    
                    $student_result = $student_stmt->execute([
                        $student_id,
                        $app['application_id'],
                        $app['full_name'],
                        $app['father_name'],
                        $app['cnic_or_bform'],
                        $app['dob'],
                        $app['gender'],
                        $app['contact_no'],
                        $app['email'],
                        $app['address'],
                        $app['program_id'],
                        date('Y-m-d')
                    ]);
                    
                    if ($student_result) {
                        // Update application status to Admitted
                        $pdo->prepare("
                            UPDATE admission_applications 
                            SET application_status = 'Admitted' 
                            WHERE application_id = ?
                        ")->execute([$id]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        setFlash('success', 'Application approved! Student created with ID: ' . $student_id);
                        header('Location: index.php');
                        exit();
                    } else {
                        // Rollback if student creation fails
                        $pdo->rollBack();
                        setFlash('error', 'Failed to create student record');
                    }
                } else {
                    // Student already exists
                    $pdo->commit();
                    setFlash('success', 'Application reviewed successfully! Student already exists.');
                    header('Location: index.php');
                    exit();
                }
            } else {
                // For Rejected or Under Review - just commit
                $pdo->commit();
                setFlash('success', 'Application reviewed successfully! Status: ' . $status);
                header('Location: index.php');
                exit();
            }
        } else {
            $pdo->rollBack();
            setFlash('error', 'Failed to update application');
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header"><h5><i class="fas fa-check"></i> Review Application</h5></div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white"><h6 class="mb-0">Application Details</h6></div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Application No</th><td><strong><?= $app['temp_application_no'] ?></strong></td></tr>
                    <tr><th>Student Name</th><td><?= $app['full_name'] ?></td></tr>
                    <tr><th>Father Name</th><td><?= $app['father_name'] ?></td></tr>
                    <tr><th>CNIC/B-Form</th><td><?= $app['cnic_or_bform'] ?? 'N/A' ?></td></tr>
                    <tr><th>Department</th><td><?= $app['department_name'] ?? 'N/A' ?></td></tr>
                    <tr><th>Submitted Date</th><td><?= date('d M Y', strtotime($app['submitted_at'])) ?></td></tr>
                    <tr><th>Current Status</th><td><span class="badge bg-warning"><?= $app['application_status'] ?></span></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning"><h6 class="mb-0">Review Decision</h6></div>
            <div class="card-body">
                <form method="POST" id="reviewForm">
                    <div class="mb-3">
                        <label class="form-label">Decision *</label>
                        <select name="status" class="form-select" required id="decisionSelect">
                            <option value="Approved">✅ Approve (Auto-create Student)</option>
                            <option value="Admitted">🎓 Admit (Auto-create Student)</option>
                            <option value="Rejected">❌ Reject</option>
                            <option value="Under Review">⏳ Keep Under Review</option>
                        </select>
                        <small class="text-muted">Selecting "Approve" or "Admit" will automatically create a student record.</small>
                    </div>
                    <div class="mb-3" id="rejection_reason_div" style="display:none;">
                        <label class="form-label">Rejection Reason <small class="text-danger">(Required if Rejected)</small></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                    <div class="mb-3" id="student_id_preview" style="display:none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Student will be created with ID: <strong id="previewStudentId">UNI-2024-00001</strong>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('decisionSelect').addEventListener('change', function() {
    const rejectionDiv = document.getElementById('rejection_reason_div');
    const previewDiv = document.getElementById('student_id_preview');
    
    if (this.value === 'Rejected') {
        rejectionDiv.style.display = 'block';
        previewDiv.style.display = 'none';
    } else if (this.value === 'Approved' || this.value === 'Admitted') {
        rejectionDiv.style.display = 'none';
        previewDiv.style.display = 'block';
        
        // Get current year and generate preview ID
        const year = new Date().getFullYear();
        const randomNum = Math.floor(Math.random() * 90000) + 10000;
        document.getElementById('previewStudentId').textContent = 'UNI-' + year + '-' + randomNum;
    } else {
        rejectionDiv.style.display = 'none';
        previewDiv.style.display = 'none';
    }
});
</script>
<?php include '../../includes/footer.php'; ?>