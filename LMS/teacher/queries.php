<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'messages';
$pageTitle = 'Student Queries';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $queryId = (int) ($_POST['query_id'] ?? 0);
        $allowedStmt = db()->prepare(
            'SELECT q.user_id
             FROM queries q
             JOIN enrollments e ON e.student_id = q.user_id
             JOIN courses c ON c.id = e.course_id
             WHERE q.id = ? AND c.teacher_id = ?
             LIMIT 1'
        );
        $allowedStmt->execute([$queryId, $user['id']]);
        $studentId = (int) $allowedStmt->fetchColumn();
        if (!$studentId) {
            throw new RuntimeException('You cannot reply to this query.');
        }

        $stmt = db()->prepare("UPDATE queries SET status = 'answered', reply = ? WHERE id = ?");
        $stmt->execute([trim((string) $_POST['reply']), $queryId]);
        create_notification($studentId, 'Query answered', 'A teacher has replied to your query.', app_url('student/queries.php'), 'notification', (int) $user['id']);
        $message = 'Reply saved.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$queriesStmt = db()->prepare(
    'SELECT DISTINCT q.*, u.name, u.email
     FROM queries q
     JOIN users u ON u.id = q.user_id
     JOIN enrollments e ON e.student_id = q.user_id
     JOIN courses c ON c.id = e.course_id
     WHERE c.teacher_id = ?
     ORDER BY q.created_at DESC'
);
$queriesStmt->execute([$user['id']]);
$queries = $queriesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card">
    <div class="card-header"><h3>Student Queries</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Student</th><th>Subject</th><th>Message</th><th>Status</th><th>Reply</th></tr>
            <?php foreach ($queries as $query): ?>
                <tr>
                    <td><?= e($query['name']) ?><br><span class="muted"><?= e($query['email']) ?></span></td>
                    <td><?= e($query['subject']) ?></td>
                    <td><?= e($query['message']) ?></td>
                    <td><span class="badge badge-<?= $query['status'] === 'answered' ? 'active' : 'draft' ?>"><?= e($query['status']) ?></span></td>
                <td>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="query_id" value="<?= (int) $query['id'] ?>">
                        <textarea name="reply"><?= e($query['reply']) ?></textarea>
                        <button class="btn btn-primary btn-sm" type="submit">Reply</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
