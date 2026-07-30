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

// ============================================
// FIX: Ensure we have a valid teacher_id from the teachers table
// ============================================
if ($teacherId === 0) {
    // If the logged-in user isn't linked to a teacher, try to fetch the first active teacher
    $fallbackTeacher = $db->query("SELECT teacher_id FROM teachers WHERE status = 'Active' LIMIT 1")->fetch();
    if ($fallbackTeacher) {
        $teacherId = (int) $fallbackTeacher['teacher_id'];
    } else {
        // If there are no teachers at all, show a readable error instead of a crash
        die("Error: No active teachers found in the database. Please add a teacher to the 'teachers' table first.");
    }
}
// ============================================

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

    // Validate course_id exists before proceeding
    $courseId = (int) $_POST['course_id'];
    if ($courseId <= 0) {
        $_SESSION['message'] = 'Please select a valid course.';
        redirect('exams.php');
    }

    // Check if the course exists in the database
    $checkCourse = $db->prepare('SELECT course_id FROM courses WHERE course_id = :id');
    $checkCourse->execute([':id' => $courseId]);
    if (!$checkCourse->fetch()) {
        $_SESSION['message'] = 'Selected course does not exist. Please select a valid course.';
        redirect('exams.php');
    }

    $payload = [
        'exam_code'          => trim((string) $_POST['exam_code']),
        'course_id'          => $courseId,
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

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['exam_id'] ? 'Edit Exam' : 'Create Exam' ?></h3>
            <p class="small" style="margin:0 0 4px;">Define the exam structure. After saving, map questions and schedule it for students.</p>

            <form method="post">
                <input type="hidden" name="exam_id" value="<?= e((string) old($form, 'exam_id', '')) ?>">

                <div class="form-group-title">Basic Info</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Exam Code</label>
                        <input type="text" name="exam_code" required value="<?= e((string) old($form, 'exam_code')) ?>" placeholder="Auto-generated if empty">
                    </div>
                    <div class="field">
                        <label>Title</label>
                        <input type="text" name="title" required value="<?= e((string) old($form, 'title')) ?>" placeholder="e.g. Midterm CS101">
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
                        </select>
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="exam_type" required>
                            <?php foreach (['Quiz','Mid','Final','Practice','Assignment Test'] as $t): ?>
                                <option value="<?= $t ?>" <?= (string) old($form, 'exam_type') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="field">
                        <label>Selection Mode</label>
                        <select name="selection_mode" required>
                            <option value="Manual" <?= (string) old($form, 'selection_mode') === 'Manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="Random" <?= (string) old($form, 'selection_mode') === 'Random' ? 'selected' : '' ?>>Random</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Negative Marking</label>
                        <input type="number" name="negative_marking" min="0" step="0.25" value="<?= e((string) old($form, 'negative_marking', '0')) ?>">
                    </div>
                </div>

                <div class="form-group-title">Options</div>
                <div class="form-grid">
                    <div class="field">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="shuffle_questions" value="1" <?= (int) old($form, 'shuffle_questions', 0) ? 'checked' : '' ?>>
                            Shuffle Questions
                        </label>
                    </div>
                    <div class="field">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="shuffle_options" value="1" <?= (int) old($form, 'shuffle_options', 0) ? 'checked' : '' ?>>
                            Shuffle Options
                        </label>
                    </div>
                    <div class="field">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="allow_review" value="1" <?= (int) old($form, 'allow_review', 1) ? 'checked' : '' ?>>
                            Allow Review
                        </label>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['Draft','Published','Closed','Archived'] as $s): ?>
                                <option value="<?= $s ?>" <?= (string) old($form, 'status') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field" style="margin-top:8px;">
                    <label>Instructions (optional)</label>
                    <textarea name="instructions" rows="3" placeholder="Exam instructions for students..."><?= e((string) old($form, 'instructions')) ?></textarea>
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
                <h3>Exam Overview</h3>
            </div>
            <div style="padding:20px;">
                <?php
                $draftCount = 0;
                $publishedCount = 0;
                $totalCount = count($exams);
                foreach ($exams as $ex) {
                    if ($ex['status'] === 'Draft') $draftCount++;
                    elseif ($ex['status'] === 'Published') $publishedCount++;
                }
                ?>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
                    <div style="flex:1; min-width:60px; text-align:center; padding:14px 8px; border-radius:10px; background:var(--bg-panel); border:1px solid var(--border);">
                        <div style="font-size:1.5rem; font-weight:700; color:var(--text-strong);"><?= $totalCount ?></div>
                        <div class="small" style="margin-top:2px;">Total</div>
                    </div>
                    <div style="flex:1; min-width:60px; text-align:center; padding:14px 8px; border-radius:10px; background:var(--bg-panel); border:1px solid var(--border);">
                        <div style="font-size:1.5rem; font-weight:700; color:var(--success);"><?= $publishedCount ?></div>
                        <div class="small" style="margin-top:2px;">Published</div>
                    </div>
                    <div style="flex:1; min-width:60px; text-align:center; padding:14px 8px; border-radius:10px; background:var(--bg-panel); border:1px solid var(--border);">
                        <div style="font-size:1.5rem; font-weight:700; color:var(--warning);"><?= $draftCount ?></div>
                        <div class="small" style="margin-top:2px;">Draft</div>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <h4 style="margin:0 0 10px; font-size:0.85rem; color:var(--text-muted);">Quick Links</h4>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <a href="question-bank.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128218; Question Bank</a>
                        <a href="exam-questions.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128450; Map Questions to Exams</a>
                        <a href="exam-schedule.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128197; Schedule Exams</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Exam Registry</h3>
        <p class="small" style="margin:0 0 16px;">All exams stored in <strong>university_mis</strong>.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Questions</th>
                        <th>Marks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <span class="empty-icon">&#128233;</span>
                            <p>No exams yet. Create one using the form.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td><span class="badge badge-manual"><?= e($ex['exam_code']) ?></span></td>
                            <td class="small fw-700"><?= e(mb_strimwidth($ex['title'], 0, 30, '...')) ?></td>
                            <td class="small"><?= e($ex['course_code'] ?? 'N/A') ?></td>
                            <td class="small"><?= e($ex['exam_type']) ?></td>
                            <td class="small">
                                <?= (int) $ex['mapped_questions'] ?> mapped
                                <?php if ((int) $ex['mapped_questions'] === 0): ?>
                                    <span style="color:var(--danger);font-size:.7rem;">&#9888;</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e(number_format((float) $ex['total_marks'], 1)) ?></td>
                            <td><span class="badge badge-<?= e(strtolower($ex['status'])) ?>"><?= e($ex['status']) ?></span></td>
                            <td>
                                <div class="actions">
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
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this exam?');">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
