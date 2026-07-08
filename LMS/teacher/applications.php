<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'applications';
$pageTitle = 'Applications';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid application status.');
        }
        $stmt = db()->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) $_POST['application_id']]);
        $message = 'Application updated.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$applications = db()->query(
    'SELECT a.*, u.name, u.email FROM applications a JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Applications</h1></div>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="table-card">
    <table>
        <tr><th>Student</th><th>Type</th><th>Details</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($applications as $application): ?>
            <tr>
                <td><?= e($application['name']) ?><br><span class="muted"><?= e($application['email']) ?></span></td>
                <td><?= e($application['type']) ?></td>
                <td><?= e($application['details']) ?></td>
                <td><span class="badge <?= e($application['status']) ?>"><?= e($application['status']) ?></span></td>
                <td>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                        <select name="status">
                            <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button class="btn" type="submit">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
