<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Scholarship';
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get students for dropdown
$students = $pdo->query("SELECT student_id, full_name FROM students ORDER BY full_name")->fetchAll();

// Get users for approved_by dropdown
$users = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll();

// Get semesters for dropdown (remove duplicates)
$semesters = $pdo->query("SELECT semester_id, semester_name FROM semesters GROUP BY semester_name ORDER BY CAST(SUBSTRING_INDEX(semester_name, ' ', -1) AS UNSIGNED)")->fetchAll();

// Get sessions for dropdown
$sessions = $pdo->query("SELECT session_id, session_name FROM sessions ORDER BY session_id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data matching your table columns
        $student_id = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
        $application_id = !empty($_POST['application_id']) ? intval($_POST['application_id']) : null;
        $scholarship_type = trim($_POST['scholarship_type'] ?? 'Merit');
        $description = trim($_POST['description'] ?? '');
        $scholarship_name = trim($_POST['scholarship_name'] ?? '');
        $percentage = floatval($_POST['percentage'] ?? 0);
        $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : null;
        $duration = trim($_POST['duration'] ?? '');
        $semester_id = !empty($_POST['semester_id']) ? intval($_POST['semester_id']) : null;
        $session_id = !empty($_POST['session_id']) ? intval($_POST['session_id']) : null;
        $status = $_POST['status'] ?? 'Pending';
        $application_status = $_POST['application_status'] ?? 'Submitted';
        
        // ============================================
        // FIX: Handle approved_by properly
        // ============================================
        $approved_by = !empty($_POST['approved_by']) ? intval($_POST['approved_by']) : null;
        $approved_date = !empty($_POST['approved_date']) ? $_POST['approved_date'] : null;
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Validation
        if (empty($scholarship_name)) {
            throw new Exception('Scholarship name is required.');
        }
        
        if (empty($student_id)) {
            throw new Exception('Student is required.');
        }
        
        // ============================================
        // FIX: Validate approved_by exists in users table
        // ============================================
        if (!empty($approved_by)) {
            $check_user = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $check_user->execute([$approved_by]);
            if (!$check_user->fetch()) {
                throw new Exception('Selected approver does not exist in the system.');
            }
        }
        
        // Prepare the insert statement with all columns
        $sql = "INSERT INTO admission_scholarships SET 
                student_id = :student_id,
                application_id = :application_id,
                scholarship_type = :scholarship_type,
                description = :description,
                scholarship_name = :scholarship_name,
                percentage = :percentage,
                amount = :amount,
                duration = :duration,
                semester_id = :semester_id,
                session_id = :session_id,
                status = :status,
                application_status = :application_status,
                approved_by = :approved_by,
                approved_date = :approved_date,
                rejection_reason = :rejection_reason,
                remarks = :remarks";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            'student_id' => $student_id,
            'application_id' => $application_id,
            'scholarship_type' => $scholarship_type,
            'description' => $description,
            'scholarship_name' => $scholarship_name,
            'percentage' => $percentage,
            'amount' => $amount,
            'duration' => $duration,
            'semester_id' => $semester_id,
            'session_id' => $session_id,
            'status' => $status,
            'application_status' => $application_status,
            'approved_by' => $approved_by,
            'approved_date' => $approved_date,
            'rejection_reason' => $rejection_reason,
            'remarks' => $remarks
        ]);
        
        setFlash('success', 'Scholarship added successfully!');
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Add New Scholarship</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="post">
        <!-- Student Information -->
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">
            <i class="fas fa-user-graduate"></i> Student Information
        </h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach($students as $student): ?>
                    <option value="<?= $student['student_id'] ?>" 
                            <?= ($_POST['student_id'] ?? '') == $student['student_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Application ID</label>
                <input type="number" name="application_id" 
                       value="<?= htmlspecialchars($_POST['application_id'] ?? '') ?>"
                       placeholder="Optional">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Semester</label>
                <select name="semester_id">
                    <option value="">-- Select Semester --</option>
                    <?php foreach($semesters as $sem): ?>
                    <option value="<?= $sem['semester_id'] ?>"
                            <?= ($_POST['semester_id'] ?? '') == $sem['semester_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sem['semester_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Session</label>
                <select name="session_id">
                    <option value="">-- Select Session --</option>
                    <?php foreach($sessions as $sess): ?>
                    <option value="<?= $sess['session_id'] ?>"
                            <?= ($_POST['session_id'] ?? '') == $sess['session_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sess['session_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Scholarship Details -->
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">
            <i class="fas fa-award"></i> Scholarship Details
        </h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Scholarship Name *</label>
                <input type="text" name="scholarship_name" required 
                       value="<?= htmlspecialchars($_POST['scholarship_name'] ?? '') ?>"
                       placeholder="e.g., Merit Scholarship 2024">
            </div>
            <div class="form-group">
                <label>Scholarship Type</label>
                <select name="scholarship_type">
                    <option value="Merit" <?= ($_POST['scholarship_type'] ?? '') == 'Merit' ? 'selected' : '' ?>>Merit</option>
                    <option value="Need-based" <?= ($_POST['scholarship_type'] ?? '') == 'Need-based' ? 'selected' : '' ?>>Need-based</option>
                    <option value="Sports" <?= ($_POST['scholarship_type'] ?? '') == 'Sports' ? 'selected' : '' ?>>Sports</option>
                    <option value="Talent" <?= ($_POST['scholarship_type'] ?? '') == 'Talent' ? 'selected' : '' ?>>Talent</option>
                    <option value="Special" <?= ($_POST['scholarship_type'] ?? '') == 'Special' ? 'selected' : '' ?>>Special</option>
                    <option value="Other" <?= ($_POST['scholarship_type'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Brief description of the scholarship"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Percentage (%)</label>
                <input type="number" name="percentage" step="0.01" 
                       value="<?= htmlspecialchars($_POST['percentage'] ?? '') ?>"
                       placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Amount (PKR)</label>
                <input type="number" name="amount" step="0.01" 
                       value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                       placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Duration</label>
                <input type="text" name="duration" 
                       value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>"
                       placeholder="e.g., 1 Year, 2 Semesters">
            </div>
        </div>
        
        <!-- Status Information -->
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">
            <i class="fas fa-info-circle"></i> Status Information
        </h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Pending" <?= ($_POST['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= ($_POST['status'] ?? '') == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= ($_POST['status'] ?? '') == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Active" <?= ($_POST['status'] ?? '') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Expired" <?= ($_POST['status'] ?? '') == 'Expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="Cancelled" <?= ($_POST['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Application Status</label>
                <select name="application_status">
                    <option value="Submitted" <?= ($_POST['application_status'] ?? '') == 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="UnderReview" <?= ($_POST['application_status'] ?? '') == 'UnderReview' ? 'selected' : '' ?>>Under Review</option>
                    <option value="Approved" <?= ($_POST['application_status'] ?? '') == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= ($_POST['application_status'] ?? '') == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Granted" <?= ($_POST['application_status'] ?? '') == 'Granted' ? 'selected' : '' ?>>Granted</option>
                    <option value="Denied" <?= ($_POST['application_status'] ?? '') == 'Denied' ? 'selected' : '' ?>>Denied</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Approved By</label>
                <select name="approved_by">
                    <option value="">-- Select Approver --</option>
                    <?php foreach($users as $user): ?>
                    <option value="<?= $user['user_id'] ?>"
                            <?= ($_POST['approved_by'] ?? '') == $user['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-muted); font-size: 0.8rem;">Leave empty if not approved yet</small>
            </div>
            <div class="form-group">
                <label>Approved Date</label>
                <input type="date" name="approved_date" 
                       value="<?= htmlspecialchars($_POST['approved_date'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Rejection Reason</label>
            <textarea name="rejection_reason" rows="2" placeholder="If rejected, provide reason"><?= htmlspecialchars($_POST['rejection_reason'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Remarks</label>
            <textarea name="remarks" rows="2" placeholder="Additional remarks"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Scholarship</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>