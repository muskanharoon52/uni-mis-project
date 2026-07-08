<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'announcements';
$pageTitle = 'Announcements';
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
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($title === '' || $body === '') {
            throw new RuntimeException('Title and announcement body are required.');
        }

        $recipientIds = [];
        $linkUrl = app_url('student/dashboard.php');
        if ($scope === 'course') {
            if ($courseId <= 0 || !teacher_owns_course((int) $user['id'], $courseId)) {
                throw new RuntimeException('Select one of your courses.');
            }

            $recipientIds = teacher_course_student_ids((int) $user['id'], $courseId);
            $linkUrl = app_url('student/courses.php?course_id=' . $courseId);
        } else {
            $recipientIds = teacher_course_student_ids((int) $user['id']);
        }

        if (!$recipientIds) {
            throw new RuntimeException('No enrolled students were found for this announcement.');
        }

        foreach (array_unique($recipientIds) as $recipientId) {
            create_notification(
                (int) $recipientId,
                $title,
                $body,
                $linkUrl,
                'announcement',
                (int) $user['id']
            );
        }

        $message = 'Announcement sent to ' . count(array_unique($recipientIds)) . ' students.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$announcementsStmt = db()->prepare(
    "SELECT n.title, n.body, n.link_url, MAX(n.created_at) AS sent_at, COUNT(*) AS recipient_count
     FROM notifications n
     WHERE n.sender_user_id = ? AND n.category = 'announcement'
     GROUP BY n.title, n.body, n.link_url, DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i')
     ORDER BY sent_at DESC
     LIMIT 8"
);
$announcementsStmt->execute([$user['id']]);
$announcements = $announcementsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Announcements</h1>
        <p class="muted">Broadcast notices to your students and have them surface in their notification feed.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="grid">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <h2>Send Announcement</h2>
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
        <label for="title">Title</label>
        <input id="title" name="title" maxlength="160" required>
        <label for="body">Announcement</label>
        <textarea id="body" name="body" placeholder="Write a clear update for your students." required></textarea>
        <button class="btn" type="submit">Publish Announcement</button>
    </form>

    <div class="table-card">
        <h2>Recent Broadcasts</h2>
        <table>
            <tr>
                <th>Title</th>
                <th>Sent</th>
                <th>Recipients</th>
                <th>Message</th>
            </tr>
            <?php foreach ($announcements as $announcement): ?>
                <tr>
                    <td><?= e($announcement['title']) ?></td>
                    <td><?= e((string) $announcement['sent_at']) ?></td>
                    <td><?= (int) $announcement['recipient_count'] ?></td>
                    <td><?= e($announcement['body']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$announcements): ?>
                <tr>
                    <td colspan="4" class="muted">No announcements sent yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
