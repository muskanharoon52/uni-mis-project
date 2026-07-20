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
        COUNT(DISTINCT e.student_user_id) AS student_count,
        COUNT(DISTINCT a.assignment_id) AS assignment_count,
        COUNT(DISTINCT l.id) AS lecture_count
     FROM courses c
     LEFT JOIN lms_enrollments e ON e.course_id = c.course_id
     LEFT JOIN lms_assignments a ON a.course_id = c.course_id
     LEFT JOIN lectures l ON l.course_id = c.course_id
     WHERE c.teacher_id = ?
     GROUP BY c.course_id
     ORDER BY c.course_code'
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
     FROM lms_submissions s
     JOIN lms_assignments a ON a.assignment_id = s.assignment_id
     JOIN courses c ON c.course_id = a.course_id
     WHERE c.teacher_id = ? AND s.grade IS NULL'
);
$pendingStmt->execute([$user['id']]);
$pendingSubmissions = (int) $pendingStmt->fetchColumn();

$attendanceStmt = db()->prepare(
    'SELECT COUNT(a.attendance_id) AS total_records,
        SUM(a.status IN ("Present", "Late")) AS present_records
     FROM attendance a
     JOIN courses c ON c.course_id = a.course_id
     WHERE c.teacher_id = ?'
);
$attendanceStmt->execute([$user['id']]);
$attendanceSummary = $attendanceStmt->fetch() ?: ['total_records' => 0, 'present_records' => 0];
$totalAttendance = (int) $attendanceSummary['total_records'];
$presentAttendance = (int) $attendanceSummary['present_records'];
$averageAttendance = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 0;

