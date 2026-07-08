<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'dashboard';
$pageTitle = 'Teacher Dashboard';
$now = new DateTimeImmutable('now');
$semester = 'Spring 2026';
$academicYear = '2025-2026';
$teacherCode = 'TCH-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT);
$initials = strtoupper(substr((string) $user['name'], 0, 1));

$courseStmt = db()->prepare(
    'SELECT c.*,
        COUNT(DISTINCT e.student_id) AS student_count,
        COUNT(DISTINCT a.id) AS assignment_count,
        COUNT(DISTINCT l.id) AS lecture_count
     FROM courses c
     LEFT JOIN enrollments e ON e.course_id = c.id
     LEFT JOIN assignments a ON a.course_id = c.id
     LEFT JOIN lectures l ON l.course_id = c.id
     WHERE c.teacher_id = ?
     GROUP BY c.id
     ORDER BY c.code'
);
$courseStmt->execute([$user['id']]);
$courses = $courseStmt->fetchAll();

$courseCount = count($courses);
$studentCount = 0;
foreach ($courses as $course) {
    $studentCount += (int) $course['student_count'];
}

$pendingStmt = db()->prepare(
    'SELECT COUNT(*)
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     WHERE c.teacher_id = ? AND s.grade IS NULL'
);
$pendingStmt->execute([$user['id']]);
$pendingSubmissions = (int) $pendingStmt->fetchColumn();

$attendanceStmt = db()->prepare(
    'SELECT COUNT(a.id) AS total_records,
        SUM(a.status IN ("present", "late")) AS present_records
     FROM attendance a
     JOIN courses c ON c.id = a.course_id
     WHERE c.teacher_id = ?'
);
$attendanceStmt->execute([$user['id']]);
$attendanceSummary = $attendanceStmt->fetch() ?: ['total_records' => 0, 'present_records' => 0];
$totalAttendance = (int) $attendanceSummary['total_records'];
$presentAttendance = (int) $attendanceSummary['present_records'];
$averageAttendance = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 0;

$activityStmt = db()->prepare(
    'SELECT u.name AS student_name, c.code, s.submitted_at AS activity_time, "Assignment submitted" AS activity
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     JOIN users u ON u.id = s.student_id
     WHERE c.teacher_id = ?
     ORDER BY s.submitted_at DESC
     LIMIT 6'
);
$activityStmt->execute([$user['id']]);
$studentActivities = $activityStmt->fetchAll();

$schedule = [];
$timeSlots = ['09:00 AM', '10:30 AM', '12:00 PM', '02:00 PM'];
foreach (array_slice($courses, 0, 4) as $index => $course) {
    $schedule[] = [
        'time' => $timeSlots[$index] ?? '03:30 PM',
        'course' => $course['title'],
        'code' => $course['code'],
        'section' => 'A',
        'room' => $index % 2 === 0 ? 'Room 204' : 'Lab 3',
        'status' => $index === 0 ? 'Ongoing' : ($index < 2 ? 'Upcoming' : 'Completed'),
    ];
}

$calendarEvents = [
    ['date' => 'Jul 20', 'title' => 'Assignment deadline', 'type' => 'High'],
    ['date' => 'Jul 25', 'title' => 'Quiz schedule review', 'type' => 'Medium'],
    ['date' => 'Aug 02', 'title' => 'Department meeting', 'type' => 'Low'],
    ['date' => 'Aug 12', 'title' => 'Midterm exam window', 'type' => 'High'],
];

$greetingHour = (int) $now->format('G');
$greeting = $greetingHour < 12 ? 'Good Morning' : ($greetingHour < 17 ? 'Good Afternoon' : 'Good Evening');

