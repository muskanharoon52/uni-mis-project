<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'messages';
$pageTitle = 'Messages';
$message = '';
$error = '';

$coursesStmt = db()->prepare('SELECT id, code, title FROM courses WHERE teacher_id = ? ORDER BY code');
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $scope = (string) ($_POST['scope'] ?? 'all');
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($subject === '' || $body === '') {
            throw new RuntimeException('Subject and message are required.');
        }

        if ($scope === 'course') {
            if ($courseId <= 0 || !teacher_owns_course((int) $user['id'], $courseId)) {
                throw new RuntimeException('Select one of your courses.');
            }

            $recipientIds = teacher_course_student_ids((int) $user['id'], $courseId);
            $linkUrl = app_url('student/courses.php?course_id=' . $courseId);
        } else {
            $recipientIds = teacher_course_student_ids((int) $user['id']);
            $linkUrl = app_url('student/dashboard.php');
        }

        if (!$recipientIds) {
            throw new RuntimeException('No students were found for this message.');
        }

        foreach (array_unique($recipientIds) as $recipientId) {
            create_notification(
                (int) $recipientId,
                $subject,
                $body,
                $linkUrl,
                'message',
                (int) $user['id']
            );
        }

        $message = 'Message sent to ' . count(array_unique($recipientIds)) . ' students.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$messagesStmt = db()->prepare(
    "SELECT n.title, n.body, n.link_url, MAX(n.created_at) AS sent_at, COUNT(*) AS recipient_count
     FROM notifications n
     WHERE n.sender_user_id = ? AND n.category = 'message'
     GROUP BY n.title, n.body, n.link_url, DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i')
     ORDER BY sent_at DESC
     LIMIT 8"
);
$messagesStmt->execute([$user['id']]);
$messages = $messagesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Messages</h1>
        <p class="muted">Send short notes to students and let them appear in the header inbox.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="grid">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <h2>Compose Message</h2>
        <label for="scope">Recipients</label>
        <select id="scope" name="scope">
            <option value="all">All my students</option>
            <option value="course">Single course</option>
        </select>
        <label for="course_id">Course</label>
        <select id="course_id" name="course_id">
            <option value="0">Select course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= (int) $course['id'] ?>"><?= e($course['code'] . ' - ' . $course['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="subject">Subject</label>
        <input id="subject" name="subject" maxlength="160" required>
        <label for="body">Message</label>
        <textarea id="body" name="body" placeholder="Write a concise message for your students." required></textarea>
        <button class="btn" type="submit">Send Message</button>
    </form>

    <div class="table-card">
        <h2>Recent Messages</h2>
        <table>
            <tr>
                <th>Subject</th>
                <th>Sent</th>
                <th>Recipients</th>
                <th>Message</th>
            </tr>
            <?php foreach ($messages as $messageItem): ?>
                <tr>
                    <td><?= e($messageItem['title']) ?></td>
                    <td><?= e((string) $messageItem['sent_at']) ?></td>
                    <td><?= (int) $messageItem['recipient_count'] ?></td>
                    <td><?= e($messageItem['body']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$messages): ?>
                <tr>
                    <td colspan="4" class="muted">No messages sent yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
