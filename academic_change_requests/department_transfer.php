<?php
$acrActive = 'department';
$pageTitle = 'Department Transfer Request';
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? array_map('intval', $_POST['student_ids']) : [];
    $new_department_id = (int)($_POST['new_department_id'] ?? 0);

    if (empty($student_ids)) {
        $error = "No students selected. Tick the students you want to apply the change to.";
    } elseif ($new_department_id <= 0) {
        $error = "Please select the new department.";
    } else {
        $processed = 0;
        $fails = [];
        foreach ($student_ids as $id) {
            $r = acr_apply_department($id, $new_department_id);
            if ($r['ok']) { $processed++; } else { $fails[] = $r['msg']; }
        }
        $success = "Applied department transfer to $processed student(s).";
        if (!empty($fails)) { $error = implode('; ', array_unique($fails)); }
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-building"></i> Department Transfer Request</h2>
                <span class="text-muted small">Move students to another department (first active program of that department).</span>
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
                    <h5 class="m-0"><i class="fas fa-building"></i> Apply Department Transfer</h5>
                    <span class="text-muted small">The student is moved to the first active program of the selected department.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">New Department <span class="text-danger">*</span></label>
                        <select name="new_department_id" class="form-select">
                            <option value="0">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['department_id']; ?>"><?= htmlspecialchars($d['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateDept()">
                        <i class="fas fa-check-circle"></i> Apply Department Transfer
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </form>
        <?php elseif ($individual): ?>
        <form method="POST" id="changeForm">
            <input type="hidden" name="student_ids[]" value="<?= (int)$individual['id']; ?>">
            <?php include __DIR__ . '/_individual_card.php'; ?>

            <div class="panel mt-3" style="border:1px dashed var(--border);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="m-0"><i class="fas fa-building"></i> Apply Department Transfer</h5>
                    <span class="text-muted small">The student is moved to the first active program of the selected department.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">New Department <span class="text-danger">*</span></label>
                        <select name="new_department_id" class="form-select">
                            <option value="0">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['department_id']; ?>"><?= htmlspecialchars($d['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateDept()">
                        <i class="fas fa-check-circle"></i> Apply Department Transfer
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/_acr_js.php'; ?>

<script>
function validateDept() {
    if (!requireStudentSelection()) return false;
    const sel = document.querySelector('select[name="new_department_id"]');
    if (sel && sel.value === '0') {
        alert('Please select the new department.');
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
