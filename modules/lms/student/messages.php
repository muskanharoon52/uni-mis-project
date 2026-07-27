<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
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

<div class="card">
    <div class="card-header"><h3>Messages</h3></div>
    <?php if (!$messages): ?>
        <div style="padding:30px;text-align:center;color:#7f8c8d;">No messages yet.</div>
    <?php else: ?>
        <div style="padding:0;">
            <?php foreach ($messages as $msg): ?>
                <div style="padding:16px 20px;border-bottom:1px solid #e9ecef;<?= !$msg['is_read'] ? 'background:#f0f7ff;' : '' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <strong style="color:#2c3e50;"><?= e($msg['title']) ?></strong>
                        <small style="color:#7f8c8d;"><?= e((string) $msg['created_at']) ?></small>
                    </div>
                    <p style="margin:0 0 4px;color:#555;"><?= e($msg['body']) ?></p>
                    <small style="color:#7f8c8d;">From: <?= e($msg['sender_name'] ?? 'System') ?></small>
                    <?php if ($msg['link_url']): ?>
                        <br><a href="<?= e($msg['link_url']) ?>" style="color:#4facfe;font-size:13px;">View details &rarr;</a>
                    <?php endif; ?>
                    <?php if (!$msg['is_read']): ?>
                        <form method="post" action="<?= app_url('student/mark_read.php') ?>" style="display:inline;margin-left:8px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="notification_id" value="<?= (int) $msg['notification_id'] ?>">
                            <button type="submit" style="background:none;border:none;color:#4facfe;cursor:pointer;font-size:12px;text-decoration:underline;">Mark read</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