require_once __DIR__ . '/../includes/header.php';
?>
<section class="faculty-dashboard">
    <div class="faculty-hero">
        <div>
            <p class="faculty-kicker"><?= e($semester) ?> | Academic Year <?= e($academicYear) ?></p>
            <h1><?= e($greeting . ', ' . $user['name']) ?></h1>
            <p class="muted"><?= e($now->format('l, F j, Y h:i A')) ?></p>
        </div>
        <form class="faculty-search" method="get">
            <input name="q" placeholder="Search courses or students">
        </form>
        <details class="faculty-profile-menu">
            <summary>
                <?php if ($user['profile_photo']): ?>
                    <img class="profile-photo-sm" src="<?= app_url($user['profile_photo']) ?>" alt="">
                <?php else: ?>
                    <span class="faculty-avatar"><?= e($initials) ?></span>
                <?php endif; ?>
                <span><?= e($teacherCode) ?></span>
            </summary>
            <div>
                <a href="<?= app_url('teacher/profile.php') ?>">Profile</a>
                <a href="<?= app_url('public/assets/logout.php') ?>">Logout</a>
            </div>
        </details>
    </div>

    <div class="faculty-kpis">
        <div class="faculty-kpi"><span>Students</span><strong><?= $studentCount ?></strong><small>Total enrolled</small></div>
        <div class="faculty-kpi"><span>Courses</span><strong><?= $courseCount ?></strong><small>Active courses</small></div>
        <div class="faculty-kpi"><span>Classes</span><strong><?= count($schedule) ?></strong><small>Scheduled today</small></div>
        <div class="faculty-kpi"><span>Submissions</span><strong><?= $pendingSubmissions ?></strong><small>Awaiting grading</small></div>
        <div class="faculty-kpi"><span>Attendance</span><strong><?= $averageAttendance ?>%</strong><small>Overall average</small></div>
    </div>

    <section class="faculty-card">
        <header><h2>Today's Schedule</h2><a href="<?= app_url('teacher/attendance.php') ?>">Attendance</a></header>
        <div class="faculty-schedule">
            <?php foreach ($schedule as $item): ?>
                <div class="faculty-schedule-row <?= $item['status'] === 'Ongoing' ? 'is-active' : '' ?>">
                    <span><?= e($item['time']) ?></span>
                    <strong><?= e($item['course']) ?><small><?= e($item['code'] . ' | Section ' . $item['section']) ?></small></strong>
                    <span><?= e($item['room']) ?></span>
                    <em class="faculty-status <?= strtolower($item['status']) ?>"><?= e($item['status']) ?></em>
                    <a class="faculty-mini-action" href="<?= app_url('teacher/attendance.php') ?>">Mark</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$schedule): ?><p class="muted">No courses assigned yet.</p><?php endif; ?>
        </div>
    </section>

    <section class="faculty-card">
        <header><h2>My Courses</h2><a href="<?= app_url('teacher/courses.php') ?>">Manage courses</a></header>
        <div class="faculty-table-wrap">
            <table class="faculty-course-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Semester</th>
                        <th>Credit Hours</th>
                        <th>Students</th>
                        <th>Assignments</th>
                        <th>Lectures</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><strong><?= e($course['title']) ?></strong></td>
                            <td><?= e($course['code']) ?></td>
                            <td><?= e($course['semester']) ?></td>
                            <td><?= (int) $course['credit_hours'] ?></td>
                            <td><?= (int) $course['student_count'] ?></td>
                            <td><?= (int) $course['assignment_count'] ?></td>
                            <td><?= (int) $course['lecture_count'] ?></td>
                            <td>
                                <div class="faculty-table-actions">
                                    <a href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['id']) ?>">View</a>
                                    <a href="<?= app_url('teacher/attendance.php?course_id=' . (int) $course['id']) ?>">Attendance</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$courses): ?>
                        <tr><td colspan="8" class="muted">No courses assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="faculty-grid">
        <section class="faculty-card">
            <header><h2>Recent Student Activity</h2></header>
            <?php foreach ($studentActivities as $activity): ?>
                <div class="faculty-activity">
                    <strong><?= e($activity['student_name']) ?></strong>
                    <span><?= e($activity['activity'] . ' | ' . $activity['code']) ?></span>
                    <small><?= e($activity['activity_time']) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$studentActivities): ?><p class="muted">No recent student activity yet.</p><?php endif; ?>
        </section>

        <section class="faculty-card">
            <header><h2>Academic Calendar</h2></header>
            <?php foreach ($calendarEvents as $event): ?>
                <a class="faculty-calendar-item" href="<?= app_url('teacher/academic_calendar.php') ?>">
                    <strong><?= e($event['date']) ?></strong>
                    <span><?= e($event['title']) ?></span>
                    <em class="priority <?= strtolower($event['type']) ?>"><?= e($event['type']) ?></em>
                </a>
            <?php endforeach; ?>
        </section>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
