<?php

$pageTitle = $pageTitle ?? APP_NAME;
$active = $active ?? '';
$user = $user ?? current_user();
$role = $user['role'] ?? null;
$brandLabel = $role ? APP_NAME . ' | ' . ucfirst($role) : APP_NAME;
$notificationCount = $user ? unread_notification_count((int) $user['id']) : 0;
$messageCount = $user ? unread_notification_count((int) $user['id'], 'message') : 0;
$notifications = $user ? recent_notifications((int) $user['id'], null, 4) : [];
$messages = $user ? recent_notifications((int) $user['id'], 'message', 4) : [];

$teacherLinks = [
    'dashboard' => ['Dashboard', app_url('teacher/dashboard.php')],
    'courses' => ['My Courses', app_url('teacher/courses.php')],
    'students' => ['Students', app_url('teacher/students.php')],
    'attendance' => ['Attendance', app_url('teacher/attendance.php')],
    'assignments' => ['Assignments', app_url('teacher/assignments.php')],
    'quizzes' => ['Quizzes', app_url('teacher/quizzes.php')],
    'examination' => ['Exams', app_url('teacher/examination.php')],
    'marks' => ['Grades', app_url('teacher/internal_marks.php')],
    'materials' => ['Course Materials', app_url('teacher/course_materials.php')],
    'calendar' => ['Academic Calendar', app_url('teacher/academic_calendar.php')],
    'messages' => ['Messages', app_url('teacher/messages.php')],
    'announcements' => ['Announcements', app_url('teacher/announcements.php')],
    'reports' => ['Reports', app_url('teacher/reports.php')],
    'settings' => ['Settings', app_url('teacher/settings.php')],
    'timetable' => ['Timetable', app_url('teacher/timetable.php')],
];

$studentLinks = [
    'dashboard' => ['Dashboard', app_url('student/dashboard.php')],
    'courses' => ['Courses', app_url('student/courses.php')],
    'attendance' => ['Attendance', app_url('student/attendance.php')],
    'marks' => ['Internal Marks', app_url('student/marks.php')],
    'timetable' => ['Timetable', app_url('student/timetable.php')],
    'examination' => ['Examination', app_url('student/examination.php')],
    'fees' => ['Fees', app_url('student/fees.php')],
    'queries' => ['Queries', app_url('student/queries.php')],
    'applications' => ['Applications', app_url('student/applications.php')],
    'profile' => ['Profile', app_url('student/profile.php')],
];

$links = $role === 'teacher' ? $teacherLinks : ($role === 'student' ? $studentLinks : []);
$examActive = in_array($active, ['examination', 'datesheet', 'transcripts'], true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= app_url('public/index.php') ?>"><?= e($brandLabel) ?></a>
    <?php if ($user): ?>
        <div class="topbar-actions">
            <details class="topbar-popover">
                <summary class="topbar-button">Messages <span class="count-badge"><?= $messageCount ?></span></summary>
                <div class="popover-panel">
                    <div class="popover-head">Messages from teacher</div>
                    <?php if ($messages): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="popover-item">
                                <?php if (!empty($msg['link_url'])): ?>
                                    <a href="<?= app_url($msg['link_url']) ?>">
                                        <strong><?= e($msg['title']) ?></strong>
                                    </a>
                                <?php else: ?>
                                    <strong><?= e($msg['title']) ?></strong>
                                <?php endif; ?>
                                <p><?= e($msg['body']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="popover-empty">No messages yet.</div>
                    <?php endif; ?>
                </div>
            </details>

            <details class="topbar-popover">
                <summary class="topbar-button">Notifications <span class="count-badge"><?= $notificationCount ?></span></summary>
                <div class="popover-panel">
                    <div class="popover-head">
                        <?php if ($role === 'teacher'): ?>
                            Teacher notifications
                        <?php else: ?>
                            Student updates
                        <?php endif; ?>
                    </div>
                    <?php if ($notifications): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="popover-item">
                                <?php if (!empty($notif['link_url'])): ?>
                                    <a href="<?= app_url($notif['link_url']) ?>">
                                        <strong><?= e($notif['title']) ?></strong>
                                    </a>
                                <?php else: ?>
                                    <strong><?= e($notif['title']) ?></strong>
                                <?php endif; ?>
                                <p><?= e($notif['body']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="popover-empty">Nothing new right now.</div>
                    <?php endif; ?>
                    <?php if ($role === 'teacher'): ?>
                        <div class="popover-separator"></div>
                        <a class="btn secondary popover-action" href="<?= app_url('teacher/announcements.php') ?>">Open Announcements</a>
                        <a class="btn secondary popover-action" href="<?= app_url('teacher/messages.php') ?>">Open Messages</a>
                    <?php endif; ?>
                </div>
            </details>

            <?php if (!empty($user['profile_photo'])): ?>
                <img class="profile-photo-sm" src="<?= app_url($user['profile_photo']) ?>" alt="">
            <?php endif; ?>
            <div class="user-pill">
                <span><?= e($user['name']) ?></span>
                <small><?= e(ucfirst($user['role'])) ?></small>
            </div>
        </div>
    <?php endif; ?>
</header>

<?php if ($links): ?>
    <nav class="sidebar">
        <?php foreach ($links as $key => [$label, $href]): ?>
            <?php if ($key === 'examination'): ?>
                <details class="sidebar-group" <?= $examActive ? 'open' : '' ?>>
                    <summary class="sidebar-group-summary <?= $examActive ? 'active' : '' ?>"><?= e($label) ?></summary>
                    <div class="sidebar-subnav">
                        <a class="<?= $active === 'examination' ? 'active' : '' ?>" href="<?= $href ?>"><?= e($label) ?></a>
                        <a class="<?= $active === 'datesheet' ? 'active' : '' ?>" href="<?= app_url($role === 'teacher' ? 'teacher/datesheet.php' : 'student/datesheet.php') ?>">Datesheet</a>
                        <a class="<?= $active === 'transcripts' ? 'active' : '' ?>" href="<?= app_url($role === 'teacher' ? 'teacher/transcripts.php' : 'student/transcripts.php') ?>">Transcripts</a>
                    </div>
                </details>
                <?php continue; ?>
            <?php endif; ?>
            <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= $href ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <a class="sidebar-logout" href="<?= app_url('public/assets/logout.php') ?>">Logout</a>
    </nav>
<?php endif; ?>

<main class="<?= $links ? 'app-main' : 'public-main' ?>">
