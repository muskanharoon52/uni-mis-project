<?php
// examination/promote/index.php - Bulk promote students to the next semester
// Filter by department + session, select students with checkboxes, promote.

$page_title = 'Promote Students';

require_once '../../config/db_connect.php';
require_once '../../modules/sbe/config/database.php';
require_once '../../modules/sbe/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();

$departmentId = !empty($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
$sessionId    = !empty($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$filtersSet   = $departmentId > 0 && $sessionId > 0;

$departments = $db->query('SELECT department_id, department_name FROM departments WHERE status = "Active" ORDER BY department_name')->fetchAll();
$sessions    = $db->query('SELECT session_id, session_name FROM sessions WHERE status = "Active" ORDER BY start_date ASC')->fetchAll();

$students = [];
$selectedSession = null;

if ($filtersSet) {
    $stmt = $db->prepare(
        'SELECT s.student_id, s.roll_no, s.full_name, s.batch_year, s.current_semester_id,
                sem.semester_name, sec.section_name, p.program_name
         FROM students s
         LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
         LEFT JOIN sections sec ON sec.section_id = s.section_id
         LEFT JOIN programs p ON p.program_id = s.program_id
         WHERE s.status = :status
           AND p.department_id = :dept
           AND s.current_session_id = :sess
         ORDER BY s.roll_no, s.full_name'
    );
    $stmt->execute([
        ':status' => 'Active',
        ':dept'   => $departmentId,
        ':sess'   => $sessionId,
    ]);
    $students = $stmt->fetchAll();

    $sessionStmt = $db->prepare('SELECT session_id, session_name, start_date, end_date FROM sessions WHERE session_id = :id');
    $sessionStmt->execute([':id' => $sessionId]);
    $selectedSession = $sessionStmt->fetch();
}

$promoted = 0;
$skipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_students'])) {
    $studentIds = array_map('intval', (array) ($_POST['student_ids'] ?? []));
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if (empty($studentIds)) {
        $_SESSION['error'] = 'Please select at least one student to promote.';
        header('Location: index.php?department_id=' . $departmentId . '&session_id=' . $sessionId);
        exit;
    }

    $sessionStmt = $db->prepare('SELECT session_id, session_name, start_date, end_date FROM sessions WHERE session_id = :id');
    $sessionStmt->execute([':id' => $sessionId]);
    $acadSession = $sessionStmt->fetch();

    $fromYear = $toYear = '2026-2027';
    if ($acadSession) {
        $fromY = !empty($acadSession['start_date']) ? (int) date('Y', strtotime((string) $acadSession['start_date'])) : 0;
        $toY = !empty($acadSession['end_date']) ? (int) date('Y', strtotime((string) $acadSession['end_date'])) : 0;
        if ($fromY > 0 && $toY >= $fromY) {
            $fromYear = $fromY . '-' . $toY;
            $toYear = ($toY + 1) . '-' . ($toY + 2);
        }
    }

    $try = 0;
    foreach ($studentIds as $sid) {
        if ($sid <= 0) continue;
        $try++;

        $studentStmt = $db->prepare(
            'SELECT s.student_id, s.current_semester_id, sem.semester_number
             FROM students s
             LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
             WHERE s.student_id = :id'
        );
        $studentStmt->execute([':id' => $sid]);
        $student = $studentStmt->fetch();

        if (!$student || (int) $student['current_semester_id'] <= 0 || $student['semester_number'] === null) {
            $skipped++;
            continue;
        }

        $currentNumber = (int) $student['semester_number'];
        $nextNumber = $currentNumber + 1;

        $nextStmt = $db->prepare(
            'SELECT semester_id, semester_name FROM semesters
             WHERE department_id = :dept AND semester_number = :num
             ORDER BY semester_number LIMIT 1'
        );
        $nextStmt->execute([':dept' => $departmentId, ':num' => $nextNumber]);
        $nextSem = $nextStmt->fetch();

        if (!$nextSem) {
            $skipped++;
            continue;
        }

        $currentSemName = (string) ($student['semester_number'] > 0 ? 'Semester ' . $currentNumber : '');

        $db->beginTransaction();
        try {
            $db->prepare('UPDATE students SET current_semester_id = :sem WHERE student_id = :id')
                ->execute([':sem' => (int) $nextSem['semester_id'], ':id' => $sid]);

            $db->prepare(
                'INSERT INTO student_promotions
                    (student_id, from_semester, to_semester, from_academic_year, to_academic_year, promotion_date, status, approved_by, remarks)
                 VALUES (:sid, :from_sem, :to_sem, :from_year, :to_year, CURDATE(), :status, :by, :remarks)'
            )->execute([
                ':sid'       => $sid,
                ':from_sem'  => $currentSemName,
                ':to_sem'    => (string) $nextSem['semester_name'],
                ':from_year' => $fromYear,
                ':to_year'   => $toYear,
                ':status'    => 'approved',
                ':by'        => $userId > 0 ? $userId : null,
                ':remarks'   => 'Bulk promotion via Examination module.',
            ]);

            $db->commit();
            $promoted++;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $skipped++;
        }
    }

    require_once __DIR__ . '/../../includes/activity.php';
    log_activity('Examination', 'Students Promoted', 'students', null, 'Promoted ' . $promoted . ' student(s) to next semester (dept ' . $departmentId . ', session ' . $sessionId . ')');

    $_SESSION['success'] = $promoted . ' student(s) promoted successfully' . ($skipped > 0 ? '. ' . $skipped . ' skipped (already in last semester or missing data).' : '.');
    header('Location: index.php?department_id=' . $departmentId . '&session_id=' . $sessionId);
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Student Promotion</h4>
            <p style="color:var(--text-secondary);font-size:13px;margin:2px 0 0;">Select a department and session to load students, then bulk-promote the selected ones.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-content">
            <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="min-width:200px;">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['department_id'] ?>" <?= $departmentId === (int) $dept['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width:180px;">
                    <label class="form-label">Session</label>
                    <select name="session_id" id="session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= (int) $sess['session_id'] ?>" <?= $sessionId === (int) $sess['session_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sess['session_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Load Students</button>
            </form>
        </div>
    </div>

    <?php if (!$filtersSet): ?>
        <div class="card">
            <div class="card-content">
                <div class="empty-state">
                    <div class="empty-state-icon">&#128101;</div>
                    <p class="empty-state-text">Select a department and session to load eligible students</p>
                </div>
            </div>
        </div>
    <?php elseif (empty($students)): ?>
        <div class="card">
            <div class="card-content">
                <div class="empty-state">
                    <div class="empty-state-icon">&#128101;</div>
                    <p class="empty-state-text">No active students found for the selected department and session</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h5 style="margin:0;">Eligible Students</h5>
                    <span class="status-badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">
                        <?= count($students) ?> students found
                    </span>
                </div>
            </div>
            <div class="card-content">
                <form method="post" onsubmit="return confirm('Are you sure you want to promote the selected students to the next semester?')">
                    <input type="hidden" name="department_id" value="<?= (int) $departmentId ?>">
                    <input type="hidden" name="session_id" value="<?= (int) $sessionId ?>">

                    <div style="margin-bottom:1rem;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline" onclick="selectAll()">
                            <i class="bi bi-check-all"></i> Select All
                        </button>
                        <button type="button" class="btn btn-outline" onclick="deselectAll()">
                            <i class="bi bi-x-circle"></i> Deselect All
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCheckboxes()"></th>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Section</th>
                                    <th>Current Semester</th>
                                    <th>Batch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="student_ids[]" value="<?= (int) $student['student_id'] ?>" class="student-checkbox">
                                        </td>
                                        <td><?= htmlspecialchars((string) $student['roll_no']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($student['full_name']) ?></strong><br>
                                            <small style="color:var(--text-secondary);">ID: <?= (int) $student['student_id'] ?></small>
                                        </td>
                                        <td><?= $student['program_name'] ? htmlspecialchars($student['program_name']) : '-' ?></td>
                                        <td><?= $student['section_name'] ? htmlspecialchars($student['section_name']) : '-' ?></td>
                                        <td>
                                            <span class="status-badge badge-exam-mid"><?= htmlspecialchars((string) $student['semester_name']) ?></span>
                                        </td>
                                        <td><?= $student['batch_year'] ? (int) $student['batch_year'] : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert" style="background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);margin-top:1rem;">
                        <i class="bi bi-info-circle"></i>
                        Selected students will be promoted to the <strong>next semester</strong> in the selected department (<?= htmlspecialchars((string) ($selectedSession['session_name'] ?? '')) ?>).
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="promote_students" class="btn btn-primary">
                            <i class="bi bi-arrow-up-circle"></i> Promote Selected Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAllCheckbox');
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = selectAll.checked);
}
function selectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    const el = document.getElementById('selectAllCheckbox');
    if (el) el.checked = true;
}
function deselectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    const el = document.getElementById('selectAllCheckbox');
    if (el) el.checked = false;
}
</script>

<?php include '../includes/footer.php'; ?>
