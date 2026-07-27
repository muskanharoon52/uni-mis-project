<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'courses';
$pageTitle = 'Courses';

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'course_code';
$dir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$sortColumns = ['course_code', 'course_title', 'credit_hours', 'teacher_name'];
if (!in_array($sort, $sortColumns)) $sort = 'course_code';
$nextDir = $dir === 'ASC' ? 'DESC' : 'ASC';
$oppositeDir = $dir === 'ASC' ? 'ASC' : 'DESC';

$conditions = ['e.student_user_id = ?'];
$params = [(int) $user['id']];

if ($search !== '') {
    $conditions[] = '(c.course_code LIKE ? OR c.course_title LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sortSql = match($sort) {
    'course_title' => "c.course_title $dir",
    'credit_hours' => "c.credit_hours $dir",
    'teacher_name' => "te.teacher_name $dir",
    default => "c.course_code $dir",
};

$where = implode(' AND ', $conditions);

$countStmt = db()->prepare("SELECT COUNT(*) FROM lms_enrollments e JOIN courses c ON c.course_id = e.course_id LEFT JOIN teachers te ON te.teacher_id = c.teacher_id WHERE $where");
$countStmt->execute($params);
$totalCourses = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalCourses / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.*, te.teacher_name, te.email AS teacher_email, u.email AS user_email
        FROM lms_enrollments e
        JOIN courses c ON c.course_id = e.course_id
        LEFT JOIN teachers te ON te.teacher_id = c.teacher_id
        LEFT JOIN users u ON u.user_id = te.user_id
        WHERE $where
        ORDER BY $sortSql
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$emailCache = [];
$teacherIds = array_filter(array_column($courses, 'teacher_id'));
if (!empty($teacherIds)) {
    $pl = implode(',', array_fill(0, count($teacherIds), '?'));
    $eStmt = db()->prepare("SELECT u.email, te.teacher_id FROM users u JOIN teachers te ON te.user_id = u.user_id WHERE te.teacher_id IN ($pl)");
    $eStmt->execute(array_values($teacherIds));
    foreach ($eStmt->fetchAll() as $row) $emailCache[$row['teacher_id']] = $row['email'];
}

