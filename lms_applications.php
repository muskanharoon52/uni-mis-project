<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: modules/sso/login.php');
    exit;
}

$conn = getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $appId = (int)($_POST['application_id'] ?? 0);
        $responseMsg = trim($_POST['response_message'] ?? '');

        if ($appId <= 0 || !in_array($action, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid request.');
        }

        $stmt = mysqli_prepare($conn, 'UPDATE lms_applications SET status = ?, response_message = ? WHERE application_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssi', $action, $responseMsg, $appId);
        mysqli_stmt_execute($stmt);

        $message = "Application #$appId marked as $action.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$query = "SELECT a.*, u.full_name, u.username, u.email,
          r.role_name AS user_role
          FROM lms_applications a
          JOIN users u ON u.user_id = a.user_id
          LEFT JOIN roles r ON r.role_id = u.role_id
          ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $query);
$applications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

$page_title = 'LMS Applications';
$headerFile = __DIR__ . '/includes/header.php';
$sidebarFile = __DIR__ . '/includes/sidebar.php';
if (file_exists($headerFile)) include $headerFile;
if (file_exists($sidebarFile)) include $sidebarFile;
?>

<div class="main-content">
    <div class="topbar">
        <div>
            <span class="welcome-text">LMS Applications</span>
            <small class="text-muted">Review and manage applications from students and teachers</small>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background:#d4edda;color:#155724;padding:12px 20px;border-radius:8px;margin-bottom:15px;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#f8d7da;color:#721c24;padding:12px 20px;border-radius:8px;margin-bottom:15px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-custom">
        <div class="card-title"><i class="fas fa-file-alt"></i> All Applications</div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid #e9ecef;">
                        <th style="padding:12px 15px;text-align:left;">#</th>
                        <th style="padding:12px 15px;text-align:left;">Applicant</th>
                        <th style="padding:12px 15px;text-align:left;">Role</th>
                        <th style="padding:12px 15px;text-align:left;">Type</th>
                        <th style="padding:12px 15px;text-align:left;">Details</th>
                        <th style="padding:12px 15px;text-align:left;">Status</th>
                        <th style="padding:12px 15px;text-align:left;">Date</th>
                        <th style="padding:12px 15px;text-align:left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$applications): ?>
                        <tr>
                            <td colspan="8" style="padding:30px;text-align:center;color:#7f8c8d;">No applications found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr style="border-bottom:1px solid #e9ecef;">
                                <td style="padding:12px 15px;"><?= (int)$app['application_id'] ?></td>
                                <td style="padding:12px 15px;">
                                    <strong><?= htmlspecialchars($app['full_name'] ?? '') ?></strong>
                                    <br><small style="color:#7f8c8d;"><?= htmlspecialchars($app['username'] ?? '') ?></small>
                                </td>
                                <td style="padding:12px 15px;">
                                    <span style="background:<?= strtolower($app['user_role'] ?? '') === 'teacher' ? '#e3f2fd' : '#e8f5e9' ?>;color:<?= strtolower($app['user_role'] ?? '') === 'teacher' ? '#1565c0' : '#2e7d32' ?>;padding:4px 12px;border-radius:20px;font-size:12px;">
                                        <?= htmlspecialchars(ucfirst($app['user_role'] ?? 'unknown')) ?>
                                    </span>
                                </td>
                                <td style="padding:12px 15px;"><?= htmlspecialchars($app['type'] ?? '') ?></td>
                                <td style="padding:12px 15px;max-width:300px;word-break:break-word;"><?= htmlspecialchars($app['details'] ?? '') ?></td>
                                <td style="padding:12px 15px;">
                                    <?php
                                    $statusColors = [
                                        'pending' => ['bg' => '#fff3cd', 'fg' => '#856404'],
                                        'approved' => ['bg' => '#d4edda', 'fg' => '#155724'],
                                        'rejected' => ['bg' => '#f8d7da', 'fg' => '#721c24'],
                                    ];
                                    $st = $app['status'] ?? 'pending';
                                    $colors = $statusColors[$st] ?? $statusColors['pending'];
                                    ?>
                                    <span style="background:<?= $colors['bg'] ?>;color:<?= $colors['fg'] ?>;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;">
                                        <?= htmlspecialchars(ucfirst($st)) ?>
                                    </span>
                                    <?php if (!empty($app['response_message'])): ?>
                                        <div class="response-msg"><?= htmlspecialchars($app['response_message']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 15px;"><?= htmlspecialchars($app['created_at'] ?? '') ?></td>
                                <td style="padding:12px 15px;">
                                    <?php if ($st === 'pending'): ?>
                                        <button type="button" onclick="openModal(<?= (int)$app['application_id'] ?>, 'approved', 'Approve')" style="background:#28a745;color:white;border:none;padding:5px 12px;border-radius:5px;cursor:pointer;font-size:12px;margin-right:4px;">Approve</button>
                                        <button type="button" onclick="openModal(<?= (int)$app['application_id'] ?>, 'rejected', 'Reject')" style="background:#dc3545;color:white;border:none;padding:5px 12px;border-radius:5px;cursor:pointer;font-size:12px;">Reject</button>
                                    <?php else: ?>
                                        <span style="color:#7f8c8d;font-size:12px;">No action needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Modal -->
<div class="modal-overlay" id="modal">
    <div class="modal-box">
        <h3 id="modal-title">Approve Application</h3>
        <p class="modal-subtitle" id="modal-subtitle">Add an optional message for the applicant.</p>
        <form method="post" id="modal-form">
            <input type="hidden" name="application_id" id="modal-app-id">
            <input type="hidden" name="action" id="modal-action">
            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Message (optional)</label>
            <textarea name="response_message" id="modal-message" placeholder="e.g. Application approved. You may proceed with the request."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" id="modal-submit-btn">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(appId, action, label) {
    document.getElementById('modal-app-id').value = appId;
    document.getElementById('modal-action').value = action;
    document.getElementById('modal-title').textContent = label + ' Application';
    document.getElementById('modal-message').value = '';
    
    var btn = document.getElementById('modal-submit-btn');
    btn.textContent = label;
    btn.className = action === 'approved' ? 'btn-approve' : 'btn-reject';
    
    var sub = document.getElementById('modal-subtitle');
    sub.textContent = action === 'approved'
        ? 'Approving this application. Add a message for the applicant.'
        : 'Rejecting this application. Please provide a reason.';
    
    document.getElementById('modal').classList.add('active');
    document.getElementById('modal-message').focus();
}
function closeModal() {
    document.getElementById('modal').classList.remove('active');
}
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
