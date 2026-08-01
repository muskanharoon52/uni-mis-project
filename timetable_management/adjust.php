<?php
$ttActive = 'adjust';
$pageTitle = 'Timetable Adjustment';
require_once __DIR__ . '/_common.php';

$f_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$focus_entry = isset($_GET['focus']) ? (int)$_GET['focus'] : 0;
$allowed_types = ['all', 'Student', 'Teacher', 'Room'];
if (!in_array($f_type, $allowed_types, true)) { $f_type = 'all'; }

// =============================================
// HANDLE ADJUSTMENT POST
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = (int)($_POST['entry_id'] ?? 0);
    $new_day = $_POST['new_day'] ?? '';
    $new_start = $_POST['new_start'] ?? '';
    $new_end = $_POST['new_end'] ?? '';
    $new_room = (int)($_POST['new_room'] ?? 0);
    $new_teacher = (int)($_POST['new_teacher'] ?? 0);
    $new_section = strtoupper(trim($_POST['new_section'] ?? ''));
    $reason = trim($_POST['reason'] ?? '');

    $eq = mysqli_query($conn, "SELECT * FROM timetable_entries WHERE id = $entry_id");
    $entry = $eq ? mysqli_fetch_assoc($eq) : null;

    if (!$entry) {
        $error = "Timetable entry not found.";
    } elseif ($new_day === '' || $new_start === '' || $new_end === '' || $new_room <= 0 || $new_teacher <= 0) {
        $error = "Please fill day, start time, end time, room and teacher.";
    } elseif ($new_end <= $new_start) {
        $error = "End time must be after start time.";
    } else {
        $conflicts = tt_check_conflicts($conn, $entry['timetable_id'], $entry['course_id'], $new_teacher, $new_day, $new_start, $new_end, $new_room, $entry_id);
        if (!empty($conflicts)) {
            $error = "Adjustment NOT saved. Conflicts detected:<ul>";
            foreach ($conflicts as $c) {
                $error .= "<li><span class='badge bg-warning text-dark'>" . htmlspecialchars($c['type']) . "</span> " . htmlspecialchars($c['desc']) . "</li>";
            }
            $error .= "</ul>";
        } else {
            // Log old/new per field
            $changes = [
                'Day' => [$entry['day_of_week'], $new_day],
                'Start Time' => [$entry['start_time'], $new_start],
                'End Time' => [$entry['end_time'], $new_end],
                'Room' => [$entry['room_id'], $new_room],
                'Teacher' => [$entry['teacher_id'], $new_teacher],
                'Section' => [$entry['section'], $new_section],
            ];
            $upd = mysqli_prepare($conn, "UPDATE timetable_entries SET day_of_week = ?, start_time = ?, end_time = ?, room_id = ?, teacher_id = ?, section = ?, status = 'Approved' WHERE id = ?");
            if ($upd) {
                mysqli_stmt_bind_param($upd, 'sssiisi', $new_day, $new_start, $new_end, $new_room, $new_teacher, $new_section, $entry_id);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
            }
            // Sync room_allocations
            mysqli_query($conn, "DELETE FROM room_allocations WHERE entry_id = $entry_id");
            $by = (int)($_SESSION['user_id'] ?? 0);
            $ains = mysqli_prepare($conn, "INSERT INTO room_allocations (room_id, entry_id, day_of_week, start_time, end_time, allocated_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($ains) {
                mysqli_stmt_bind_param($ains, 'iisssi', $new_room, $entry_id, $new_day, $new_start, $new_end, $by);
                mysqli_stmt_execute($ains);
                mysqli_stmt_close($ains);
            }
            // Log adjustments
            $room_names = [];
            foreach ($rooms as $r) { $room_names[$r['id']] = $r['room_no']; }
            $teacher_names = [];
            foreach ($teachers as $t) { $teacher_names[$t['teacher_id']] = $t['teacher_name']; }
            foreach ($changes as $field => $pair) {
                if ($pair[0] == $pair[1]) continue;
                $old_display = $field === 'Room' ? ($room_names[$pair[0]] ?? $pair[0]) : ($field === 'Teacher' ? ($teacher_names[$pair[0]] ?? $pair[0]) : $pair[0]);
                $new_display = $field === 'Room' ? ($room_names[$pair[1]] ?? $pair[1]) : ($field === 'Teacher' ? ($teacher_names[$pair[1]] ?? $pair[1]) : $pair[1]);
                $lst = mysqli_prepare($conn, "INSERT INTO timetable_adjustments (entry_id, timetable_id, field_changed, old_value, new_value, reason, adjusted_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($lst) {
                    mysqli_stmt_bind_param($lst, 'iissssi', $entry_id, $entry['timetable_id'], $field, $old_display, $new_display, $reason, $by);
                    mysqli_stmt_execute($lst);
                    mysqli_stmt_close($lst);
                }
            }
            // Resolve related open conflicts for this entry
            mysqli_query($conn, "UPDATE timetable_conflicts SET status = 'Resolved', resolved_at = NOW() WHERE entry_id = $entry_id AND status = 'Open'");
            log_activity('Timetable Management', 'Adjust Entry', 'timetable_entries', $entry_id, "Entry #$entry_id -> $new_day $new_start-$new_end room #$new_room teacher #$new_teacher section $new_section");
            $success = "Timetable entry #$entry_id adjusted successfully and conflicts re-validated.";
        }
    }
}

