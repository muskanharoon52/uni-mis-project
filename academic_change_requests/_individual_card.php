<?php
// Expects: $individual
?>
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Student Selected for Change</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Student ID</label>
                    <span><?= htmlspecialchars($individual['adm_student_id']); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Full Name</label>
                    <span><?= htmlspecialchars($individual['full_name']); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Roll No</label>
                    <span><?= htmlspecialchars($individual['roll_no'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Program</label>
                    <span><?= htmlspecialchars($individual['program_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Department</label>
                    <span><?= htmlspecialchars($individual['department_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="detail-block">
                    <label>Section</label>
                    <span><?= htmlspecialchars($individual['section_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
