<?php
$ttActive = 'publish';
$pageTitle = 'Publish Timetable';
require_once __DIR__ . '/_common.php';

$f_status = isset($_GET['status']) ? $_GET['status'] : '';
$allowed_statuses = ['Draft', 'Pending Review', 'Approved', 'Published'];

// =============================================
// WORKFLOW POST: Draft -> Pending Review -> Approved -> Published
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tt_id = (int)($_POST['timetable_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $by = (int)($_SESSION['user_id'] ?? 0);

    $tt = null;
    $res = mysqli_query($conn, "SELECT * FROM timetables WHERE id = $tt_id");
    if ($res) { $tt = mysqli_fetch_assoc($res); }

    if (!$tt) {
        $error = "Timetable not found.";
    } else {
        $next = '';
        switch ($action) {
            case 'review':  if ($tt['status'] === 'Draft')            $next = 'Pending Review'; break;
            case 'approve': if ($tt['status'] === 'Pending Review')   $next = 'Approved'; break;
            case 'publish': if ($tt['status'] === 'Approved')         $next = 'Published'; break;
        }
        if ($next === '') {
            $error = "Invalid workflow step.";
        } else {
            // Check every entry for conflicts before approving/publishing
            $entry_count = 0;
            $bad = [];
            $res2 = mysqli_query($conn, "SELECT * FROM timetable_entries WHERE timetable_id = $tt_id");
            if ($res2) {
                while ($row = mysqli_fetch_assoc($res2)) {
                    $entry_count++;
                    $conflicts = tt_check_conflicts($conn, $tt_id, $row['course_id'], $row['teacher_id'], $row['day_of_week'], $row['start_time'], $row['end_time'], $row['room_id'], $row['id']);
                    if (!empty($conflicts)) { $bad[] = $row['id']; }
                }
            }
            if ($entry_count === 0) {
                $error = "This timetable has no entries. Add courses before publishing.";
            } elseif (!empty($bad)) {
                $error = "Cannot advance workflow: " . count($bad) . " entry(ies) still have conflicts (#" . implode(', #', array_map('intval', $bad)) . "). Resolve them first.";
            } else {
                // Advance status
                $ts = date('Y-m-d H:i:s');
                $upd = mysqli_prepare($conn, "UPDATE timetables SET status = ?, updated_at = ? WHERE id = ?");
                if ($upd) {
                    mysqli_stmt_bind_param($upd, 'ssi', $next, $ts, $tt_id);
                    mysqli_stmt_execute($upd);
                    mysqli_stmt_close($upd);
                }
                // Sync entry statuses (entries move with the timetable, capped at 'Published')
                $entry_status = $next;
                if ($next === 'Published') $entry_status = 'Published';
                mysqli_query($conn, "UPDATE timetable_entries SET status = '$entry_status' WHERE timetable_id = $tt_id");
                // Record publication timestamp
                if ($next === 'Published') {
                    mysqli_query($conn, "UPDATE timetables SET published_at = '$ts' WHERE id = $tt_id");
                }
                $success = "Timetable #$tt_id moved to <strong>$next</strong>.";
                log_activity('Timetable Management', 'Workflow: ' . $next, 'timetables', $tt_id, "Timetable #$tt_id moved to $next");
            }
        }
    }
}

// =============================================
// Load timetables
// =============================================
$tt_rows = [];
$sql = "SELECT t.id, t.department_id, t.program_id, t.session_id, t.semester_id, t.section,
               t.status, t.published_at, t.created_at,
               p.program_name, d.department_name, ss.session_name, sem.semester_name,
               (SELECT COUNT(*) FROM timetable_entries e WHERE e.timetable_id = t.id) AS entry_count,
               (SELECT COUNT(*) FROM timetable_conflicts c JOIN timetable_entries e2 ON e2.id = c.entry_id
                 WHERE e2.timetable_id = t.id AND c.status = 'Open') AS open_conflicts
        FROM timetables t
        LEFT JOIN programs p ON p.program_id = t.program_id
        LEFT JOIN departments d ON d.department_id = t.department_id
        LEFT JOIN sessions ss ON ss.session_id = t.session_id
        LEFT JOIN semesters sem ON sem.semester_id = t.semester_id";
if ($f_status !== '' && in_array($f_status, $allowed_statuses, true)) {
    $sql .= " WHERE t.status = '" . mysqli_real_escape_string($conn, $f_status) . "'";
}
$sql .= " ORDER BY t.created_at DESC, t.id DESC";
$res = mysqli_query($conn, $sql);
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $tt_rows[] = $row; } }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-rocket"></i> Publish Timetable</h2>
            <p class="text-muted mb-0">Move timetables through the workflow: <span class="badge bg-secondary">Draft</span> &rarr; <span class="badge bg-info text-dark">Pending Review</span> &rarr; <span class="badge bg-warning text-dark">Approved</span> &rarr; <span class="badge bg-success">Published</span></p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Status filter -->
        <div class="panel">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Status Filter</label>
                    <select name="status" class="form-select" style="min-width:200px;">
                        <option value="">All Statuses</option>
                        <?php foreach ($allowed_statuses as $s): ?>
                            <option value="<?= $s; ?>" <?= $f_status === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Generated Timetables (<?= count($tt_rows); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($tt_rows)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Program</th>
                                    <th>Department</th>
                                    <th>Session</th>
                                    <th>Semester</th>
                                    <th>Section</th>
                                    <th>Entries</th>
                                    <th>Open Conflicts</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="min-width:200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tt_rows as $tt): ?>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if ($tt['status'] === 'Pending Review') $badge = 'bg-info text-dark';
                                    elseif ($tt['status'] === 'Approved')    $badge = 'bg-warning text-dark';
                                    elseif ($tt['status'] === 'Published')   $badge = 'bg-success';
                                    ?>
                                    <tr>
                                        <td><?= (int)$tt['id']; ?></td>
                                        <td><strong><?= htmlspecialchars($tt['program_name'] ?? '—'); ?></strong></td>
                                        <td><?= htmlspecialchars($tt['department_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($tt['session_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($tt['semester_name'] ?? '—'); ?></td>
                                        <td><span class="badge bg-info">Section <?= htmlspecialchars($tt['section']); ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= (int)$tt['entry_count']; ?></span></td>
                                        <td>
                                            <?php if ((int)$tt['open_conflicts'] > 0): ?>
                                                <a href="conflicts.php" class="badge bg-danger text-decoration-none"><i class="fas fa-exclamation-triangle"></i> <?= (int)$tt['open_conflicts']; ?> open</a>
                                            <?php else: ?>
                                                <span class="badge bg-success">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-badge <?= $tt['status'] === 'Published' ? 'status-active' : 'status-pending'; ?>"><?= htmlspecialchars($tt['status']); ?></span></td>
                                        <td><small><?= date('d M Y', strtotime($tt['created_at'])); ?></small></td>
                                        <td>
                                            <?php if ($tt['status'] === 'Draft'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Send timetable #<?= (int)$tt['id']; ?> to Pending Review?');">
                                                    <input type="hidden" name="timetable_id" value="<?= (int)$tt['id']; ?>">
                                                    <input type="hidden" name="action" value="review">
                                                    <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-paper-plane"></i> Review</button>
                                                </form>
                                            <?php elseif ($tt['status'] === 'Pending Review'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Approve timetable #<?= (int)$tt['id']; ?>?');">
                                                    <input type="hidden" name="timetable_id" value="<?= (int)$tt['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-check"></i> Approve</button>
                                                </form>
                                            <?php elseif ($tt['status'] === 'Approved'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Publish timetable #<?= (int)$tt['id']; ?>? This makes it visible to students.');">
                                                    <input type="hidden" name="timetable_id" value="<?= (int)$tt['id']; ?>">
                                                    <input type="hidden" name="action" value="publish">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-rocket"></i> Publish</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-success small"><i class="fas fa-check-circle"></i> Published <?= $tt['published_at'] ? 'on ' . date('d M Y', strtotime($tt['published_at'])) : ''; ?></span>
                                            <?php endif; ?>
                                            <a href="view.php?view=section&program=<?= (int)$tt['program_id']; ?>&session=<?= (int)$tt['session_id']; ?>&semester=<?= (int)$tt['semester_id']; ?>&section=<?= urlencode($tt['section']); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h5>No Timetables Found</h5>
                        <p class="text-muted">Generate a timetable first to start the workflow.</p>
                        <a href="generate.php" class="btn btn-primary"><i class="fas fa-plus"></i> Generate Timetable</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
