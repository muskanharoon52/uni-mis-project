<?php
$acrActive = 'course_withdrawal';
$pageTitle = 'Course Withdrawal Request';
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? array_map('intval', $_POST['student_ids']) : [];
    $remove_course_ids = isset($_POST['remove_course_ids']) && is_array($_POST['remove_course_ids']) ? array_map('intval', $_POST['remove_course_ids']) : [];

    if (empty($student_ids)) {
        $error = "No students selected. Tick the students you want to apply the change to.";
    } elseif (empty($remove_course_ids)) {
        $error = "Please select at least one course to withdraw.";
    } else {
        $processed = 0;
        $fails = [];
        foreach ($student_ids as $id) {
            $r = acr_apply_courses($id, [], $remove_course_ids, true);
            if ($r['ok']) { $processed++; } else { $fails[] = $r['msg']; }
        }
        $success = "Applied course withdrawal to $processed student(s).";
        if (!empty($fails)) { $error = implode('; ', array_unique($fails)); }
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-sign-out-alt"></i> Course Withdrawal Request</h2>
                <span class="text-muted small">Withdraw students from specific courses.</span>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Academic Change Requests</a>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <?php include __DIR__ . '/_filter_panel.php'; ?>

        <?php if ($mode === 'bulk'): ?>
        <form method="POST" id="changeForm">
            <?php include __DIR__ . '/_students_table.php'; ?>

            <?php if (!empty($bulk_students)): ?>
            <div class="panel mt-3" style="border:1px dashed var(--border);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="m-0"><i class="fas fa-sign-out-alt"></i> Apply Course Withdrawal</h5>
                    <span class="text-muted small">Hold Ctrl/Cmd to select multiple courses.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Withdraw Courses</label>
                        <select name="remove_course_ids[]" class="form-select" multiple style="min-height:130px;">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['course_id']; ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateWithdraw()">
                        <i class="fas fa-check-circle"></i> Apply Course Withdrawal
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </form>
        <?php elseif ($individual): ?>
        <form method="POST" id="changeForm">
            <input type="hidden" name="student_ids[]" value="<?= (int)$individual['id']; ?>">
            <?php include __DIR__ . '/_individual_card.php'; ?>

            <?php
            $cur_courses = acr_course_codes((int)$individual['application_id']);
            ?>
            <div class="panel mt-3" style="border:1px dashed var(--border);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="m-0"><i class="fas fa-sign-out-alt"></i> Apply Course Withdrawal</h5>
                    <span class="text-muted small">Hold Ctrl/Cmd to select multiple courses.</span>
                </div>
                <?php if ($cur_courses !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Currently Allocated</label>
                        <div>
                            <?php foreach (explode(', ', $cur_courses) as $cc): ?>
                                <span class="badge bg-info me-1"><?= htmlspecialchars($cc); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Withdraw Courses</label>
                        <select name="remove_course_ids[]" class="form-select" multiple style="min-height:130px;">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['course_id']; ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateWithdraw()">
                        <i class="fas fa-check-circle"></i> Apply Course Withdrawal
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/_acr_js.php'; ?>

<script>
function validateWithdraw() {
    if (!requireStudentSelection()) return false;
    const rem = document.querySelector('select[name="remove_course_ids[]"]');
    if (!rem || rem.selectedOptions.length === 0) {
        alert('Please select at least one course to withdraw.');
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
