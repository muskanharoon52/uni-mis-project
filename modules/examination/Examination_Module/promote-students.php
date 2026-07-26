<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login();
$db = db();
$user = current_user();

$search = trim((string) ($_GET['search'] ?? ''));
$filterSemester = trim((string) ($_GET['semester'] ?? ''));
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'promote') {
        $studentIds = $_POST['student_ids'] ?? [];
        $toSemesterId = (int) ($_POST['to_semester_id'] ?? 0);
        $toSessionId = (int) ($_POST['to_session_id'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if (empty($studentIds) || $toSemesterId <= 0 || $toSessionId <= 0) {
            $message = 'Please select students, a target semester, and a target session.';
            $messageType = 'error';
        } else {
            $promoted = 0;
            $skipped = 0;

            $checkStmt = $db->prepare('SELECT student_id FROM student_promotions WHERE student_id = :student_id AND to_semester_id = :to_semester_id AND to_session_id = :to_session_id');
            $insStmt = $db->prepare('INSERT INTO student_promotions (student_id, from_semester_id, to_semester_id, from_session_id, to_session_id, promoted_by, remarks) VALUES (:student_id, :from_semester_id, :to_semester_id, :from_session_id, :to_session_id, :promoted_by, :remarks)');
            $updStmt = $db->prepare('UPDATE students SET current_semester_id = :to_semester_id, current_session_id = :to_session_id WHERE student_id = :student_id');

            $db->beginTransaction();

            try {
                foreach ($studentIds as $sid) {
                    $sid = (int) $sid;
                    if ($sid <= 0) continue;

                    $student = $db->query("SELECT current_semester_id, current_session_id FROM students WHERE student_id = $sid")->fetch();
                    if (!$student) { $skipped++; continue; }

                    $checkStmt->execute([
                        ':student_id' => $sid,
                        ':to_semester_id' => $toSemesterId,
                        ':to_session_id' => $toSessionId,
                    ]);
                    if ($checkStmt->fetch()) { $skipped++; continue; }

                    $insStmt->execute([
                        ':student_id' => $sid,
                        ':from_semester_id' => (int) $student['current_semester_id'],
                        ':to_semester_id' => $toSemesterId,
                        ':from_session_id' => (int) $student['current_session_id'],
                        ':to_session_id' => $toSessionId,
                        ':promoted_by' => 1,
                        ':remarks' => $remarks !== '' ? $remarks : null,
                    ]);

                    $updStmt->execute([
                        ':to_semester_id' => $toSemesterId,
                        ':to_session_id' => $toSessionId,
                        ':student_id' => $sid,
                    ]);

                    $promoted++;
                }

                $db->commit();
                $message = "Successfully promoted $promoted student(s)." . ($skipped > 0 ? " $skipped skipped (already promoted or invalid)." : '');
            } catch (Throwable $e) {
                $db->rollBack();
                $message = 'Promotion failed: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$semesters = $db->query('SELECT MIN(semester_id) AS semester_id, semester_name, semester_number FROM semesters GROUP BY semester_number, semester_name ORDER BY semester_number')->fetchAll();
$sessions = $db->query('SELECT session_id, session_name FROM sessions ORDER BY session_name')->fetchAll();

$sql = 'SELECT s.student_id, s.full_name, s.roll_no, s.father_name, s.contact_no, s.status AS student_status,
               sem.semester_name, sem.semester_number,
               sess.session_name,
               d.department_name
        FROM students s
        INNER JOIN semesters sem ON sem.semester_id = s.current_semester_id
        INNER JOIN sessions sess ON sess.session_id = s.current_session_id
        INNER JOIN departments d ON d.department_id = s.program_id
        WHERE s.status = \'Active\'';

$params = [];

if ($search !== '') {
    $sql .= ' AND (s.full_name LIKE :search OR CAST(s.student_id AS CHAR) LIKE :search OR s.roll_no LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($filterSemester !== '') {
    $sql .= ' AND s.current_semester_id = :semester_id';
    $params[':semester_id'] = $filterSemester;
}

$sql .= ' ORDER BY s.student_id ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Promote Students';
$activePage = 'promote_students';
require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">

    <div class="page-head">
        <div>
            <h2>Promote Students</h2>
            <p>Select students and move them to the next semester.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:18px;">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="card page-section animate-in animate-delay-1">
        <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div class="field" style="flex:1; min-width:200px; margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" placeholder="Student name, ID, or roll no..." value="<?= e($search) ?>">
            </div>
            <div class="field" style="min-width:180px; margin-bottom:0;">
                <label>Current Semester</label>
                <select name="semester">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?= e((string) $sem['semester_id']) ?>" <?= $filterSemester === (string) $sem['semester_id'] ? 'selected' : '' ?>><?= e($sem['semester_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="actions" style="margin-bottom:0;">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-ghost" href="promote-students.php">Reset</a>
            </div>
        </form>
    </div>

    <form method="post" id="promote-form">
        <input type="hidden" name="action" value="promote">

        <div class="card page-section animate-in animate-delay-2">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3>Select Students <span class="badge" style="margin-left:8px;"><?= count($students) ?></span></h3>
                    <p>Check the students you want to promote</p>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.querySelectorAll('.student-check').forEach(c => c.checked = true)">Select All</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.querySelectorAll('.student-check').forEach(c => c.checked = false)">Deselect All</button>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top:10px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="check-all" onchange="document.querySelectorAll('.student-check').forEach(c => c.checked = this.checked)"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Roll No</th>
                            <th>Father</th>
                            <th>Contact</th>
                            <th>Current Semester</th>
                            <th>Session</th>
                            <th>Program</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="9"><div class="empty-state"><span class="empty-icon">&#128101;</span><p>No active students found.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?= (int) $s['student_id'] ?>" class="student-check" style="cursor:pointer;"></td>
                                <td class="fw-700">#<?= (int) $s['student_id'] ?></td>
                                <td>
                                    <div class="fw-700" style="font-size:.88rem;"><?= e($s['full_name']) ?></div>
                                </td>
                                <td class="small"><?= e($s['roll_no'] ?? '') ?></td>
                                <td class="small"><?= e($s['father_name'] ?? '') ?></td>
                                <td class="small"><?= e($s['contact_no'] ?? '') ?></td>
                                <td><span class="badge teacher"><?= e($s['semester_name']) ?></span></td>
                                <td class="small"><?= e($s['session_name']) ?></td>
                                <td class="small"><?= e($s['department_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($students)): ?>
        <div class="card page-section animate-in animate-delay-3">
            <div class="card-header">
                <h3>Promotion Settings</h3>
                <p>Choose the target semester and session for the selected students</p>
            </div>
            <div class="form-grid" style="margin-top:16px;">
                <div class="field">
                    <label>Target Semester</label>
                    <select name="to_semester_id" required>
                        <option value="">Select semester...</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= e((string) $sem['semester_id']) ?>"><?= e($sem['semester_name']) ?> (Sem <?= (int) $sem['semester_number'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Target Session</label>
                    <select name="to_session_id" required>
                        <option value="">Select session...</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= e((string) $sess['session_id']) ?>"><?= e($sess['session_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g. Spring 2026 promotion batch">
                </div>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Are you sure you want to promote the selected students?')">Promote Selected Students</button>
            </div>
        </div>
        <?php endif; ?>
    </form>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