$activityStmt = db()->prepare(
    'SELECT u.full_name AS student_name, c.course_code, s.submitted_at AS activity_time, "Assignment submitted" AS activity
     FROM lms_submissions s
     JOIN lms_assignments a ON a.assignment_id = s.assignment_id
     JOIN courses c ON c.course_id = a.course_id
     JOIN users u ON u.user_id = s.student_user_id
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
        'course' => $course['course_title'],
        'code' => $course['course_code'],
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

<div class="greeting-card">
    <div class="greeting-card-body">
        <span class="eyebrow"><?= e($semester) ?> &middot; Academic Year <?= e($academicYear) ?></span>
        <h1><?= e($greeting . ', ' . $user['name']) ?></h1>
        <p class="muted" style="margin-top:4px;"><?= e($now->format('l, F j, Y')) ?> &middot; <?= e($teacherCode) ?></p>
    </div>
    <div class="greeting-card-avatar"><?= e($initials) ?></div>
</div>

<div class="stat-row">
    <div class="stat-card-v2"><div class="stat-label">Students</div><div class="stat-number"><?= $studentCount ?></div><div class="stat-hint">Total enrolled</div></div>
    <div class="stat-card-v2"><div class="stat-label">Courses</div><div class="stat-number"><?= $courseCount ?></div><div class="stat-hint">Active courses</div></div>
    <div class="stat-card-v2"><div class="stat-label">Pending</div><div class="stat-number"><?= $pendingSubmissions ?></div><div class="stat-hint">Submissions to grade</div></div>
    <div class="stat-card-v2"><div class="stat-label">Attendance</div><div class="stat-number"><?= $averageAttendance ?>%</div><div class="stat-hint">Overall average</div></div>
</div>

<div class="action-cards">
    <a class="action-card" href="<?= app_url('teacher/courses.php') ?>">
        <span class="action-card-icon">&#128218;</span>
        <div class="action-card-title">My Courses</div>
        <div class="action-card-desc"><?= $courseCount ?> active course<?= $courseCount !== 1 ? 's' : '' ?></div>
    </a>
    <a class="action-card" href="<?= app_url('teacher/students.php') ?>">
        <span class="action-card-icon">&#128101;</span>
        <div class="action-card-title">Students</div>
        <div class="action-card-desc">View &amp; manage enrolled students</div>
    </a>
    <a class="action-card" href="<?= app_url('teacher/grading.php') ?>">
        <span class="action-card-icon">&#9997;</span>
        <div class="action-card-title">Grading</div>
        <div class="action-card-desc"><?= $pendingSubmissions ?> pending submission<?= $pendingSubmissions !== 1 ? 's' : '' ?></div>
    </a>
    <a class="action-card" href="<?= app_url('teacher/attendance.php') ?>">
        <span class="action-card-icon">&#128197;</span>
        <div class="action-card-title">Attendance</div>
        <div class="action-card-desc">Mark or review attendance</div>
    </a>
    <a class="action-card" href="<?= app_url('teacher/assignments.php') ?>">
        <span class="action-card-icon">&#128221;</span>
        <div class="action-card-title">Assignments</div>
        <div class="action-card-desc">Create &amp; manage assignments</div>
    </a>
    <a class="action-card" href="<?= app_url('teacher/announcements.php') ?>">
        <span class="action-card-icon">&#128227;</span>
        <div class="action-card-title">Announcements</div>
        <div class="action-card-desc">Post class announcements</div>
    </a>
</div>

<?php if ($schedule): ?>
<div class="card mt-4">
    <div class="card-header">
        <h3>Today's Schedule</h3>
        <a class="btn btn-sm btn-outline" href="<?= app_url('teacher/attendance.php') ?>">Attendance</a>
    </div>
    <table class="table">
        <thead>
            <tr><th>Time</th><th>Course</th><th>Section</th><th>Room</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($schedule as $item): ?>
                <tr>
                    <td><strong><?= e($item['time']) ?></strong></td>
                    <td><?= e($item['course']) ?><div class="muted" style="font-size:.75rem;"><?= e($item['code']) ?></div></td>
                    <td><?= e($item['section']) ?></td>
                    <td><?= e($item['room']) ?></td>
                    <td><span class="badge badge-<?= $item['status'] === 'Ongoing' ? 'active' : ($item['status'] === 'Upcoming' ? 'draft' : 'inactive') ?>"><?= e($item['status']) ?></span></td>
                    <td><a class="btn btn-sm btn-outline" href="<?= app_url('teacher/attendance.php') ?>">Mark</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-header">
        <h3>My Courses</h3>
        <a class="btn btn-sm btn-outline" href="<?= app_url('teacher/courses.php') ?>">Manage</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Course</th><th>Code</th><th>Semester</th><th>Credits</th><th>Students</th><th>Assignments</th><th>Lectures</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><strong><?= e($course['course_title']) ?></strong></td>
                        <td><span class="badge badge-outline"><?= e($course['course_code']) ?></span></td>
                        <td><?= e($course['semester_name']) ?></td>
                        <td><?= (int) $course['credit_hours'] ?></td>
                        <td><?= (int) $course['student_count'] ?></td>
                        <td><?= (int) $course['assignment_count'] ?></td>
                        <td><?= (int) $course['lecture_count'] ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['course_id']) ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$courses): ?>
                    <tr><td colspan="8" class="muted" style="text-align:center;">No courses assigned yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2 mt-4">
    <div class="card">
        <div class="card-header"><h3>Recent Activity</h3></div>
        <?php if ($studentActivities): ?>
            <?php foreach ($studentActivities as $activity): ?>
                <div class="activity-row">
                    <span class="activity-dot"></span>
                    <div>
                        <strong><?= e($activity['student_name']) ?></strong>
                        <div class="muted" style="font-size:.75rem;"><?= e($activity['activity'] . ' &middot; ' . $activity['code']) ?></div>
                    </div>
                    <small class="muted" style="margin-left:auto;white-space:nowrap;"><?= e($activity['activity_time']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted" style="padding:1rem;">No recent activity yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><h3>Upcoming</h3></div>
        <?php foreach ($calendarEvents as $event): ?>
            <div class="activity-row">
                <span class="activity-dot" style="background:<?= $event['type'] === 'High' ? 'var(--red)' : ($event['type'] === 'Medium' ? 'var(--amber)' : 'var(--green)') ?>;"></span>
                <div>
                    <strong><?= e($event['title']) ?></strong>
                    <div class="muted" style="font-size:.75rem;"><?= e($event['date']) ?></div>
                </div>
                <span class="badge badge-<?= $event['type'] === 'High' ? 'active' : ($event['type'] === 'Medium' ? 'draft' : 'inactive') ?>" style="margin-left:auto;"><?= e($event['type']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
