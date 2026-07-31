<?php
if (!isset($courses)) $courses = [];
if (!isset($programs)) $programs = [];
if (!isset($departments)) $departments = [];
if (!isset($section_letters)) $section_letters = ['A', 'B', 'C'];
?>
<!-- Change Request Panel -->
<div class="panel mt-3" style="border:1px dashed var(--border);">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="m-0"><i class="fas fa-exchange-alt"></i> Apply Academic Change</h5>
        <span class="text-muted small">Choose the change type and the new value, then apply.</span>
    </div>

    <input type="hidden" name="change_type" id="change_type" value="section">

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Change Type</label>
        <div>
            <button type="button" class="btn btn-sm btn-primary change-type-tab" data-type="section"><i class="fas fa-users"></i> Change Section</button>
            <button type="button" class="btn btn-sm btn-outline-primary change-type-tab" data-type="department"><i class="fas fa-building"></i> Change Department</button>
            <button type="button" class="btn btn-sm btn-outline-primary change-type-tab" data-type="program"><i class="fas fa-university"></i> Change Program</button>
            <button type="button" class="btn btn-sm btn-outline-primary change-type-tab" data-type="course"><i class="fas fa-book"></i> Course Add/Drop</button>
        </div>
    </div>

    <!-- Section change -->
    <div class="change-pane" id="pane-section">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold small text-muted">New Section <span class="text-danger">*</span></label>
                <select name="new_section" class="form-select">
                    <option value="">Select Section</option>
                    <?php foreach ($section_letters as $letter): ?>
                        <option value="<?= htmlspecialchars($letter); ?>">Section <?= htmlspecialchars($letter); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> The section is matched within the student's program.</div>
            </div>
        </div>
    </div>

    <!-- Department change -->
    <div class="change-pane" id="pane-department" style="display:none;">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold small text-muted">New Department <span class="text-danger">*</span></label>
                <select name="new_department_id" id="new_department_id" class="form-select">
                    <option value="0">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int)$d['department_id']; ?>"><?= htmlspecialchars($d['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> The student is moved to the first active program of the selected department.</div>
            </div>
        </div>
    </div>

    <!-- Program change -->
    <div class="change-pane" id="pane-program" style="display:none;">
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
                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Changing the program also moves the student to the new department.</div>
            </div>
        </div>
    </div>

    <!-- Course add/drop -->
    <div class="change-pane" id="pane-course" style="display:none;">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small text-muted">Add Courses</label>
                <select name="add_course_ids[]" class="form-select" multiple style="min-height:110px;">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['course_id']; ?>">
                            <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple courses to add.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small text-muted">Drop Courses</label>
                <select name="remove_course_ids[]" class="form-select" multiple style="min-height:110px;">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['course_id']; ?>">
                            <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple courses to drop.</div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary" onclick="return requireChangeSelection()">
            <i class="fas fa-check-circle"></i> Apply Change to Selected
        </button>
    </div>
</div>

<script>
function requireChangeSelection() {
    const type = document.getElementById('change_type').value;
    if (type === 'section' && !document.querySelector('select[name="new_section"]').value) {
        alert('Please select the new section.');
        return false;
    }
    if (type === 'department' && !document.querySelector('select[name="new_department_id"]').value) {
        alert('Please select the new department.');
        return false;
    }
    if (type === 'program' && !document.querySelector('select[name="new_program_id"]').value) {
        alert('Please select the new program.');
        return false;
    }
    if (type === 'course') {
        const add = document.querySelector('select[name="add_course_ids[]"]').selectedOptions.length;
        const rem = document.querySelector('select[name="remove_course_ids[]"]').selectedOptions.length;
        if (add === 0 && rem === 0) {
            alert('Please select courses to add or drop.');
            return false;
        }
    }
    return true;
}
</script>
