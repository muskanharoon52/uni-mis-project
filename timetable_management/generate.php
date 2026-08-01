<?php
$ttActive = 'generate';
$pageTitle = 'Generate Timetable';
require_once __DIR__ . '/_common.php';

$f_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$f_program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$f_session = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$f_semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$f_section = isset($_GET['section']) ? trim($_GET['section']) : '';

// Courses available for the chosen program
$program_courses = [];
if ($f_program > 0) {
    $stmt = mysqli_prepare($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name,''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.program_id = ? AND c.status = 'Active' ORDER BY c.course_code");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $f_program);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $program_courses[] = $row; }
        mysqli_stmt_close($stmt);
    }
}

// =============================================
// HANDLE SAVE (validate all, then insert)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = (int)($_POST['program_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $section = strtoupper(trim($_POST['section'] ?? ''));
    $dept_id = (int)($_POST['dept_id'] ?? 0);

    $rows = [];
    $course_ids = isset($_POST['course_ids']) ? (array)$_POST['course_ids'] : [];
    $teacher_ids = isset($_POST['teacher_ids']) ? (array)$_POST['teacher_ids'] : [];
    $days = isset($_POST['days']) ? (array)$_POST['days'] : [];
    $starts = isset($_POST['starts']) ? (array)$_POST['starts'] : [];
    $ends = isset($_POST['ends']) ? (array)$_POST['ends'] : [];
    $room_ids = isset($_POST['room_ids']) ? (array)$_POST['room_ids'] : [];

    if ($program_id <= 0 || $session_id <= 0 || $semester_id <= 0 || $section === '') {
        $error = "Please select program, session, semester and section before saving.";
    } else {
        $count = count($course_ids);
        for ($i = 0; $i < $count; $i++) {
            if (empty($course_ids[$i])) continue;
            $rows[] = [
                'course_id' => (int)$course_ids[$i],
                'teacher_id' => isset($teacher_ids[$i]) ? (int)$teacher_ids[$i] : 0,
                'day' => isset($days[$i]) ? $days[$i] : '',
                'start' => isset($starts[$i]) ? $starts[$i] : '',
                'end' => isset($ends[$i]) ? $ends[$i] : '',
                'room_id' => isset($room_ids[$i]) ? (int)$room_ids[$i] : 0,
            ];
        }

        if (empty($rows)) {
            $error = "No courses were provided for the timetable.";
        } else {
            // Incomplete rows
            $incomplete = [];
            foreach ($rows as $idx => $r) {
                if ($r['day'] === '' || $r['start'] === '' || $r['end'] === '' || $r['room_id'] <= 0 || $r['teacher_id'] <= 0) {
                    $incomplete[] = $idx + 1;
                }
            }
            if (!empty($incomplete)) {
                $error = "Row(s) " . implode(', ', $incomplete) . " are incomplete. Every course needs a day, start time, end time, room and teacher.";
            } else {
                // Find existing timetable (create if needed)
                $tt = tt_find_timetable($conn, $program_id, $session_id, $semester_id, $section);
                if (!$tt) {
                    $ins = mysqli_prepare($conn, "INSERT INTO timetables (department_id, program_id, session_id, semester_id, section, status, created_by) VALUES (?, ?, ?, ?, ?, 'Draft', ?)");
                    if ($ins) {
                        $by = (int)($_SESSION['user_id'] ?? 0);
                        mysqli_stmt_bind_param($ins, 'iiiisi', $dept_id, $program_id, $session_id, $semester_id, $section, $by);
                        mysqli_stmt_execute($ins);
                        $timetable_id = mysqli_insert_id($conn);
                        mysqli_stmt_close($ins);
                    } else {
                        $timetable_id = 0;
                    }
                } else {
                    $timetable_id = (int)$tt['id'];
                }

                if (!$timetable_id) {
                    $error = "Could not create timetable record.";
                } else {
                    // Validate ALL rows against each other + DB before inserting
                    $all_conflicts = [];
                    $seen = [];
                    foreach ($rows as $r) {
                        // Compare against DB
                        $db_conflicts = tt_check_conflicts($conn, $timetable_id, $r['course_id'], $r['teacher_id'], $r['day'], $r['start'], $r['end'], $r['room_id']);
                        foreach ($db_conflicts as $c) { $all_conflicts[] = $c; }

                        // Compare against proposed rows (intra-batch)
                        foreach ($seen as $s) {
                            if ($s['day'] !== $r['day'] || !tt_overlaps($r['start'], $r['end'], $s['start'], $s['end'])) continue;
                            if ((int)$s['room_id'] === (int)$r['room_id']) {
                                $all_conflicts[] = ['type' => 'Room', 'desc' => "Room already occupied at this time within this batch."];
                            }
                            if ($s['teacher_id'] && $r['teacher_id'] && (int)$s['teacher_id'] === (int)$r['teacher_id']) {
                                $all_conflicts[] = ['type' => 'Teacher', 'desc' => "Same teacher assigned to two classes in this batch at the same time."];
                            }
                            if ((int)$s['course_id'] !== (int)$r['course_id']) {
                                $all_conflicts[] = ['type' => 'Student', 'desc' => "Student section has two courses at the same time in this batch."];
                            }
                        }
                        $seen[] = $r;
                    }

                    if (!empty($all_conflicts)) {
                        $error = "Timetable NOT saved. The following conflicts were detected:<ul>";
                        foreach ($all_conflicts as $c) {
                            $error .= "<li><span class='badge bg-warning text-dark'>" . htmlspecialchars($c['type']) . "</span> " . htmlspecialchars($c['desc']) . "</li>";
                        }
                        $error .= "</ul>";
                    } else {
                        // All clear: insert courses + entries
                        $by = (int)($_SESSION['user_id'] ?? 0);
                        $course_inserted = 0;
                        $entry_inserted = 0;
                        $alloc_inserted = 0;
                        foreach ($rows as $r) {
                            // timetable_courses
                            $chk = mysqli_query($conn, "SELECT id FROM timetable_courses WHERE timetable_id = $timetable_id AND course_id = " . $r['course_id']);
                            if (!($chk && mysqli_num_rows($chk) > 0)) {
                                $cq = mysqli_query($conn, "SELECT credit_hours FROM courses WHERE course_id = " . $r['course_id']);
                                $cr = $cq ? (int)(mysqli_fetch_assoc($cq)['credit_hours'] ?? 3) : 3;
                                $cins = mysqli_prepare($conn, "INSERT INTO timetable_courses (timetable_id, course_id, credit_hours) VALUES (?, ?, ?)");
                                if ($cins) {
                                    mysqli_stmt_bind_param($cins, 'iii', $timetable_id, $r['course_id'], $cr);
                                    mysqli_stmt_execute($cins);
                                    mysqli_stmt_close($cins);
                                    $course_inserted++;
                                }
                            }
                            // timetable_entries
                            $eins = mysqli_prepare($conn, "INSERT INTO timetable_entries (timetable_id, course_id, teacher_id, day_of_week, start_time, end_time, room_id, section, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)");
                            if ($eins) {
                                mysqli_stmt_bind_param($eins, 'iiisssisi', $timetable_id, $r['course_id'], $r['teacher_id'], $r['day'], $r['start'], $r['end'], $r['room_id'], $section, $by);
                                mysqli_stmt_execute($eins);
                                $entry_id = mysqli_insert_id($conn);
                                mysqli_stmt_close($eins);
                                $entry_inserted++;
                                // room_allocations
                                $ains = mysqli_prepare($conn, "INSERT INTO room_allocations (room_id, entry_id, day_of_week, start_time, end_time, allocated_by) VALUES (?, ?, ?, ?, ?, ?)");
                                if ($ains) {
                                    mysqli_stmt_bind_param($ains, 'iisssi', $r['room_id'], $entry_id, $r['day'], $r['start'], $r['end'], $by);
                                    mysqli_stmt_execute($ains);
                                    mysqli_stmt_close($ains);
                                    $alloc_inserted++;
                                }
                            }
                        }
                        $success = "Timetable generated successfully: $course_inserted course(s), $entry_inserted slot(s), $alloc_inserted room allocation(s) recorded. Status: Draft.";
                        log_activity('Timetable Management', 'Generate Timetable', 'timetables', $timetable_id, "Program #$program_id / Session #$session_id / Semester #$semester_id / Section $section — $entry_inserted slots, $course_inserted courses");
                        // reset filters to reload new timetable
                        $f_program = $program_id; $f_session = $session_id; $f_semester = $semester_id; $f_section = $section;
                    }
                }
            }
        }
    }
}

