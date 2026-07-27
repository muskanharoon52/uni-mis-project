<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'courses';
$pageTitle = 'Course Detail';

$courseId = max(0, (int) ($_GET['id'] ?? 0));
$tab = (string) ($_GET['tab'] ?? 'overview');
$tab = in_array($tab, ['overview', 'materials', 'assignments'], true) ? $tab : 'overview';

$message = '';
$error = '';

$stmt = db()->prepare('SELECT c.*, te.teacher_name, u.email AS teacher_email FROM courses c LEFT JOIN teachers te ON te.teacher_id = c.teacher_id LEFT JOIN users u ON u.user_id = te.user_id WHERE c.course_id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="card" style="text-align:center;padding:60px 40px;"><h2>Course not found</h2><p class="muted">This course does not exist or you may not have access.</p><a href="' . app_url('student/courses.php') . '" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Back to Courses</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$enrolled = db()->prepare('SELECT COUNT(*) FROM lms_enrollments WHERE student_user_id = ? AND course_id = ?');
$enrolled->execute([(int) $user['id'], $courseId]);
if ((int) $enrolled->fetchColumn() === 0) {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="card" style="text-align:center;padding:60px 40px;"><h2>Access Denied</h2><p class="muted">You are not enrolled in this course.</p><a href="' . app_url('student/courses.php') . '" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Back to Courses</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$scheduleStmt = db()->prepare("SELECT GROUP_CONCAT(CONCAT(day_of_week, ' ', SUBSTRING(start_time,1,5), '-', SUBSTRING(end_time,1,5)) ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time SEPARATOR ', ') AS schedule FROM timetable WHERE course_id = ?");
$scheduleStmt->execute([$courseId]);
$schedule = $scheduleStmt->fetchColumn() ?: 'TBA';

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'assignments') {
    try {
        verify_csrf();
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        if (!$assignmentId) throw new RuntimeException('Invalid submission.');

        $allowed = db()->prepare('SELECT COUNT(*) FROM lms_assignments WHERE assignment_id = ? AND course_id = ?');
        $allowed->execute([$assignmentId, $courseId]);
        if ((int) $allowed->fetchColumn() === 0) throw new RuntimeException('Assignment not found.');

        $filePath = save_uploaded_file('submission_file', 'submissions', ['pdf', 'doc', 'docx', 'zip']);
        $stmt = db()->prepare('INSERT INTO lms_submissions (assignment_id, student_user_id, file_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_at = CURRENT_TIMESTAMP');
        $stmt->execute([$assignmentId, (int) $user['id'], $filePath]);
        $message = 'Assignment submitted successfully.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

// Load lectures and assignments
$lectures = [];
$lStmt = db()->prepare('SELECT * FROM lms_lectures WHERE course_id = ? ORDER BY lecture_id DESC');
$lStmt->execute([$courseId]);
$lectures = $lStmt->fetchAll();

$assignments = [];
$aStmt = db()->prepare('SELECT a.*, s.file_path, s.submitted_at, s.marks, s.feedback FROM lms_assignments a LEFT JOIN lms_submissions s ON s.assignment_id = a.assignment_id AND s.student_user_id = ? WHERE a.course_id = ? ORDER BY a.due_date');
$aStmt->execute([(int) $user['id'], $courseId]);
$assignments = $aStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="detail-page">
    <a href="<?= app_url('student/courses.php') ?>" class="back-link">&larr; Back to Courses</a>

    <div class="detail-header">
        <div class="detail-header-left">
            <span class="detail-code"><?= e($course['course_code']) ?></span>
            <h1 class="detail-title"><?= e($course['course_title']) ?></h1>
            <div class="detail-meta">
                <span><strong>Credits:</strong> <?= (int) $course['credit_hours'] ?></span>
                <span><strong>Teacher:</strong> <?= e($course['teacher_name'] ?? 'N/A') ?></span>
                <?php if (!empty($course['teacher_email'])): ?>
                    <span class="meta-email"><?= e($course['teacher_email']) ?></span>
                <?php endif; ?>
                <span><strong>Schedule:</strong> <?= e($schedule) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-tabs">
        <a href="?id=<?= $courseId ?>&tab=overview" class="detail-tab <?= $tab === 'overview' ? 'active' : '' ?>">Overview</a>
        <a href="?id=<?= $courseId ?>&tab=materials" class="detail-tab <?= $tab === 'materials' ? 'active' : '' ?>">Materials</a>
        <a href="?id=<?= $courseId ?>&tab=assignments" class="detail-tab <?= $tab === 'assignments' ? 'active' : '' ?>">Assignments</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($tab === 'overview'): ?>
        <div class="card detail-card">
            <h3>Course Description</h3>
            <p class="detail-desc"><?= e($course['description'] ?: 'No description available for this course.') ?></p>
        </div>

        <div class="card detail-card">
            <h3>Course Outline</h3>
            <?php
            $outline = $course['description'] ? explode('.', $course['description']) : ['Course content will be updated by the instructor.'];
            ?>
            <ul class="outline-list">
                <?php foreach ($outline as $item): ?>
                    <?php $item = trim($item); if ($item): ?>
                        <li><?= e($item) ?>.</li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card detail-card">
            <h3>Quick Stats</h3>
            <div class="stat-row">
                <div class="stat-card-v2"><div class="stat-label">Lectures</div><div class="stat-number"><?= count($lectures) ?></div></div>
                <div class="stat-card-v2"><div class="stat-label">Assignments</div><div class="stat-number"><?= count($assignments) ?></div></div>
                <div class="stat-card-v2"><div class="stat-label">Credit Hours</div><div class="stat-number"><?= (int) $course['credit_hours'] ?></div></div>
            </div>
        </div>

    <?php elseif ($tab === 'materials'): ?>
        <div class="card detail-card">
            <h3>Lecture Materials</h3>
            <?php if (empty($lectures)): ?>
                <p class="muted">No lecture materials uploaded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="detail-table">
                        <thead>
                            <tr><th>Title</th><th>Date</th><th>File</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lectures as $lecture): ?>
                            <tr>
                                <td style="font-weight:500;"><?= e($lecture['title']) ?></td>
                                <td><?= e($lecture['lecture_date'] ?: 'N/A') ?></td>
                                <td><?php if (!empty($lecture['file_path'])): ?><a href="<?= app_url($lecture['file_path']) ?>" target="_blank" class="dl-link">Download</a><?php else: ?><span class="muted">No file</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($tab === 'assignments'): ?>
        <div class="card detail-card">
            <h3>Assignments</h3>
            <?php if (empty($assignments)): ?>
                <p class="muted">No assignments posted for this course.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="detail-table">
                        <thead>
                            <tr><th>Title</th><th>Due Date</th><th>Status</th><th>Marks</th><th>Feedback</th><th>Upload</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $hasSubmission = !empty($assignment['submitted_at']); $isLate = !$hasSubmission && $assignment['due_date'] < date('Y-m-d'); ?>
                            <tr>
                                <td style="font-weight:500;"><?= e($assignment['title']) ?></td>
                                <td><?= e($assignment['due_date']) ?></td>
                                <td>
                                    <?php if ($hasSubmission): ?>
                                        <span class="status-badge status-active">Submitted</span>
                                    <?php elseif ($isLate): ?>
                                        <span class="status-badge status-dropped">Missing</span>
                                    <?php else: ?>
                                        <span class="status-badge status-completed">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $assignment['marks'] !== null ? e((string) $assignment['marks']) : '-' ?></td>
                                <td><?= e($assignment['feedback'] ?: '-') ?></td>
                                <td>
                                    <form method="post" enctype="multipart/form-data" class="upload-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['assignment_id'] ?>">
                                        <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip" required style="font-size:12px;padding:6px;">
                                        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:4px;"><?= $hasSubmission ? 'Update' : 'Submit' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.detail-page { max-width: 960px; }
.back-link { display: inline-block; font-size: 13px; color: #3b82f6; text-decoration: none; margin-bottom: 16px; }
.back-link:hover { text-decoration: underline; }
.detail-header { margin-bottom: 20px; }
.detail-code { display: inline-block; font-size: 12px; font-weight: 600; color: #3b82f6; background: #eef2ff; padding: 4px 10px; border-radius: 4px; margin-bottom: 8px; }
.detail-title { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 10px; }
.detail-meta { display: flex; flex-wrap: wrap; gap: 8px 20px; font-size: 13px; color: #4b5563; }
.detail-meta strong { font-weight: 600; color: #1f2937; }
.meta-email { color: #9ca3af; font-size: 12px; }
.detail-tabs { display: flex; gap: 0; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
.detail-tab { padding: 10px 20px; font-size: 13px; font-weight: 500; color: #6b7280; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all .12s; }
.detail-tab:hover { color: #374151; }
.detail-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; font-weight: 600; }
.detail-card { margin-bottom: 16px; }
.detail-card h3 { font-size: 16px; font-weight: 600; color: #0f172a; margin: 0 0 14px; }
.detail-desc { font-size: 14px; color: #4b5563; line-height: 1.7; margin: 0; }
.outline-list { margin: 0; padding: 0 0 0 18px; font-size: 14px; color: #4b5563; line-height: 2; }
.detail-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.detail-table thead th { background: #f8f9fa; padding: 12px 14px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #374151; border-bottom: 1px solid #e5e7eb; }
.detail-table tbody td { padding: 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.detail-table tbody tr:hover { background: #f8fafc; }
.dl-link { color: #3b82f6; text-decoration: none; font-weight: 500; font-size: 12px; }
.dl-link:hover { text-decoration: underline; }
.upload-form { display: flex; flex-direction: column; gap: 4px; min-width: 150px; }
.upload-form input { margin: 0; font-size: 12px; }
.stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.stat-card-v2 { padding: 18px 20px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; }
.stat-card-v2 .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-bottom: 4px; }
.stat-card-v2 .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; line-height: 1.1; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.status-active { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.status-completed { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
.status-dropped { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
@media (max-width: 640px) {
    .stat-row { grid-template-columns: 1fr; }
    .detail-meta { flex-direction: column; gap: 4px; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>