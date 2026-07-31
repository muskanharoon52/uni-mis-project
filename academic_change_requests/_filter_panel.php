<?php
// Expects: $mode, $departments, $sessions, $dept_filter, $session_filter, $search
?>
<div class="panel">
    <form method="GET" class="row g-3">
        <input type="hidden" name="mode" value="<?= htmlspecialchars($mode); ?>">

        <?php if ($mode === 'bulk'): ?>
            <div class="col-md-4">
                <select name="session" class="form-select">
                    <option value="0">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['session_id']; ?>" <?= $session_filter == $s['session_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($s['session_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="dept" class="form-select">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id']; ?>" <?= $dept_filter == $d['department_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($d['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Students</button>
                <a href="?mode=bulk" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            </div>
            <div class="col-12">
                <small class="text-muted"><i class="fas fa-info-circle"></i> Select a session and/or department to load admitted students in bulk.</small>
            </div>
        <?php else: ?>
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" required
                       placeholder="Enter Student ID (e.g. STU-2026-AI01), Roll No, or Application ID..."
                       value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Find Student</button>
                <a href="?mode=individual" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            </div>
        <?php endif; ?>
    </form>
</div>
