<?php
$acrActive = 'program';
$pageTitle = 'Program Change Request';
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? array_map('intval', $_POST['student_ids']) : [];
    $new_program_id = (int)($_POST['new_program_id'] ?? 0);

    if (empty($student_ids)) {
        $error = "No students selected. Tick the students you want to apply the change to.";
    } elseif ($new_program_id <= 0) {
        $error = "Please select the new program.";
    } else {
        $processed = 0;
        $fails = [];
        foreach ($student_ids as $id) {
            $r = acr_apply_program($id, $new_program_id);
            if ($r['ok']) { $processed++; } else { $fails[] = $r['msg']; }
        }
        $success = "Applied program change to $processed student(s).";
        if (!empty($fails)) { $error = implode('; ', array_unique($fails)); }
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-graduation-cap"></i> Program Change Request</h2>
                <span class="text-muted small">Move students to a different degree program.</span>
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
                    <h5 class="m-0"><i class="fas fa-graduation-cap"></i> Apply Program Change</h5>
                    <span class="text-muted small">Changing the program also moves the student to the new program's department.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">New Program <span class="text-danger">*</span></label>
                        <select name="new_program_id" class="form-select">
                            <option value="0">Select Program</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= (int)$p['program_id']; ?>">
                                    <?= htmlspecialchars($p['program_name'] . ($p['department_name'] ? ' (' . $p['department_name'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateProgram()">
                        <i class="fas fa-check-circle"></i> Apply Program Change
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
                    <h5 class="m-0"><i class="fas fa-graduation-cap"></i> Apply Program Change</h5>
                    <span class="text-muted small">Changing the program also moves the student to the new program's department.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">New Program <span class="text-danger">*</span></label>
                        <select name="new_program_id" class="form-select">
                            <option value="0">Select Program</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= (int)$p['program_id']; ?>">
                                    <?= htmlspecialchars($p['program_name'] . ($p['department_name'] ? ' (' . $p['department_name'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateProgram()">
                        <i class="fas fa-check-circle"></i> Apply Program Change
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/_acr_js.php'; ?>

<script>
function validateProgram() {
    if (!requireStudentSelection()) return false;
    const sel = document.querySelector('select[name="new_program_id"]');
    if (sel && sel.value === '0') {
        alert('Please select the new program.');
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
