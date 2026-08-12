<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$db = db();
$pageTitle = 'Create Exam';
$activePage = 'exams';
$teacher = current_user();
$teacherId = (int) ($teacher['teacher_id'] ?? 0);

if ($teacherId === 0) {
    $fallbackTeacher = $db->query("SELECT teacher_id FROM teachers WHERE status = 'Active' LIMIT 1")->fetch();
    if ($fallbackTeacher) {
        $teacherId = (int) $fallbackTeacher['teacher_id'];
    } else {
        die("Error: No active teachers found in the database. Please add a teacher to the 'teachers' table first.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $examId = (int) $_POST['exam_id'];
        $db->prepare('DELETE FROM sbe_exam_questions WHERE exam_id = :id')->execute([':id' => $examId]);
        $db->prepare('DELETE FROM sbe_exams WHERE exam_id = :id')->execute([':id' => $examId]);
        $_SESSION['message'] = 'Exam deleted.';
        redirect('exams.php');
    }

    if ($action === 'publish') {
        $examId = (int) $_POST['exam_id'];
        $countStmt = $db->prepare('SELECT COUNT(*) FROM sbe_exam_questions WHERE exam_id = :id');
        $countStmt->execute([':id' => $examId]);
        if ((int) $countStmt->fetchColumn() === 0) {
            $_SESSION['message'] = 'Cannot publish — no questions added. Add questions to the exam first.';
            redirect('exams.php');
        }
        $db->prepare("UPDATE sbe_exams SET status = 'Published' WHERE exam_id = :id")->execute([':id' => $examId]);
        $_SESSION['message'] = 'Exam published. Students can now be scheduled for it.';
        redirect('exams.php');
    }

    if ($action === 'archive') {
        $examId = (int) $_POST['exam_id'];
        $db->prepare("UPDATE sbe_exams SET status = 'Archived' WHERE exam_id = :id")->execute([':id' => $examId]);
        $_SESSION['message'] = 'Exam archived.';
        redirect('exams.php');
    }

    $courseId = (int) $_POST['course_id'];
    if ($courseId <= 0) {
        $_SESSION['message'] = 'Please select a valid subject.';
        redirect('exams.php');
    }

    $checkCourse = $db->prepare('SELECT course_id FROM courses WHERE course_id = :id');
    $checkCourse->execute([':id' => $courseId]);
    if (!$checkCourse->fetch()) {
        $_SESSION['message'] = 'Selected subject does not exist. Please select a valid subject.';
        redirect('exams.php');
    }

    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $sectionId    = (int) ($_POST['section_id'] ?? 0);
    $batchYear    = (int) ($_POST['batch_year'] ?? 0);
    $title        = trim((string) ($_POST['title'] ?? ''));
    $examType     = (string) ($_POST['exam_type'] ?? 'Mid');
    $duration     = (int) ($_POST['duration_minutes'] ?? 60);
    $totalQuestions = (int) ($_POST['total_questions'] ?? 0);
    $totalMarks   = (float) ($_POST['total_marks'] ?? 0);
    $passingMarks = (float) ($_POST['passing_marks'] ?? 0);

    if ($title === '' || $departmentId <= 0 || $batchYear <= 0) {
        $_SESSION['message'] = 'Exam name, department and batch are required.';
        redirect('exams.php');
    }

    $payload = [
        'exam_code'        => trim((string) ($_POST['exam_code'] ?? '')),
        'course_id'        => $courseId,
        'department_id'    => $departmentId ?: null,
        'section_id'       => $sectionId ?: null,
        'batch_year'       => $batchYear ?: null,
        'teacher_id'       => $teacherId,
        'title'            => $title,
        'exam_type'        => $examType,
        'instructions'     => trim((string) ($_POST['instructions'] ?? '')),
        'duration_minutes' => max(1, $duration),
        'total_questions'  => max(1, $totalQuestions),
        'total_marks'      => $totalMarks,
        'passing_marks'    => $passingMarks,
        'selection_mode'   => (string) ($_POST['selection_mode'] ?? 'Manual'),
        'negative_marking' => (float) ($_POST['negative_marking'] ?? 0),
        'shuffle_questions'=> isset($_POST['shuffle_questions']) ? 1 : 0,
        'shuffle_options'  => isset($_POST['shuffle_options']) ? 1 : 0,
        'allow_review'     => isset($_POST['allow_review']) ? 1 : 0,
        'status'           => (string) ($_POST['status'] ?? 'Draft'),
    ];

    if (!empty($_POST['exam_id'])) {
        $payload['exam_id'] = (int) $_POST['exam_id'];
        $stmt = $db->prepare('UPDATE sbe_exams SET exam_code = :exam_code, course_id = :course_id, department_id = :department_id, section_id = :section_id, batch_year = :batch_year, teacher_id = :teacher_id, title = :title, exam_type = :exam_type, instructions = :instructions, duration_minutes = :duration_minutes, total_questions = :total_questions, total_marks = :total_marks, passing_marks = :passing_marks, selection_mode = :selection_mode, negative_marking = :negative_marking, shuffle_questions = :shuffle_questions, shuffle_options = :shuffle_options, allow_review = :allow_review, status = :status WHERE exam_id = :exam_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Exam updated successfully.';
        redirect('exams.php');
    }

    if ($payload['exam_code'] === '') {
        $courseRow = $db->prepare('SELECT course_code FROM courses WHERE course_id = :id');
        $courseRow->execute([':id' => $courseId]);
        $courseCode = (string) ($courseRow->fetchColumn() ?: 'EXAM');
        $examTypeShort = strtoupper(substr($examType, 0, 3));
        $seqRow = $db->prepare('SELECT COUNT(*) + 1 AS seq FROM sbe_exams WHERE course_id = :course_id');
        $seqRow->execute([':course_id' => $courseId]);
        $seq = str_pad((string) ($seqRow->fetchColumn() ?: 1), 2, '0', STR_PAD_LEFT);
        $payload['exam_code'] = $courseCode . '-' . $examTypeShort . '-' . $seq;
    }

    $stmt = $db->prepare('INSERT INTO sbe_exams (exam_code, course_id, department_id, section_id, batch_year, teacher_id, title, exam_type, instructions, duration_minutes, total_questions, total_marks, passing_marks, selection_mode, negative_marking, shuffle_questions, shuffle_options, allow_review, status) VALUES (:exam_code, :course_id, :department_id, :section_id, :batch_year, :teacher_id, :title, :exam_type, :instructions, :duration_minutes, :total_questions, :total_marks, :passing_marks, :selection_mode, :negative_marking, :shuffle_questions, :shuffle_options, :allow_review, :status)');
    $stmt->execute($payload);
    $newExamId = (int) $db->lastInsertId();
    $_SESSION['message'] = 'Exam created. Now add questions to it (upload a PDF or add manually).';
    redirect('exam-questions.php?exam_id=' . $newExamId);
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'exam_id'          => null,
    'exam_code'        => '',
    'course_id'        => '',
    'department_id'    => '',
    'section_id'       => '',
    'batch_year'       => '',
    'title'            => '',
    'exam_type'        => 'Quiz',
    'instructions'     => '',
    'duration_minutes' => 60,
    'total_questions'  => 20,
    'total_marks'      => 20,
    'passing_marks'    => 10,
    'selection_mode'   => 'Manual',
    'negative_marking' => 0,
    'shuffle_questions'=> 0,
    'shuffle_options'  => 0,
    'allow_review'     => 1,
    'status'           => 'Draft',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM sbe_exams WHERE exam_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$departments = $db->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$sections = $db->query('SELECT s.section_id, s.section_name, s.program_id, p.department_id FROM sections s LEFT JOIN programs p ON p.program_id = s.program_id ORDER BY s.section_name')->fetchAll();
$courses = $db->query('SELECT course_id, course_title, course_code, department_id FROM courses ORDER BY course_title')->fetchAll();
$batches = $db->query('SELECT DISTINCT batch_year FROM students WHERE batch_year IS NOT NULL ORDER BY batch_year DESC')->fetchAll();

$exams = $db->query("SELECT e.*, c.course_code, c.course_title, d.department_name, s.section_name, (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS mapped_questions, (SELECT COUNT(*) FROM sbe_exam_schedule es WHERE es.exam_id = e.exam_id) AS schedule_count FROM sbe_exams e LEFT JOIN courses c ON c.course_id = e.course_id LEFT JOIN departments d ON d.department_id = e.department_id LEFT JOIN sections s ON s.section_id = e.section_id ORDER BY e.exam_id DESC LIMIT 50")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2><?= $form['exam_id'] ? 'Edit Exam' : 'Create New Exam' ?></h2>
            <p>Set the exam name, department, section, batch and subject. After creating, you can upload a question bank PDF or add questions manually. Quiz, Assignment Test and Practice exams are scheduled by you; <strong>Mid and Final</strong> exams go to the Examination section for scheduling.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="teacher-home.php">&larr; Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['exam_id'] ? 'Edit Exam' : 'Step 1 — Exam Details' ?></h3>
            <p class="small" style="margin:0 0 4px;">Fill the details below and click <strong>Create Exam</strong>. You will then add questions to the exam.</p>

            <form method="post">
                <input type="hidden" name="exam_id" value="<?= e((string) old($form, 'exam_id', '')) ?>">

                <div class="form-group-title">Exam Info</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Exam Name</label>
                        <input type="text" name="title" required value="<?= e((string) old($form, 'title')) ?>" placeholder="e.g. Midterm — Computer Fundamentals">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Department</label>
                        <select name="department_id" id="department_id" required onchange="filterDept('department_id','section_id'); filterDept('department_id','course_id');">
                            <option value="">Select department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int) $d['department_id'] ?>" <?= (string) old($form, 'department_id') === (string) $d['department_id'] ? 'selected' : '' ?>>
                                    <?= e($d['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Section</label>
                        <select name="section_id" id="section_id" required>
                            <option value="">Select section</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int) $s['section_id'] ?>" data-dept="<?= (int) $s['department_id'] ?>" <?= (string) old($form, 'section_id') === (string) $s['section_id'] ? 'selected' : '' ?>>
                                    <?= e($s['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Batch</label>
                        <select name="batch_year" required>
                            <option value="">Select batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= (int) $b['batch_year'] ?>" <?= (string) old($form, 'batch_year') === (string) $b['batch_year'] ? 'selected' : '' ?>>
                                    <?= (int) $b['batch_year'] ?> Batch
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Subject</label>
                        <select name="course_id" id="course_id" required>
                            <option value="">Select subject</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int) $course['course_id'] ?>" data-dept="<?= (int) $course['department_id'] ?>" <?= (string) old($form, 'course_id') === (string) $course['course_id'] ? 'selected' : '' ?>>
                                    <?= e($course['course_code']) ?> &mdash; <?= e($course['course_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Exam Type</label>
                        <select name="exam_type" required>
                            <?php foreach (['Quiz','Assignment Test','Practice','Mid','Final'] as $t): ?>
                                <option value="<?= $t ?>" <?= (string) old($form, 'exam_type') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="small" style="margin-top:4px;">Quiz, Assignment Test and Practice exams are scheduled by you. <strong>Mid and Final</strong> exams are scheduled by the Examination section.</p>
                    </div>
                </div>

                <div class="form-group-title">Structure</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Duration (min)</label>
                        <input type="number" name="duration_minutes" required min="1" value="<?= e((string) old($form, 'duration_minutes', '60')) ?>">
                    </div>
                    <div class="field">
                        <label>Total Questions</label>
                        <input type="number" name="total_questions" required min="1" value="<?= e((string) old($form, 'total_questions', '20')) ?>">
                    </div>
                    <div class="field">
                        <label>Total Marks</label>
                        <input type="number" name="total_marks" required min="1" step="0.5" value="<?= e((string) old($form, 'total_marks', '20')) ?>">
                    </div>
                    <div class="field">
                        <label>Passing Marks</label>
                        <input type="number" name="passing_marks" required min="0" step="0.5" value="<?= e((string) old($form, 'passing_marks', '10')) ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $form['exam_id'] ? 'Update Exam' : 'Create Exam' ?></button>
                    <?php if ($form['exam_id']): ?>
                        <a class="btn btn-ghost" href="exams.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Next Step</h3>
            </div>
            <div style="padding:20px;">
                <ol style="margin:0; padding-left:20px; line-height:2; font-size:.9rem; color:var(--text-strong);">
                    <li>Fill the exam details and click <strong>Create Exam</strong>.</li>
                    <li>You will be taken to the <strong>Add Questions</strong> page.</li>
                    <li>Choose one of two options:<br>
                        <span class="badge active">Upload Question Bank PDF</span> &mdash; questions are scanned out of the PDF, or<br>
                        <span class="badge draft">Add Manually</span> &mdash; enter questions one by one.
                    </li>
                    <li>Click <strong>Save Exam</strong> to finish.</li>
                </ol>
                <div style="margin-top:16px;">
                    <h4 style="margin:0 0 10px; font-size:0.85rem; color:var(--text-muted);">Quick Links</h4>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <a href="exam-questions.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128450; Add / Manage Questions</a>
                        <a href="schedule.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128197; Schedule Exams</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Exam Registry</h3>
        <p class="small" style="margin:0 0 16px;">All exams stored in <strong>university_mis</strong> and accessible from the Examination section.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Exam</th>
                        <th>Dept</th>
                        <th>Section</th>
                        <th>Batch</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="10">
                        <div class="empty-state">
                            <span class="empty-icon">&#128233;</span>
                            <p>No exams yet. Create one using the form.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td><span class="badge badge-manual"><?= e($ex['exam_code']) ?></span></td>
                            <td class="small fw-700">
                                <?= e(mb_strimwidth($ex['title'], 0, 30, '...')) ?>
                                <?php if ((int) $ex['mapped_questions'] === 0): ?>
                                    <span style="color:var(--danger);font-size:.7rem;" title="No questions yet">&#9888;</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e($ex['department_name'] ?? '—') ?></td>
                            <td class="small"><?= e($ex['section_name'] ?? '—') ?></td>
                            <td class="small"><?= $ex['batch_year'] ? (int) $ex['batch_year'] : '—' ?></td>
                            <td class="small">
                                <?= e($ex['course_code'] ?? '') ?>
                                <?php if ($ex['course_title']): ?><div class="small" style="color:var(--text-muted);"><?= e(mb_strimwidth($ex['course_title'],0,22,'...')) ?></div><?php endif; ?>
                            </td>
                            <td class="small"><?= e($ex['exam_type']) ?></td>
                            <td class="small">
                                <a href="exam-questions.php?exam_id=<?= (int) $ex['exam_id'] ?>"><?= (int) $ex['mapped_questions'] ?> added</a>
                            </td>
                            <td><span class="badge badge-<?= e(strtolower($ex['status'])) ?>"><?= e($ex['status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost btn-sm" href="exam-questions.php?exam_id=<?= (int) $ex['exam_id'] ?>">Questions</a>
                                    <?php if (in_array($ex['exam_type'], ['Quiz', 'Assignment Test', 'Practice'], true)): ?>
                                        <a class="btn btn-ghost btn-sm" href="schedule.php?exam_id=<?= (int) $ex['exam_id'] ?>">Schedule</a>
                                    <?php elseif ((int) $ex['schedule_count'] > 0): ?>
                                        <span class="badge badge-approved">Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge badge-scheduled" title="Mid and Final exams are scheduled by the Examination section.">Sent to Examination</span>
                                    <?php endif; ?>
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $ex['exam_id'] ?>">Edit</a>
                                    <?php if ($ex['status'] === 'Draft' && (int) $ex['mapped_questions'] > 0): ?>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="publish">
                                            <input type="hidden" name="exam_id" value="<?= (int) $ex['exam_id'] ?>">
                                            <button class="btn btn-primary btn-sm" type="submit">Publish</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($ex['status'] !== 'Archived'): ?>
                                        <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Archive this exam?');">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="exam_id" value="<?= (int) $ex['exam_id'] ?>">
                                            <button class="btn btn-ghost btn-sm" type="submit">Archive</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this exam and its questions?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="exam_id" value="<?= (int) $ex['exam_id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function filterDept(deptId, targetId) {
    var dept = document.getElementById(deptId).value;
    var sel = document.getElementById(targetId);
    for (var i = 0; i < sel.options.length; i++) {
        var opt = sel.options[i];
        if (opt.value === '') { opt.style.display = ''; continue; }
        opt.style.display = (dept === '' || opt.getAttribute('data-dept') === dept) ? '' : 'none';
    }
}
document.addEventListener('DOMContentLoaded', function () {
    var d = document.getElementById('department_id');
    if (d && d.value !== '') { filterDept('department_id', 'section_id'); filterDept('department_id', 'course_id'); }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
