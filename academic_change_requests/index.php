<?php
$pageTitle = 'Academic Change Requests';
require_once __DIR__ . '/_common.php';
include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-exchange-alt"></i> Academic Change Requests</h2>
            <p class="text-muted mb-0">Choose a request type below to apply academic changes to admitted students.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row g-3">
            <?php
            $moduleCards = [
                'section_change'    => ['fas fa-users',    'Change the assigned section (A/B/C) of one or more students.'],
                'department'        => ['fas fa-building', 'Transfer students to another department (picks its first active program).'],
                'program'           => ['fas fa-graduation-cap', 'Move students to a different degree program.'],
                'course_add_drop'   => ['fas fa-book-open', 'Add or drop courses for students and update their enrolment.'],
                'course_withdrawal' => ['fas fa-sign-out-alt', 'Withdraw students from specific courses.'],
                'request_status'    => ['fas fa-clipboard-list', 'Review the history and status of every change request.'],
            ];
            foreach ($moduleCards as $key => $info):
                $m = $acrModules[$key];
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
