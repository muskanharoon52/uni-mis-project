<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$db = db();
$pageTitle = 'Manage Exams';
$activePage = 'exams';
$teacher = current_user();
$teacherId = (int) ($teacher['teacher_id'] ?? 0);

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
            $_SESSION['message'] = 'Cannot publish — no questions mapped. Go to Question Mapping first.';
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

    $payload = [
        'exam_code'          => trim((string) $_POST['exam_code']),
        'course_id'          => (int) $_POST['course_id'],
        'teacher_id'         => $teacherId,
        'title'              => trim((string) $_POST['title']),
        'exam_type'          => (string) $_POST['exam_type'],
        'instructions'       => trim((string) ($_POST['instructions'] ?? '')),
        'duration_minutes'   => (int) $_POST['duration_minutes'],
        'total_questions'    => (int) $_POST['total_questions'],
        'total_marks'        => (float) $_POST['total_marks'],
        'passing_marks'      => (float) $_POST['passing_marks'],
        'selection_mode'     => (string) $_POST['selection_mode'],
        'negative_marking'   => (float) ($_POST['negative_marking'] ?? 0),
        'shuffle_questions'  => isset($_POST['shuffle_questions']) ? 1 : 0,
        'shuffle_options'    => isset($_POST['shuffle_options']) ? 1 : 0,
        'allow_review'       => isset($_POST['allow_review']) ? 1 : 0,
        'status'             => (string) ($_POST['status'] ?? 'Draft'),
    ];

    if (!empty($_POST['exam_id'])) {
        $payload['exam_id'] = (int) $_POST['exam_id'];
        $stmt = $db->prepare('UPDATE sbe_exams SET exam_code = :exam_code, course_id = :course_id, teacher_id = :teacher_id, title = :title, exam_type = :exam_type, instructions = :instructions, duration_minutes = :duration_minutes, total_questions = :total_questions, total_marks = :total_marks, passing_marks = :passing_marks, selection_mode = :selection_mode, negative_marking = :negative_marking, shuffle_questions = :shuffle_questions, shuffle_options = :shuffle_options, allow_review = :allow_review, status = :status WHERE exam_id = :exam_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Exam updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO sbe_exams (exam_code, course_id, teacher_id, title, exam_type, instructions, duration_minutes, total_questions, total_marks, passing_marks, selection_mode, negative_marking, shuffle_questions, shuffle_options, allow_review, status) VALUES (:exam_code, :course_id, :teacher_id, :title, :exam_type, :instructions, :duration_minutes, :total_questions, :total_marks, :passing_marks, :selection_mode, :negative_marking, :shuffle_questions, :shuffle_options, :allow_review, :status)');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Exam created successfully.';
    }

    redirect('exams.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'exam_id'            => null,
    'exam_code'          => '',
    'course_id'          => '',
    'title'              => '',
    'exam_type'          => 'Quiz',
    'instructions'       => '',
    'duration_minutes'   => 60,
    'total_questions'    => 20,
    'total_marks'        => 20,
    'passing_marks'      => 10,
    'selection_mode'     => 'Manual',
    'negative_marking'   => 0,
    'shuffle_questions'  => 0,
    'shuffle_options'    => 0,
    'allow_review'       => 1,
    'status'             => 'Draft',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM sbe_exams WHERE exam_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$courses = $db->query('SELECT course_id, course_title, course_code FROM courses ORDER BY course_title')->fetchAll();
$exams = $db->query('SELECT e.*, c.course_code, (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS mapped_questions, (SELECT COUNT(*) FROM sbe_exam_schedule es WHERE es.exam_id = e.exam_id) AS schedule_count FROM sbe_exams e LEFT JOIN courses c ON c.course_id = e.course_id ORDER BY e.exam_id DESC LIMIT 50')->fetchAll();

if (!$form['exam_id'] && empty($form['exam_code'])) {
    $courseCode = 'EXAM';
    $examType = strtoupper(substr($form['exam_type'] ?? 'QUIZ', 0, 3));
    $seqRow = $db->query("SELECT COUNT(*) + 1 AS seq FROM sbe_exams WHERE exam_type = '" . ($form['exam_type'] ?? 'Quiz') . "'")->fetch();
    $seq = str_pad((string)($seqRow['seq'] ?? 1), 2, '0', STR_PAD_LEFT);
    $form['exam_code'] = $courseCode . '-' . $examType . '-' . $seq;
}

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Manage Exams</h2>
            <p>Create exam definitions. After creating, map questions via <a href="exam-questions.php">Question Mapping</a>, then schedule via <a href="exam-schedule.php">Exam Schedule</a>.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="teacher-home.php">&larr; Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 24px;">
        <form method="post">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="exam_id" value="<?= e((string) old($form, 'exam_id', '')) ?>">

            <h3 style="margin:0 0 4px;"><?= $form['exam_id'] ? 'Edit Exam' : 'Register New Exam' ?></h3>
            <p class="small" style="margin:0 0 16px;">Define the exam structure, marks, and behaviour.</p>

            <div class="form-group-title">Basic Info</div>
            <div class="form-grid">
                <div class="field">
                    <label>Exam Code <span class="small">(auto-generated)</span></label>
                    <input type="text" name="exam_code" required value="<?= e((string) old($form, 'exam_code')) ?>" placeholder="Auto-generated">
                </div>
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" required value="<?= e((string) old($form, 'title')) ?>" placeholder="e.g. Midterm Quiz - OS">
                </div>
                <div class="field">
                    <label>Course</label>
                    <select name="course_id" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= (int) $course['course_id'] ?>" <?= (string) old($form, 'course_id') === (string) $course['course_id'] ? 'selected' : '' ?>>
                                    <?= e($course['course_code']) ?> &mdash; <?= e($course['course_title']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($courses)): ?>
                            <option value="1" <?= old($form, 'course_id') === '1' ? 'selected' : '' ?>>1 &mdash; Default Course</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Exam Type</label>
                    <select name="exam_type" required>
                        <?php foreach (['Quiz', 'Mid', 'Final', 'Practice', 'Assignment Test'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= old($form, 'exam_type') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        <?php foreach (['Draft', 'Published', 'Closed', 'Archived'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-title">Marks & Time</div>
            <div class="form-grid">
                <div class="field">
                    <label>Duration (minutes)</label>
                    <input type="number" min="1" name="duration_minutes" required value="<?= e((string) old($form, 'duration_minutes')) ?>">
                </div>
                <div class="field">
                    <label>Total Questions</label>
                    <input type="number" min="1" name="total_questions" required value="<?= e((string) old($form, 'total_questions')) ?>">
                </div>
                <div class="field">
                    <label>Total Marks</label>
                    <input type="number" min="0" step="0.5" name="total_marks" required value="<?= e((string) old($form, 'total_marks')) ?>">
                </div>
                <div class="field">
                    <label>Passing Marks</label>
                    <input type="number" min="0" step="0.5" name="passing_marks" required value="<?= e((string) old($form, 'passing_marks')) ?>">
                </div>
                <div class="field">
                    <label>Negative Marking</label>
                    <input type="number" min="0" step="0.25" name="negative_marking" value="<?= e((string) old($form, 'negative_marking')) ?>" placeholder="0">
                </div>
                <div class="field">
                    <label>Selection Mode</label>
                    <select name="selection_mode" required>
                        <?php foreach (['Manual', 'Random'] as $mode): ?>
                            <option value="<?= e($mode) ?>" <?= old($form, 'selection_mode') === $mode ? 'selected' : '' ?>><?= e($mode) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-title">Options</div>
            <div class="form-grid">
                <div class="field" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="shuffle_questions" id="shuffle_questions" <?= old($form, 'shuffle_questions') ? 'checked' : '' ?>>
                    <label for="shuffle_questions" style="margin:0;">Shuffle Questions</label>
                </div>
                <div class="field" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="shuffle_options" id="shuffle_options" <?= old($form, 'shuffle_options') ? 'checked' : '' ?>>
                    <label for="shuffle_options" style="margin:0;">Shuffle Options</label>
                </div>
                <div class="field" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="allow_review" id="allow_review" <?= old($form, 'allow_review') ? 'checked' : '' ?>>
                    <label for="allow_review" style="margin:0;">Allow Review Before Submit</label>
                </div>
            </div>

            <div class="form-group-title">Instructions <span class="small">(optional)</span></div>
            <div class="form-grid">
                <div class="field" style="grid-column:1 / -1;">
                    <textarea name="instructions" style="min-height:80px;" placeholder="e.g. Answer all questions. Each MCQ carries 1 mark."><?= e((string) old($form, 'instructions')) ?></textarea>
                </div>
            </div>

            <div class="actions" style="margin-top:18px;">
                <button class="btn btn-primary" type="submit"><?= $form['exam_id'] ? 'Update Exam' : 'Register Exam' ?></button>
                <?php if ($form['exam_id']): ?>
                    <a class="btn btn-ghost" href="exams.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-card page-section">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h3 style="margin:0 0 4px;">Registered Exams</h3>
                <p class="small" style="margin:0 0 16px;">All your exam definitions. Add questions via <a href="question-bank.php">Question Bank</a> and map them via <a href="exam-questions.php">Manual Mapping</a>.</p>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Marks</th>
                        <th>Questions</th>
                        <th>Mapped</th>
                        <th>Schedules</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <span class="empty-icon">&#128233;</span>
                                <p>No exams registered yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><span class="badge badge-manual"><?= e($exam['exam_code']) ?></span></td>
                            <td style="font-weight:600; color:var(--text-strong);"><?= e($exam['title']) ?></td>
                            <td class="small"><?= e($exam['exam_type']) ?></td>
                            <td class="small"><?= (int) $exam['duration_minutes'] ?>m</td>
                            <td class="small"><?= number_format((float) $exam['total_marks'], 1) ?></td>
                            <td class="small"><?= (int) $exam['total_questions'] ?></td>
                            <td class="small">
                                <?php
                                $mapped = (int) $exam['mapped_questions'];
                                $required = (int) $exam['total_questions'];
                                if ($required > 0 && $mapped >= $required) {
                                    echo '<span class="badge active">' . $mapped . '/' . $required . '</span>';
                                } elseif ($mapped > 0) {
                                    echo '<span class="badge draft">' . $mapped . '/' . $required . '</span>';
                                } else {
                                    echo '<span class="badge inactive">0/' . $required . '</span>';
                                }
                                ?>
                            </td>
                            <td class="small"><?= (int) $exam['schedule_count'] ?></td>
                            <td><span class="badge badge-<?= e(strtolower($exam['status'])) ?>"><?= e($exam['status']) ?></span></td>
                            <td>
                                <div class="actions" style="gap:4px;">
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $exam['exam_id'] ?>">Edit</a>
                                    <a class="btn btn-ghost btn-sm" href="exam-questions.php?exam_id=<?= (int) $exam['exam_id'] ?>">Map Qs</a>
                                    <?php if ($exam['status'] === 'Draft'): ?>
                                        <?php if ((int) $exam['mapped_questions'] === 0): ?>
                                            <span class="btn btn-ghost btn-sm" style="opacity:0.5; cursor:not-allowed;" title="Map questions first">Publish</span>
                                        <?php else: ?>
                                            <form method="post" style="display:inline; margin:0;">
                                                <input type="hidden" name="action" value="publish">
                                                <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
                                                <button class="btn btn-primary btn-sm" type="submit">Publish</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this exam and all its questions/schedules?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
