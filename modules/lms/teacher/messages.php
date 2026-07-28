<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('teacher');
$active = 'messages';
$pageTitle = 'Messages';

$messagesStmt = db()->prepare(
    "SELECT n.*, u.full_name AS sender_name
     FROM lms_notifications n
     LEFT JOIN users u ON u.user_id = n.sender_user_id
     WHERE n.recipient_user_id = ? AND n.category = 'message'
     ORDER BY n.created_at DESC"
);
$messagesStmt->execute([$user['id']]);
$messages = $messagesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (empty($messages)): ?>
    <div class="card">
        <div class="card-header"><h3>Messages</h3></div>
        <p class="muted" style="padding:16px 0;">No messages received yet.</p>
    </div>
<?php else: ?>
    <?php foreach ($messages as $msg): ?>
        <div class="card" style="margin-bottom:12px; <?= $msg['is_read'] ? '' : 'border-left:3px solid #6366f1; background:#f8faff;' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                <div>
                    <h3 style="font-size:1rem;font-weight:700;margin:0;"><?= e($msg['title']) ?></h3>
                    <span class="muted">From: <?= e($msg['sender_name'] ?? 'SSO Admin') ?> &middot; <?= date('M j, g:i A', strtotime($msg['created_at'])) ?></span>
                </div>
                <?php if (!$msg['is_read']): ?>
                    <span class="badge badge-draft" style="font-size:.68rem;">Unread</span>
                <?php endif; ?>
            </div>
            <p style="margin:8px 0 12px;color:#334155;line-height:1.6;"><?= nl2br(e($msg['body'])) ?></p>
            <?php if (!$msg['is_read']): ?>
                <form method="post" action="<?= app_url('student/mark_read.php') ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="notification_id" value="<?= (int) $msg['notification_id'] ?>">
                    <button class="btn btn-sm btn-outline" type="submit">Mark as read</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
