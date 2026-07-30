<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Scholarship';
include __DIR__ . '/../includes/header.php';

$error = '';

// Get users for approved_by dropdown
$users = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll();

// Get semesters for dropdown
$semesters = $pdo->query("SELECT semester_id, semester_name FROM semesters GROUP BY semester_name ORDER BY CAST(SUBSTRING_INDEX(semester_name, ' ', -1) AS UNSIGNED)")->fetchAll();

// Get sessions for dropdown
$sessions = $pdo->query("SELECT session_id, session_name FROM sessions ORDER BY session_id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $application_id = !empty($_POST['application_id']) ? intval($_POST['application_id']) : null;
        $scholarship_type = trim($_POST['scholarship_type'] ?? 'Merit');
        $description = trim($_POST['description'] ?? '');
        $scholarship_name = trim($_POST['scholarship_name'] ?? '');
        $percentage = floatval($_POST['percentage'] ?? 0);
        $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : null;
        $duration = trim($_POST['duration'] ?? '');
        $semester_id = !empty($_POST['semester_id']) ? intval($_POST['semester_id']) : null;
        $session_id = !empty($_POST['session_id']) ? intval($_POST['session_id']) : null;
        
        // Force status to 'Active' so it shows on index.php immediately
        $status = 'Active'; 
        $application_status = $_POST['application_status'] ?? 'Submitted';
        
        $approved_by = !empty($_POST['approved_by']) ? intval($_POST['approved_by']) : null;
        $approved_date = !empty($_POST['approved_date']) ? $_POST['approved_date'] : null;
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        
        if (empty($scholarship_name)) {
            throw new Exception('Scholarship name is required.');
        }
        
        if (!empty($approved_by)) {
            $check_user = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $check_user->execute([$approved_by]);
            if (!$check_user->fetch()) {
                throw new Exception('Selected approver does not exist in the system.');
            }
        }
        
        // NOTE: student_id has been completely removed from this insert
        $sql = "INSERT INTO admission_scholarships SET 
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
        <!-- Scholarship Details -->
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">
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

        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">
            <i class="fas fa-calendar-alt"></i> Academic Period
        </h6>
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
            <div class="form-group">
                <label>Application ID</label>
                <input type="number" name="application_id" 
                       value="<?= htmlspecialchars($_POST['application_id'] ?? '') ?>"
                       placeholder="Optional">
            </div>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Scholarship</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>