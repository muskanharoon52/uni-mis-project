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
<div class="page-head">
    <div>
        <h1>Applications</h1>
        <p class="muted">Submit leave or support applications to the department.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="grid">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <h2>Submit Application</h2>
        <label for="type">Type</label>
        <input id="type" name="type" placeholder="Leave Request" required>
        <label for="details">Details</label>
        <textarea id="details" name="details" required></textarea>
        <button class="btn" type="submit">Submit</button>
    </form>

    <div class="table-card">
        <h2>My Applications</h2>
        <table>
            <tr><th>Type</th><th>Details</th><th>Status</th><th>Date</th></tr>
            <?php foreach ($applications as $application): ?>
                <tr>
                    <td><?= e($application['type']) ?></td>
                    <td><?= e($application['details']) ?></td>
                    <td><span class="badge <?= e($application['status']) ?>"><?= e($application['status']) ?></span></td>
                    <td><?= e($application['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$applications): ?>
                <tr><td colspan="4" class="muted">No applications submitted yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
