<?php
$pageTitle = 'Timetable Management';
require_once __DIR__ . '/_common.php';

// Counts for landing cards
$tt_count = 0; $entry_count = 0; $open_conflicts = 0; $published = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM timetables"); if ($res && ($r = mysqli_fetch_assoc($res))) $tt_count = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM timetable_entries"); if ($res && ($r = mysqli_fetch_assoc($res))) $entry_count = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM timetable_conflicts WHERE status = 'Open'"); if ($res && ($r = mysqli_fetch_assoc($res))) $open_conflicts = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM timetables WHERE status = 'Published'"); if ($res && ($r = mysqli_fetch_assoc($res))) $published = (int)$r['c'];

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-calendar-alt"></i> Timetable Management</h2>
            <p class="text-muted mb-0">Generate, view, de-conflict, adjust and publish academic timetables.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Stat cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold" style="color:var(--accent);"><?= $tt_count; ?></div><small class="text-muted">Timetables</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-info"><?= $entry_count; ?></div><small class="text-muted">Total Entries</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold <?= $open_conflicts > 0 ? 'text-danger' : 'text-success'; ?>"><?= $open_conflicts; ?></div><small class="text-muted">Open Conflicts</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-success"><?= $published; ?></div><small class="text-muted">Published</small></div></div></div>
        </div>

        <div class="row g-3">
            <?php
            $moduleCards = [
                'generate'  => ['fas fa-plus',           'Build a timetable by adding courses with day, time, room and teacher, with automatic conflict gating.'],
                'view'      => ['fas fa-table',          'Browse timetables by section, student, teacher or room.'],
                'conflicts' => ['fas fa-exclamation-triangle', 'Scan for room, teacher and student conflicts and mark them resolved or ignored.'],
                'adjust'    => ['fas fa-tools',          'Move entries, change time slots, rooms or sections with re-validation on every change.'],
                'publish'   => ['fas fa-rocket',         'Drive the workflow from Draft to Pending Review to Approved to Published.'],
            ];
            foreach ($moduleCards as $key => $info):
                $m = $ttModules[$key];
            ?>
                <div class="col-md-4">
                    <a class="card h-100 text-decoration-none" style="transition:.15s;" href="<?= htmlspecialchars($m['file']); ?>">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;">
                                    <i class="<?= $info[0]; ?>"></i>
                                </div>
                                <h5 class="mb-0" style="color:var(--text);"><?= htmlspecialchars($m['title']); ?></h5>
                            </div>
                            <p class="text-muted small mb-0" style="flex:1;"><?= htmlspecialchars($info[1]); ?></p>
                            <span class="small" style="color:var(--accent);">Open Module <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
