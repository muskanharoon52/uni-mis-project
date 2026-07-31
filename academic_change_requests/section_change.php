<?php
$acrActive = 'section_change';
$pageTitle = 'Section Change Request';
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? array_map('intval', $_POST['student_ids']) : [];
    $new_section = strtoupper(trim($_POST['new_section'] ?? ''));

    if (empty($student_ids)) {
        $error = "No students selected. Tick the students you want to apply the change to.";
    } elseif ($new_section === '') {
        $error = "Please select the new section.";
    } elseif (!in_array($new_section, $section_letters, true)) {
        $error = "Invalid section selected.";
    } else {
        $processed = 0;
        $fails = [];
        foreach ($student_ids as $id) {
            $r = acr_apply_section($id, $new_section);
            if ($r['ok']) { $processed++; } else { $fails[] = $r['msg']; }
        }
        $success = "Applied section change to $processed student(s).";
        if (!empty($fails)) { $error = implode('; ', array_unique($fails)); }
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-users"></i> Section Change Request</h2>
                <span class="text-muted small">Change the assigned section of admitted students.</span>
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
                    <h5 class="m-0"><i class="fas fa-users"></i> Apply Section Change</h5>
                    <span class="text-muted small">New section is matched within each student's program.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">New Section <span class="text-danger">*</span></label>
                        <select name="new_section" class="form-select">
                            <option value="">Select Section</option>
                            <?php foreach ($section_letters as $letter): ?>
                                <option value="<?= htmlspecialchars($letter); ?>">Section <?= htmlspecialchars($letter); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateSection()">
                        <i class="fas fa-check-circle"></i> Apply Section Change
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
                    <h5 class="m-0"><i class="fas fa-users"></i> Apply Section Change</h5>
                    <span class="text-muted small">New section is matched within the student's program.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">New Section <span class="text-danger">*</span></label>
                        <select name="new_section" class="form-select">
                            <option value="">Select Section</option>
                            <?php foreach ($section_letters as $letter): ?>
                                <option value="<?= htmlspecialchars($letter); ?>">Section <?= htmlspecialchars($letter); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" onclick="return validateSection()">
                        <i class="fas fa-check-circle"></i> Apply Section Change
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/_acr_js.php'; ?>

<script>
function validateSection() {
    if (!requireStudentSelection()) return false;
    const sel = document.querySelector('select[name="new_section"]');
    if (sel && !sel.value) {
        alert('Please select the new section.');
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
