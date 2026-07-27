<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

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

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Review Application';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Review Application</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    <!-- Application Details -->
    <div class="card">
        <div class="card-header">
            <h3>Application Details</h3>
        </div>

        <div class="detail-row">
            <span class="detail-label">Student</span>
            <span class="detail-value">
                <strong><?= htmlspecialchars($application['student_name']) ?></strong>
                <div class="muted" style="font-size:12px;"><?= htmlspecialchars($application['student_id']) ?></div>
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Program</span>
            <span class="detail-value"><?= htmlspecialchars($application['program_name'] ?? 'N/A') ?></span>
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
                    <a href="../<?= $application['attachment'] ?>" target="_blank" class="btn btn-sm btn-outline">View Attachment</a>
                <?php else: ?>
                    <span class="muted">No attachment</span>
                <?php endif; ?>
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Submitted</span>
            <span class="detail-value"><?= date('d M Y h:i A', strtotime($application['created_at'])) ?></span>
        </div>

        <div class="detail-row" style="border-bottom:none;">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <span class="status-badge <?= $application['status'] ?>"><?= $application['status'] ?></span>
            </span>
        </div>
    </div>

    <!-- Review Form -->
    <div class="card">
        <div class="card-header">
            <h3>Approve Application</h3>
            <p class="muted" style="font-size:13px;margin-top:2px;">Add an optional message for the applicant.</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Decision <span style="color:var(--danger);">*</span></label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--success);">
                        <input type="radio" name="action" value="approve" style="width:18px;height:18px;accent-color:var(--success);">
                        Approve
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--danger);">
                        <input type="radio" name="action" value="reject" style="width:18px;height:18px;accent-color:var(--danger);">
                        Reject
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Message (optional)</label>
                <textarea name="remarks" rows="4" required
                          placeholder="e.g. Application approved. You may proceed with the request."
                          style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:.84rem;resize:vertical;"></textarea>
                <div class="hint">Provide detailed remarks for your decision.</div>
            </div>

            <div style="display:flex;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary" onclick="this.form.action_value.value='approve'">Submit</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
            <input type="hidden" name="action_value" value="">
        </form>
    </div>

</div>

<script>
document.querySelectorAll('input[name="action"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelector('input[name="action_value"]').value = this.value;
    });
});
</script>

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
