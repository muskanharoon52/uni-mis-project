<?php
$pageTitle = 'Student Schedule Requests';
require_once __DIR__ . '/_common.php';

// Pending count badge on landing
$pending_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM student_schedule_requests WHERE status = 'Pending'");
if ($res && ($r = mysqli_fetch_assoc($res))) { $pending_count = (int)$r['c']; }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-calendar-check"></i> Student Schedule Requests</h2>
            <p class="text-muted mb-0">Manage student-requested timetable changes: submissions, review and audit history.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row g-3">
            <?php
            $moduleCards = [
                'submit'  => ['fas fa-paper-plane',     'Log a schedule conflict or timing request for a student against their published timetable.'],
                'review'  => ['fas fa-inbox',           'Review pending requests and approve, reject or forward them to senior administration.'],
                'history' => ['fas fa-history',         'Audit log of every schedule request with status, reviewer and decision timestamps.'],
            ];
            foreach ($moduleCards as $key => $info):
                $m = $ssrModules[$key];
            ?>
                <div class="col-md-4">
                    <a class="card h-100 text-decoration-none" style="transition:.15s;" href="<?= htmlspecialchars($m['file']); ?>">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;">
                                    <i class="<?= $info[0]; ?>"></i>
                                </div>
                                <h5 class="mb-0" style="color:var(--text);"><?= htmlspecialchars($m['title']); ?></h5>
                                <?php if ($key === 'review' && $pending_count > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?= $pending_count; ?> pending</span>
                                <?php endif; ?>
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
