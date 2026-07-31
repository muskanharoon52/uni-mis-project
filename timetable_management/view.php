<?php
$ttActive = 'view';
$pageTitle = 'View Timetable';
require_once __DIR__ . '/_common.php';

$view_type = isset($_GET['v']) ? $_GET['v'] : 'section';

// Section / Student view filters
$f_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$f_program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$f_session = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$f_semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$f_section = isset($_GET['section']) ? trim($_GET['section']) : '';

// Teacher view filters
$f_teacher = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;
// Room view filters
$f_room = isset($_GET['room']) ? (int)$_GET['room'] : 0;
// Student search
$f_search = trim($_GET['search'] ?? '');

$entries = tt_all_entries($conn);
$view_rows = [];

if ($view_type === 'section' || $view_type === 'student') {
    $filters = [];
    foreach ($entries as $e) {
        if ($f_program > 0 && (int)$e['program_id'] !== $f_program) continue;
        if ($f_session > 0 && (int)$e['session_id'] !== $f_session) continue;
        if ($f_semester > 0 && (int)$e['semester_id'] !== $f_semester) continue;
        if ($f_section !== '' && $e['tt_section'] !== $f_section) continue;
        $view_rows[] = $e;
    }
    // Student timetable: find student by roll/id then show their section
    if ($view_type === 'student' && $f_search !== '') {
        $student = null;
        $sql = "SELECT s.student_id, s.roll_no, s.full_name, s.program_id, s.current_semester_id, s.section_id,
                       sec.section_name, p.program_name, d.department_name
                FROM students s
                LEFT JOIN sections sec ON sec.section_id = s.section_id
                LEFT JOIN programs p ON p.program_id = s.program_id
                LEFT JOIN departments d ON d.department_id = p.department_id
                WHERE s.roll_no = ? OR CAST(s.student_id AS CHAR) = ? OR s.full_name LIKE ?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $like = "%$f_search%";
            mysqli_stmt_bind_param($stmt, 'sss', $f_search, $f_search, $like);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $student = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }
        if ($student) {
            // Normalize section letter from student's section
            $stu_section = trim(preg_replace('/Section\s*/i', '', $student['section_name'] ?? ''));
            $view_rows = [];
            foreach ($entries as $e) {
                if ((int)$e['program_id'] !== (int)$student['program_id']) continue;
                if (!empty($student['current_session_id']) && (int)$e['session_id'] !== (int)$student['current_session_id']) continue;
                if ((int)$e['semester_id'] !== (int)$student['current_semester_id']) continue;
                if ($stu_section !== '' && $e['tt_section'] !== $stu_section) continue;
                $view_rows[] = $e;
            }
            $view_title = "Student Timetable - " . htmlspecialchars($student['full_name'] . ' (' . $student['roll_no'] . ')');
        } else {
            $error = "No student found for the given search.";
        }
    }
} elseif ($view_type === 'teacher') {
    foreach ($entries as $e) {
        if ($f_teacher > 0 && (int)$e['teacher_id'] !== $f_teacher) continue;
        $view_rows[] = $e;
    }
} elseif ($view_type === 'room') {
    foreach ($entries as $e) {
        if ($f_room > 0 && (int)$e['room_id'] !== $f_room) continue;
        $view_rows[] = $e;
    }
}

