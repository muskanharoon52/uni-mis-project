<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'queries';
$pageTitle = 'Queries';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $stmt = db()->prepare('INSERT INTO lms_queries (user_id, subject, message) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], trim((string) $_POST['subject']), trim((string) $_POST['message'])]);
        $message = 'Query submitted.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$queriesStmt = db()->prepare('SELECT * FROM lms_queries WHERE user_id = ? ORDER BY created_at DESC');
$queriesStmt->execute([$user['id']]);
$queries = $queriesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<section class="grid-2">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <h3>Ask a Question</h3>
        <label for="subject">Subject</label>
        <input id="subject" name="subject" required>
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
        <button class="btn btn-primary" type="submit">Submit Query</button>
    </form>
    <div class="card">
        <div class="card-header"><h3>My Queries</h3></div>
        <div class="table-responsive">
        <table>
            <tr><th>Subject</th><th>Message</th><th>Status</th><th>Reply</th></tr>
            <?php foreach ($queries as $query): ?>
                <tr><td><?= e($query['subject']) ?></td><td><?= e($query['message']) ?></td><td><span class="badge badge-<?= $query['status'] === 'answered' ? 'active' : 'draft' ?>"><?= e($query['status']) ?></span></td><td><?= e($query['reply'] ?: 'No reply yet') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$queries): ?>
                <tr><td colspan="4" class="muted text-center">No queries submitted yet.</td></tr>
            <?php endif; ?>
            </table>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
