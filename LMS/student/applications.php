<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'applications';
$pageTitle = 'Applications';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $type = trim((string) ($_POST['type'] ?? ''));
        $details = trim((string) ($_POST['details'] ?? ''));

        if ($type === '' || $details === '') {
            throw new RuntimeException('Type and details are required.');
        }

        $stmt = db()->prepare('INSERT INTO applications (user_id, type, details) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $type, $details]);
        $message = 'Application submitted successfully.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$applicationsStmt = db()->prepare('SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC');
$applicationsStmt->execute([$user['id']]);
$applications = $applicationsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<section class="grid-2">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <h3>Submit Application</h3>
        <label for="type">Type</label>
        <input id="type" name="type" placeholder="Leave Request" required>
        <label for="details">Details</label>
        <textarea id="details" name="details" required></textarea>
        <button class="btn btn-primary" type="submit">Submit</button>
    </form>

    <div class="card">
        <div class="card-header"><h3>My Applications</h3></div>
        <div class="table-responsive">
        <table>
            <tr><th>Type</th><th>Details</th><th>Status</th><th>Date</th></tr>
            <?php foreach ($applications as $application): ?>
                <tr>
                    <td><?= e($application['type']) ?></td>
                    <td><?= e($application['details']) ?></td>
                    <td><span class="badge badge-<?= $application['status'] === 'approved' ? 'active' : ($application['status'] === 'rejected' ? 'inactive' : 'draft') ?>"><?= e($application['status']) ?></span></td>
                    <td><?= e($application['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$applications): ?>
                <tr><td colspan="4" class="muted">No applications submitted yet.</td></tr>
            <?php endif; ?>
            </table>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