// Reload courses for repopulation after POST
if ($f_program > 0) {
    $stmt = mysqli_prepare($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name,''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.program_id = ? AND c.status = 'Active' ORDER BY c.course_code");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $f_program);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $program_courses = [];
        while ($row = mysqli_fetch_assoc($res)) { $program_courses[] = $row; }
        mysqli_stmt_close($stmt);
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-calendar-plus"></i> Generate Timetable</h2>
            <p class="text-muted mb-0">Select department, program, session, semester and section, assign courses with day/time/room/teacher, then validate before saving.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Step 1: Select filters -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="dept" id="gen_dept" class="form-select">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['department_id']; ?>" <?= $f_dept == $d['department_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($d['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="program" id="gen_program" class="form-select">
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
                        <option value="">Section</option>
                        <?php foreach ($section_letters as $letter): ?>
                            <option value="<?= $letter; ?>" <?= $f_section === $letter ? 'selected' : ''; ?>>Section <?= $letter; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Load Courses</button>
                </div>
            </form>
        </div>

        <?php if ($f_program > 0 && $f_session > 0 && $f_semester > 0 && $f_section !== ''): ?>
        <!-- Step 2: Build timetable -->
        <form method="POST" id="ttForm" class="mt-3">
            <input type="hidden" name="program_id" value="<?= (int)$f_program; ?>">
            <input type="hidden" name="session_id" value="<?= (int)$f_session; ?>">
            <input type="hidden" name="semester_id" value="<?= (int)$f_semester; ?>">
            <input type="hidden" name="section" value="<?= htmlspecialchars($f_section); ?>">
            <input type="hidden" name="dept_id" value="<?= (int)$f_dept; ?>">

            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h5 class="m-0"><i class="fas fa-book"></i> Add Courses &amp; Assign Schedule</h5>
                    <span class="text-muted small">Every row must have day, start, end, room and teacher.</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Add Course</label>
                        <div class="d-flex gap-2">
                            <select id="course_picker" class="form-select">
                                <option value="">Select Course</option>
                                <?php foreach ($program_courses as $c): ?>
                                    <option value="<?= (int)$c['course_id']; ?>" data-code="<?= htmlspecialchars($c['course_code']); ?>" data-name="<?= htmlspecialchars($c['course_name']); ?>" data-credits="<?= (int)$c['credit_hours']; ?>"><?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="addCourseRow()"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="ttRows">
                        <thead>
                            <tr>
                                <th style="width:22%;">Course</th>
                                <th>Day</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Room</th>
                                <th>Teacher</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" onclick="return validateTtRows()">
                        <i class="fas fa-check-circle"></i> Validate &amp; Save Timetable
                    </button>
                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="panel mt-3">
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <h5>Select Filters to Start</h5>
                    <p class="text-muted">Choose a department, program, session, semester and section, then click Load Courses to begin generating the timetable.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/_tt_js.php'; ?>

<script>
const COURSES = <?= json_encode($program_courses); ?>;
const TEACHERS = <?= json_encode(array_map(function($t){ return ['id'=>(int)$t['teacher_id'],'name'=>$t['teacher_name']]; }, $teachers)); ?>;
const ROOMS = <?= json_encode(array_map(function($r){ return ['id'=>(int)$r['id'],'no'=>$r['room_no']]; }, $rooms)); ?>;
const DAYS = <?= json_encode($days_of_week); ?>;
const SLOTS = <?= json_encode(array_keys($time_slots)); ?>;

function optsFor(list, valKey, labelKey, valueAttr) {
    return list.map(function(item) {
        return '<option value="' + item[valKey] + '">' + item[labelKey] + '</option>';
    }).join('');
}

function addCourseRow() {
    const picker = document.getElementById('course_picker');
    const cid = picker.value;
    if (!cid) { alert('Select a course first.'); return; }
    const opt = picker.options[picker.selectedIndex];
    const tbody = document.querySelector('#ttRows tbody');
    const tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="hidden" name="course_ids[]" value="' + cid + '"><strong>' + opt.dataset.code + '</strong><br><small class="text-muted">' + opt.dataset.name + ' (' + opt.dataset.credits + ' cr)</small></td>' +
        '<td><select name="days[]" class="form-select form-select-sm">' + optsFor(DAYS.map(function(d){return {id:d,name:d};}), 'id', 'name') + '</select></td>' +
        '<td><select name="starts[]" class="form-select form-select-sm">' + optsFor(SLOTS.map(function(s){return {id:s,name:s};}), 'id', 'name') + '</select></td>' +
        '<td><select name="ends[]" class="form-select form-select-sm">' + optsFor(SLOTS.map(function(s){return {id:s,name:s};}), 'id', 'name') + '</select></td>' +
        '<td><select name="room_ids[]" class="form-select form-select-sm">' + optsFor(ROOMS, 'id', 'no') + '</select></td>' +
        '<td><select name="teacher_ids[]" class="form-select form-select-sm">' + optsFor(TEACHERS, 'id', 'name') + '</select></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="fas fa-trash"></i></button></td>';
    tbody.appendChild(tr);
}

function validateTtRows() {
    const rows = document.querySelectorAll('#ttRows tbody tr');
    if (rows.length === 0) { alert('Add at least one course to the timetable.'); return false; }
    let ok = true;
    rows.forEach(function(tr) {
        const sel = tr.querySelectorAll('select');
        if (!sel[0].value || !sel[1].value || !sel[2].value || !sel[3].value || !sel[4].value) { ok = false; }
        if (sel[1].value && sel[2].value && sel[2].value <= sel[1].value) { ok = false; }
    });
    if (!ok) { alert('Please complete every row and make sure end time is after start time.'); return false; }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
