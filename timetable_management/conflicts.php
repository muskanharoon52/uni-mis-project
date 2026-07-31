<?php
$ttActive = 'conflicts';
$pageTitle = 'Conflict Detection';
require_once __DIR__ . '/_common.php';

// ---- Action: run scan ----
if (isset($_GET['scan']) && $_GET['scan'] === '1') {
    $entries = tt_all_entries($conn);
    $detected = [];
    foreach ($entries as $e) {
        $conflicts = tt_check_conflicts($conn, $e['timetable_id'], $e['course_id'], $e['teacher_id'], $e['day_of_week'], $e['start_time'], $e['end_time'], $e['room_id'], $e['entry_id']);
        foreach ($conflicts as $c) {
            $detected[] = [
                'entry_id' => (int)$e['entry_id'],
                'type' => $c['type'],
                'desc' => $c['desc'],
                'course_id' => (int)$e['course_id'],
                'teacher_id' => (int)$e['teacher_id'],
                'room_id' => (int)$e['room_id'],
                'day' => $e['day_of_week'],
                'start' => $e['start_time'],
                'end' => $e['end_time'],
            ];
        }
    }
    // Store into timetable_conflicts (avoid duplicates)
    $by = (int)($_SESSION['user_id'] ?? 0);
    foreach ($detected as $d) {
        $chk = mysqli_prepare($conn, "SELECT id FROM timetable_conflicts WHERE entry_id = ? AND conflict_type = ? AND status = 'Open' LIMIT 1");
        if ($chk) {
            mysqli_stmt_bind_param($chk, 'is', $d['entry_id'], $d['type']);
            mysqli_stmt_execute($chk);
            $r = mysqli_stmt_get_result($chk);
            $exists = mysqli_fetch_assoc($r);
            mysqli_stmt_close($chk);
            if (!$exists) {
                $ins = mysqli_prepare($conn, "INSERT INTO timetable_conflicts (entry_id, conflict_type, description, course_id, teacher_id, room_id, day_of_week, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
                if ($ins) {
                    mysqli_stmt_bind_param($ins, 'issiissss', $d['entry_id'], $d['type'], $d['desc'], $d['course_id'], $d['teacher_id'], $d['room_id'], $d['day'], $d['start'], $d['end']);
                    mysqli_stmt_execute($ins);
                    mysqli_stmt_close($ins);
                }
            }
        }
    }
    $success = "Conflict scan complete. Detected " . count($detected) . " conflict(s).";
}

// ---- Action: resolve / ignore ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conflict_id = (int)($_POST['conflict_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($conflict_id > 0 && in_array($action, ['Resolved', 'Ignored'], true)) {
        $stmt = mysqli_prepare($conn, "UPDATE timetable_conflicts SET status = ?, resolved_at = NOW() WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $action, $conflict_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = "Conflict #$conflict_id marked as $action.";
        }
    }
}

// ---- Filters ----
$f_type = isset($_GET['type']) ? $_GET['type'] : '';
$f_status = isset($_GET['status']) ? $_GET['status'] : '';
$allowed_types = ['Student', 'Teacher', 'Room'];

$sql = "SELECT tc.*,
               c.course_code, COALESCE(NULLIF(c.course_name,''), c.course_title) AS course_name,
               tc2.teacher_name, r.room_no
        FROM timetable_conflicts tc
        LEFT JOIN courses c ON c.course_id = tc.course_id
        LEFT JOIN teachers tc2 ON tc2.teacher_id = tc.teacher_id
        LEFT JOIN rooms r ON r.id = tc.room_id
        WHERE 1=1";
$params = [];
$types = '';
if ($f_type !== '' && in_array($f_type, $allowed_types, true)) {
    $sql .= " AND tc.conflict_type = ?"; $params[] = $f_type; $types .= 's';
}
if ($f_status !== '' && in_array($f_status, ['Open', 'Resolved', 'Ignored'], true)) {
    $sql .= " AND tc.status = ?"; $params[] = $f_status; $types .= 's';
}
$sql .= " ORDER BY tc.status = 'Open' DESC, tc.detected_at DESC";

$conflicts = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $conflicts[] = $row; }
    mysqli_stmt_close($stmt);
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-exclamation-triangle"></i> Conflict Detection</h2>
                <span class="text-muted small">Automatically scan timetable tables for student, teacher and room conflicts.</span>
            </div>
            <a href="conflicts.php?scan=1" class="btn btn-warning"><i class="fas fa-radiation"></i> Run Conflict Scan</a>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Filters -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Conflict Types</option>
                        <?php foreach ($allowed_types as $t): ?>
                            <option value="<?= htmlspecialchars($t); ?>" <?= $f_type === $t ? 'selected' : ''; ?>><?= htmlspecialchars($t); ?> Conflict</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach (['Open', 'Resolved', 'Ignored'] as $st): ?>
                            <option value="<?= $st; ?>" <?= $f_status === $st ? 'selected' : ''; ?>><?= $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="conflicts.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Conflicts table -->
        <div class="card mt-3">
            <div class="card-header"><h5>Conflict Report (<?= count($conflicts); ?>)</h5></div>
            <div class="card-body">
                <?php if (!empty($conflicts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Day / Time</th>
                                    <th>Status</th>
                                    <th>Detected At</th>
                                    <th style="min-width:220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conflicts as $c): ?>
                                    <tr>
                                        <td><?= (int)$c['id']; ?></td>
                                        <td>
                                            <span class="badge <?= $c['conflict_type'] === 'Student' ? 'bg-danger' : ($c['conflict_type'] === 'Teacher' ? 'bg-warning text-dark' : 'bg-info'); ?>"><?= htmlspecialchars($c['conflict_type']); ?></span>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($c['description']); ?></td>
                                        <td class="small">
                                            <?= htmlspecialchars($c['day_of_week'] ?? 'N/A'); ?>
                                            <br><?= $c['start_time'] ? tt_time_ago($c['start_time']) . ' - ' . tt_time_ago($c['end_time']) : 'N/A'; ?>
                                        </td>
                                        <td><span class="status-badge <?= $c['status'] === 'Open' ? 'status-pending' : 'status-active'; ?>"><?= htmlspecialchars($c['status']); ?></span></td>
                                        <td class="muted"><?= htmlspecialchars($c['detected_at']); ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a class="btn btn-sm btn-outline-primary" href="adjust.php?type=<?= htmlspecialchars($c['conflict_type']); ?>&focus=<?= (int)$c['entry_id']; ?>"><i class="fas fa-edit"></i> Resolve</a>
                                                <?php if ($c['status'] === 'Open'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="conflict_id" value="<?= (int)$c['id']; ?>">
                                                        <button type="submit" name="action" value="Resolved" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Mark Resolved</button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="conflict_id" value="<?= (int)$c['id']; ?>">
                                                        <button type="submit" name="action" value="Ignored" class="btn btn-sm btn-secondary"><i class="fas fa-eye-slash"></i> Ignore</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shield-alt"></i>
                        <h5>No Conflicts Found</h5>
                        <p class="text-muted">Run a conflict scan to detect scheduling issues, or adjust the filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