// =============================================
// Load entries to display
// =============================================
$entries = tt_all_entries($conn);
$display = [];
if ($f_type === 'all') {
    $display = $entries;
} else {
    // Show entries that have an Open conflict of the selected type
    $conflict_entry_ids = [];
    $sql = "SELECT DISTINCT entry_id FROM timetable_conflicts WHERE conflict_type = ? AND status = 'Open' AND entry_id IS NOT NULL";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $f_type);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $conflict_entry_ids[] = (int)$row['entry_id']; }
        mysqli_stmt_close($stmt);
    }
    foreach ($entries as $e) {
        if (in_array((int)$e['entry_id'], $conflict_entry_ids, true)) { $display[] = $e; }
    }
}

// Sort by day/time
$day_order = array_flip($days_of_week);
usort($display, function ($a, $b) use ($day_order) {
    $da = $day_order[$a['day_of_week']] ?? 0;
    $db = $day_order[$b['day_of_week']] ?? 0;
    if ($da !== $db) return $da <=> $db;
    return strcmp($a['start_time'], $b['start_time']);
});

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-tools"></i> Timetable Adjustment</h2>
            <p class="text-muted mb-0">Move courses to another day, change time slots, rooms or sections. Every change is re-validated for conflicts.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Conflict type tabs -->
        <div class="panel">
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm <?= $f_type === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>" href="adjust.php?type=all"><i class="fas fa-th-list"></i> All Entries</a>
                <a class="btn btn-sm <?= $f_type === 'Student' ? 'btn-primary' : 'btn-outline-primary' ?>" href="adjust.php?type=Student"><i class="fas fa-user-graduate"></i> Student Conflict</a>
                <a class="btn btn-sm <?= $f_type === 'Teacher' ? 'btn-primary' : 'btn-outline-primary' ?>" href="adjust.php?type=Teacher"><i class="fas fa-chalkboard-teacher"></i> Teacher Conflict</a>
                <a class="btn btn-sm <?= $f_type === 'Room' ? 'btn-primary' : 'btn-outline-primary' ?>" href="adjust.php?type=Room"><i class="fas fa-door-open"></i> Room Conflict</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><?= $f_type === 'all' ? 'All Timetable Entries' : htmlspecialchars($f_type) . ' Conflict Entries'; ?> (<?= count($display); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($display)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Day / Time</th>
                                    <th>Room</th>
                                    <th>Teacher</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th style="min-width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($display as $e): ?>
                                    <?php $rowId = 'entry-' . $e['entry_id']; ?>
                                    <tr id="<?= $rowId; ?>" <?= $focus_entry == $e['entry_id'] ? 'class="table-warning"' : ''; ?>>
                                        <td><?= (int)$e['entry_id']; ?></td>
                                        <td>
                                            <span style="font-weight:600;"><?= htmlspecialchars($e['course_code']); ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($e['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($e['day_of_week']); ?></span>
                                            <br><small><?= tt_time_ago($e['start_time']); ?> - <?= tt_time_ago($e['end_time']); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($e['room_no'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($e['teacher_name'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-info">Section <?= htmlspecialchars($e['tt_section']); ?></span></td>
                                        <td><span class="status-badge <?= $e['entry_status'] === 'Published' ? 'status-active' : 'status-pending'; ?>"><?= htmlspecialchars($e['entry_status']); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="openAdjust(<?= (int)$e['entry_id']; ?>)"><i class="fas fa-edit"></i> Adjust</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-double"></i>
                        <h5><?= $f_type === 'all' ? 'No Timetable Entries' : 'No ' . htmlspecialchars($f_type) . ' Conflicts Open'; ?></h5>
                        <p class="text-muted">Generate a timetable first, or run a conflict scan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Adjustment Modal -->
    <div class="modal fade" id="adjustModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Adjust Timetable Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="adjustBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" onclick="return validateAdjust()"><i class="fas fa-save"></i> Save Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
const TEACHERS_JSON = <?= json_encode(array_map(function($t){ return ['id'=>(int)$t['teacher_id'],'name'=>$t['teacher_name']]; }, $teachers)); ?>;
const ROOMS_JSON = <?= json_encode(array_map(function($r){ return ['id'=>(int)$r['id'],'no'=>$r['room_no']]; }, $rooms)); ?>;
const DAYS_JSON = <?= json_encode($days_of_week); ?>;
const SLOTS_JSON = <?= json_encode(array_keys($time_slots)); ?>;
const ENTRIES_JSON = <?= json_encode($display); ?>;

function adjustOpts(list, valKey, labelKey) {
    return list.map(function(item) { return '<option value="' + item[valKey] + '">' + item[labelKey] + '</option>'; }).join('');
}

function openAdjust(entryId) {
    const e = ENTRIES_JSON.find(function(x) { return x.entry_id === entryId; });
    if (!e) return;
    document.getElementById('adjustBody').innerHTML =
        '<input type="hidden" name="entry_id" value="' + e.entry_id + '">' +
        '<div class="mb-3"><label class="form-label fw-semibold small text-muted">Course</label>' +
        '<div class="form-control bg-light">' + e.course_code + ' - ' + e.course_name + '</div></div>' +
        '<div class="row g-3">' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">Day <span class="text-danger">*</span></label>' +
        '<select name="new_day" class="form-select">' + adjustOpts(DAYS_JSON.map(function(d){return {id:d,name:d};}), 'id', 'name') + '</select></div>' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">Start Time <span class="text-danger">*</span></label>' +
        '<select name="new_start" class="form-select">' + adjustOpts(SLOTS_JSON.map(function(s){return {id:s,name:s};}), 'id', 'name') + '</select></div>' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">End Time <span class="text-danger">*</span></label>' +
        '<select name="new_end" class="form-select">' + adjustOpts(SLOTS_JSON.map(function(s){return {id:s,name:s};}), 'id', 'name') + '</select></div>' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">Room <span class="text-danger">*</span></label>' +
        '<select name="new_room" class="form-select">' + adjustOpts(ROOMS_JSON, 'id', 'no') + '</select></div>' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">Teacher <span class="text-danger">*</span></label>' +
        '<select name="new_teacher" class="form-select">' + adjustOpts(TEACHERS_JSON, 'id', 'name') + '</select></div>' +
        '<div class="col-md-4"><label class="form-label small text-muted fw-semibold">Section <span class="text-danger">*</span></label>' +
        '<select name="new_section" class="form-select">' + ['A','B','C'].map(function(s){return '<option value="'+s+'">Section '+s+'</option>';}).join('') + '</select></div>' +
        '<div class="col-12"><label class="form-label small text-muted fw-semibold">Reason for Adjustment</label>' +
        '<textarea name="reason" class="form-control" rows="2" placeholder="Optional: reason for the change..."></textarea></div>' +
        '</div>';
    // Pre-select current values
    const modal = document.getElementById('adjustModal');
    const form = modal.querySelector('form');
    const daySel = form.querySelector('select[name="new_day"]');
    const startSel = form.querySelector('select[name="new_start"]');
    const endSel = form.querySelector('select[name="new_end"]');
    const roomSel = form.querySelector('select[name="new_room"]');
    const teachSel = form.querySelector('select[name="new_teacher"]');
    const secSel = form.querySelector('select[name="new_section"]');
    daySel.value = e.day_of_week;
    startSel.value = (e.start_time || '').substring(0, 5);
    endSel.value = (e.end_time || '').substring(0, 5);
    roomSel.value = e.room_id;
    teachSel.value = e.teacher_id;
    secSel.value = e.tt_section || e.entry_section || 'A';
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function validateAdjust() {
    const form = document.querySelector('#adjustModal form');
    const day = form.querySelector('select[name="new_day"]').value;
    const start = form.querySelector('select[name="new_start"]').value;
    const end = form.querySelector('select[name="new_end"]').value;
    const room = form.querySelector('select[name="new_room"]').value;
    const teach = form.querySelector('select[name="new_teacher"]').value;
    if (!day || !start || !end || !room || !teach) { alert('Please complete all required fields.'); return false; }
    if (start >= end) { alert('End time must be after start time.'); return false; }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
