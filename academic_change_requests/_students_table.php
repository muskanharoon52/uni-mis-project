<?php
// Expects: $bulk_students, $session_names
?>
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Admitted Students (<?= count($bulk_students); ?>)</h5>
        <label style="font-weight:500; margin-bottom:0;"><input type="checkbox" id="select_all" style="margin-right:5px;"> Select All</label>
    </div>
    <div class="card-body">
        <?php if (!empty($bulk_students)): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all_top"></th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Department</th>
                            <th>Section</th>
                            <th>Session</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bulk_students as $s): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?= $s['id']; ?>" class="student-cb"></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($s['adm_student_id']); ?></td>
                                <td>
                                    <?= htmlspecialchars($s['full_name']); ?>
                                    <?php if (!empty($s['roll_no'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($s['roll_no']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($s['program_name'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($s['department_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($s['section_name'])): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($s['section_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="muted"><?= !empty($s['app_session_id']) && isset($session_names[$s['app_session_id']]) ? htmlspecialchars($session_names[$s['app_session_id']]) : 'N/A'; ?></td>
                                <td>
                                    <?php if (!empty($s['is_registered'])): ?>
                                        <span class="status-badge status-active">Registered</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Fee Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h5>No Students Loaded</h5>
                <p class="text-muted">Use the filters above to load admitted students for a session and department.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