$courseScheduleCache = [];
$courseIds = array_column($courses, 'course_id');
if (!empty($courseIds)) {
    $pl = implode(',', array_fill(0, count($courseIds), '?'));
    $sStmt = db()->prepare("SELECT course_id, GROUP_CONCAT(CONCAT(day_of_week, ' ', SUBSTRING(start_time,1,5), '-', SUBSTRING(end_time,1,5)) ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time SEPARATOR ', ') AS schedule FROM timetable WHERE course_id IN ($pl) GROUP BY course_id");
    $sStmt->execute($courseIds);
    foreach ($sStmt->fetchAll() as $row) $courseScheduleCache[$row['course_id']] = $row['schedule'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="courses-page">
    <div class="courses-header">
        <p class="courses-subtitle">Spring 2026 &middot; <?= $totalCourses ?> course<?= $totalCourses !== 1 ? 's' : '' ?> enrolled</p>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('search-input').value='';document.getElementById('filter-form').submit();">Reset</button>
    </div>

    <div class="courses-toolbar">
        <form id="filter-form" method="GET" class="filter-row">
            <div class="search-wrapper">
                <span class="search-icon">&#128269;</span>
                <input id="search-input" name="search" type="text" placeholder="Search courses..." value="<?= e($search) ?>" class="search-input">
            </div>
            <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="course_code" <?= $sort === 'course_code' ? 'selected' : '' ?>>Sort by Code</option>
                <option value="course_title" <?= $sort === 'course_title' ? 'selected' : '' ?>>Sort by Title</option>
                <option value="credit_hours" <?= $sort === 'credit_hours' ? 'selected' : '' ?>>Sort by Credits</option>
                <option value="teacher_name" <?= $sort === 'teacher_name' ? 'selected' : '' ?>>Sort by Teacher</option>
            </select>
            <input type="hidden" name="dir" value="<?= e($oppositeDir) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </form>
    </div>

    <?php if (empty($courses)): ?>
        <div class="card courses-empty">
            <div class="empty-icon">&#128218;</div>
            <h3>No courses found</h3>
            <p class="muted"><?= $search ? 'No courses match your search criteria.' : 'You are not enrolled in any courses this semester.' ?></p>
        </div>
    <?php else: ?>
        <div class="card courses-card">
            <div class="table-responsive">
                <table class="courses-table">
                    <thead>
                        <tr>
                            <th><a href="?sort=course_code&dir=<?= $sort === 'course_code' ? e($nextDir) : 'ASC' ?>&search=<?= e($search) ?>" class="sort-link">Course ID <?= $sort === 'course_code' ? ($dir === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a></th>
                            <th><a href="?sort=course_title&dir=<?= $sort === 'course_title' ? e($nextDir) : 'ASC' ?>&search=<?= e($search) ?>" class="sort-link">Course Title <?= $sort === 'course_title' ? ($dir === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a></th>
                            <th><a href="?sort=credit_hours&dir=<?= $sort === 'credit_hours' ? e($nextDir) : 'ASC' ?>&search=<?= e($search) ?>" class="sort-link">Credits <?= $sort === 'credit_hours' ? ($dir === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a></th>
                            <th><a href="?sort=teacher_name&dir=<?= $sort === 'teacher_name' ? e($nextDir) : 'ASC' ?>&search=<?= e($search) ?>" class="sort-link">Teacher <?= $sort === 'teacher_name' ? ($dir === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a></th>
                            <th>Schedule</th>
                            <th class="actions-col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <?php
                            $schedule = $courseScheduleCache[$course['course_id']] ?? 'TBA';
                            $teacherEmail = $emailCache[$course['teacher_id']] ?? $course['user_email'] ?? '';
                            $detailUrl = app_url('student/course_detail.php?id=' . (int) $course['course_id']);
                            ?>
                            <tr class="course-row" data-href="<?= $detailUrl ?>">
                                <td><a href="<?= $detailUrl ?>" class="course-id-link"><?= e($course['course_code']) ?></a></td>
                                <td class="course-title-cell"><?= e($course['course_title']) ?></td>
                                <td><?= (int) $course['credit_hours'] ?></td>
                                <td>
                                    <div class="teacher-info">
                                        <span class="teacher-name"><?= e($course['teacher_name'] ?? 'N/A') ?></span>
                                        <?php if ($teacherEmail): ?>
                                            <span class="teacher-email"><?= e($teacherEmail) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="schedule-cell"><?= e($schedule) ?></td>
                                <td class="actions-col">
                                    <div class="dropdown">
                                        <button class="dot-menu" onclick="event.stopPropagation();this.nextElementSibling.classList.toggle('show')">&#8942;</button>
                                        <div class="dropdown-menu">
                                            <a href="<?= $detailUrl ?>">View Course</a>
                                            <a href="<?= $detailUrl ?>?tab=materials">Materials</a>
                                            <a href="<?= $detailUrl ?>?tab=assignments">Assignments</a>
                                            <a href="<?= app_url('student/attendance.php?course_id=' . (int) $course['course_id']) ?>">Attendance</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">Page <?= $page ?> of <?= $totalPages ?></div>
                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>&dir=<?= e($dir) ?>&search=<?= e($search) ?>" class="page-link">&laquo;</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&sort=<?= e($sort) ?>&dir=<?= e($dir) ?>&search=<?= e($search) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>&dir=<?= e($dir) ?>&search=<?= e($search) ?>" class="page-link">&raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.courses-page { max-width: 1240px; }
.courses-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.courses-subtitle { font-size: 13px; color: #64748b; margin: 0; }
.courses-toolbar { margin-bottom: 20px; }
.filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.search-wrapper { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: #94a3b8; pointer-events: none; }
.search-input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit; font-size: 14px; color: #1f2937; background: #fff; outline: none; }
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.search-input::placeholder { color: #9ca3af; }
.filter-select { padding: 9px 14px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit; font-size: 13px; color: #374151; background: #fff; outline: none; cursor: pointer; min-width: 140px; }
.filter-select:focus { border-color: #3b82f6; }
.courses-card { padding: 0; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
.courses-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.courses-table thead th { background: #f8f9fa; padding: 14px 16px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.courses-table tbody td { padding: 16px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.courses-table tbody tr:last-child td { border-bottom: none; }
.courses-table tbody tr { transition: background .12s; cursor: pointer; }
.courses-table tbody tr:hover { background: #f8fafc; }
.sort-link { color: #374151; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; }
.sort-link:hover { color: #3b82f6; }
.course-id-link { color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 13px; }
.course-id-link:hover { text-decoration: underline; }
.course-title-cell { font-weight: 500; color: #0f172a; }
.teacher-info { display: flex; flex-direction: column; gap: 1px; }
.teacher-name { font-weight: 500; color: #1f2937; font-size: 13px; }
.teacher-email { font-size: 11px; color: #9ca3af; }
.schedule-cell { font-size: 12px; color: #4b5563; max-width: 260px; line-height: 1.5; }
.actions-col { width: 40px; text-align: center; }
.dropdown { position: relative; display: inline-block; }
.dot-menu { background: none; border: none; font-size: 18px; color: #9ca3af; cursor: pointer; padding: 4px 8px; border-radius: 4px; line-height: 1; }
.dot-menu:hover { background: #f3f4f6; color: #374151; }
.dropdown-menu { display: none; position: absolute; right: 0; top: 100%; z-index: 50; min-width: 160px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 4px 0; }
.dropdown-menu.show { display: block; }
.dropdown-menu a { display: block; padding: 8px 14px; font-size: 13px; color: #374151; text-decoration: none; }
.dropdown-menu a:hover { background: #f8fafc; color: #3b82f6; }
.pagination { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-top: 1px solid #e5e7eb; background: #fafafa; }
.pagination-info { font-size: 12px; color: #6b7280; }
.pagination-links { display: flex; gap: 4px; }
.page-link { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 12px; font-weight: 500; color: #374151; text-decoration: none; }
.page-link:hover { background: #f3f4f6; border-color: #d1d5db; }
.page-link.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.courses-empty { text-align: center; padding: 60px 40px; }
.empty-icon { font-size: 48px; margin-bottom: 12px; }
.courses-empty h3 { margin: 0 0 8px; font-size: 18px; color: #0f172a; }
.courses-empty p { margin: 0; }
@media (max-width: 900px) {
    .courses-table thead th:nth-child(5),
    .courses-table tbody td:nth-child(5) { display: none; }
}
@media (max-width: 640px) {
    .courses-table thead th:nth-child(3),
    .courses-table tbody td:nth-child(3) { display: none; }
    .filter-row { flex-direction: column; align-items: stretch; }
    .search-wrapper { max-width: none; }
    .pagination { flex-direction: column; gap: 10px; text-align: center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.course-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('a') || e.target.closest('.dropdown') || e.target.closest('.dot-menu')) return;
            var href = this.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>