// Sort by day then start time
$day_order = array_flip($days_of_week);
usort($view_rows, function ($a, $b) use ($day_order) {
    $da = $day_order[$a['day_of_week']] ?? 0;
    $db = $day_order[$b['day_of_week']] ?? 0;
    if ($da !== $db) return $da <=> $db;
    return strcmp($a['start_time'], $b['start_time']);
});

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-calendar-week"></i> View Timetable</h2>
            <p class="text-muted mb-0">View timetables by section, student, teacher or room.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

        <!-- View type switcher -->
        <div class="panel">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php
                $views = [
                    'section' => ['Student Timetable', 'fas fa-users'],
                    'student' => ['Student (by ID/Roll)', 'fas fa-user-graduate'],
                    'teacher' => ['Teacher Timetable', 'fas fa-chalkboard-teacher'],
                    'room'    => ['Room Timetable', 'fas fa-door-open'],
                ];
                foreach ($views as $vk => $vinfo):
                ?>
                    <a class="btn btn-sm <?= $view_type === $vk ? 'btn-primary' : 'btn-outline-primary' ?>" href="view.php?v=<?= $vk; ?>"><i class="<?= $vinfo[1]; ?>"></i> <?= $vinfo[0]; ?></a>
                <?php endforeach; ?>
            </div>

            <form method="GET" class="row g-3">
                <input type="hidden" name="v" value="<?= htmlspecialchars($view_type); ?>">

                <?php if ($view_type === 'student'): ?>
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Enter Roll No, Student ID, or Name..." value="<?= htmlspecialchars($f_search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Find Timetable</button>
                    </div>
                <?php elseif ($view_type === 'section'): ?>
                    <div class="col-md-3">
                        <select name="program" class="form-select">
                            <option value="0">All Programs</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= (int)$p['program_id']; ?>" <?= $f_program == $p['program_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($p['program_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="session" class="form-select">
                            <option value="0">All Sessions</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= (int)$s['session_id']; ?>" <?= $f_session == $s['session_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['session_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="semester" class="form-select">
                            <option value="0">All Semesters</option>
                            <?php foreach ($semesters as $sm): ?>
                                <option value="<?= (int)$sm['semester_id']; ?>" <?= $f_semester == $sm['semester_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($sm['semester_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="section" class="form-select">
                            <option value="">All Sections</option>
                            <?php foreach ($section_letters as $letter): ?>
                                <option value="<?= $letter; ?>" <?= $f_section === $letter ? 'selected' : ''; ?>>Section <?= $letter; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Timetable</button>
                    </div>
                <?php elseif ($view_type === 'teacher'): ?>
                    <div class="col-md-8">
                        <select name="teacher" class="form-select">
                            <option value="0">All Teachers</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= (int)$t['teacher_id']; ?>" <?= $f_teacher == $t['teacher_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($t['teacher_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Teacher Timetable</button>
                    </div>
                <?php elseif ($view_type === 'room'): ?>
                    <div class="col-md-8">
                        <select name="room" class="form-select">
                            <option value="0">All Rooms</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= (int)$r['id']; ?>" <?= $f_room == $r['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($r['room_no'] . ' (' . $r['room_type'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Room Timetable</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php
        $title = $view_title ?? '';
        if ($title === '' && $view_type === 'section') $title = 'Section Timetable';
        if ($title === '' && $view_type === 'teacher') $title = 'Teacher Timetable';
        if ($title === '' && $view_type === 'room') $title = 'Room Timetable';
        ?>
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-table"></i> <?= $title ?> (<?= count($view_rows); ?> entries)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($view_rows)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Room</th>
                                    <th>Section</th>
                                    <th>Department / Program</th>
                                    <th>Session</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($view_rows as $e): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($e['day_of_week']); ?></span></td>
                                        <td><?= tt_time_ago($e['start_time']); ?> - <?= tt_time_ago($e['end_time']); ?></td>
                                        <td>
                                            <span style="font-weight:600;"><?= htmlspecialchars($e['course_code']); ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($e['course_name']); ?> (<?= (int)$e['credit_hours']; ?> cr)</small>
                                        </td>
                                        <td><?= htmlspecialchars($e['teacher_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($e['room_no'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-info">Section <?= htmlspecialchars($e['tt_section']); ?></span></td>
                                        <td class="muted small"><?= htmlspecialchars($e['department_name'] ?? 'N/A'); ?><br><?= htmlspecialchars($e['program_name'] ?? ''); ?></td>
                                        <td class="muted"><?= htmlspecialchars($e['session_name'] ?? 'N/A'); ?></td>
                                        <td><span class="status-badge <?= $e['entry_status'] === 'Published' ? 'status-active' : 'status-pending'; ?>"><?= htmlspecialchars($e['entry_status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-week"></i>
                        <h5>No Timetable Found</h5>
                        <p class="text-muted">Adjust the filters above to load a timetable.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/_tt_js.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
