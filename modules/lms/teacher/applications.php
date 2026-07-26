<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'applications';
$pageTitle = 'Applications';
$message = '';
$error = '';

// Check if the table exists
try {
    $tableCheck = db()->query("SHOW TABLES LIKE 'lms_applications'");
    $tableExists = $tableCheck->rowCount() > 0;
} catch (PDOException $e) {
    $tableExists = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        
        if (!$tableExists) {
            throw new RuntimeException('Applications table does not exist. Please contact the administrator.');
        }
        
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid application status.');
        }
        $stmt = db()->prepare('UPDATE lms_applications SET status = ? WHERE application_id = ?');
        $stmt->execute([$status, (int) $_POST['application_id']]);
        $message = 'Application updated.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

// FIXED: Added error handling and fallback
try {
    if ($tableExists) {
        $applications = db()->query(
            'SELECT a.*, u.full_name, u.email 
             FROM lms_applications a 
             JOIN users u ON u.user_id = a.user_id 
             ORDER BY a.created_at DESC'
        )->fetchAll();
    } else {
        // Fallback: Show a message and empty array
        $applications = [];
        $error = 'The applications module is not available. Please contact the administrator.';
    }
} catch (PDOException $e) {
    error_log('Applications query failed: ' . $e->getMessage());
    $applications = [];
    $error = 'Failed to load applications. Please try again later.';
}

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card">
    <div class="card-header"><h3>Applications</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Student</th><th>Type</th><th>Details</th><th>Status</th><th>Action</th></tr>
            <?php if (!$tableExists): ?>
                <tr><td colspan="5" class="muted text-center">Applications module is not configured. Please create the required database table.</td></tr>
            <?php elseif (!$applications): ?>
                <tr><td colspan="5" class="muted text-center">No applications found.</td></tr>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= e($application['full_name']) ?><br><span class="muted"><?= e($application['email']) ?></span></td>
                        <td><?= e($application['type']) ?></td>
                        <td><?= e($application['details']) ?></td>
                        <td><span class="badge badge-<?= $application['status'] === 'approved' ? 'active' : ($application['status'] === 'rejected' ? 'inactive' : 'draft') ?>"><?= e($application['status']) ?></span></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $application['application_id'] ?>">
                                <select name="status">
                                    <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                                <button class="btn btn-primary btn-sm" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>