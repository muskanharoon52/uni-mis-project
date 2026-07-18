<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Question Bank';
$activePage = 'question_bank';
$db = db();
$teacher = current_user();
$teacherId = (int) ($teacher['teacher_id'] ?? 0);
$message = null;

$defaultCourseId = (int) ($_SESSION['qb_last_course_id'] ?? 0);
$defaultTopic    = $_SESSION['qb_last_topic'] ?? '';

$form = [
    'question_id'     => null,
    'course_id'       => $defaultCourseId ?: '',
    'teacher_id'      => $teacherId,
    'topic'           => $defaultTopic,
    'question_text'   => '',
    'option_a'        => '',
    'option_b'        => '',
    'option_c'        => '',
    'option_d'        => '',
    'correct_option'  => 'A',
    'explanation'     => '',
    'marks'           => '1.00',
    'difficulty_level'=> 'Medium',
    'status'          => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'toggle_status') {
        $stmt = $db->prepare('UPDATE sbe_question_bank SET status = CASE status WHEN \'Active\' THEN \'Inactive\' ELSE \'Active\' END WHERE question_id = :id');
        $stmt->execute([':id' => (int) $_POST['question_id']]);
        $message = 'Question status updated.';
    } else {
        $data = [
            'course_id'       => (int) $_POST['course_id'],
            'teacher_id'      => $teacherId,
            'topic'           => trim((string) $_POST['topic']),
            'question_text'   => trim((string) $_POST['question_text']),
            'option_a'        => trim((string) $_POST['option_a']),
            'option_b'        => trim((string) $_POST['option_b']),
            'option_c'        => trim((string) $_POST['option_c']),
            'option_d'        => trim((string) $_POST['option_d']),
            'correct_option'  => strtoupper(trim((string) $_POST['correct_option'])),
            'explanation'     => trim((string) $_POST['explanation']),
            'marks'           => (float) $_POST['marks'],
            'difficulty_level'=> (string) $_POST['difficulty_level'],
            'status'          => 'Active',
        ];

        if (!empty($_POST['question_id'])) {
            $sql = 'UPDATE sbe_question_bank SET course_id = :course_id, teacher_id = :teacher_id, topic = :topic, question_text = :question_text, option_a = :option_a, option_b = :option_b, option_c = :option_c, option_d = :option_d, correct_option = :correct_option, explanation = :explanation, marks = :marks, difficulty_level = :difficulty_level, status = :status WHERE question_id = :question_id';
            $data['question_id'] = (int) $_POST['question_id'];
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            $message = 'Question updated successfully.';
        } else {
            $sql = 'INSERT INTO sbe_question_bank (course_id, teacher_id, topic, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, difficulty_level, status) VALUES (:course_id, :teacher_id, :topic, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option, :explanation, :marks, :difficulty_level, :status)';
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            $message = 'Question added successfully.';
        }

        $_SESSION['qb_last_course_id'] = $data['course_id'];
        $_SESSION['qb_last_topic'] = $data['topic'];
    }

    $_SESSION['message'] = $message;
    redirect('question-bank.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM sbe_question_bank WHERE question_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$courses   = $db->query('SELECT course_id, course_code, course_title FROM courses WHERE status = \'Active\' ORDER BY course_code')->fetchAll();
$questions = $db->query('SELECT qb.*, c.course_code FROM sbe_question_bank qb LEFT JOIN courses c ON c.course_id = qb.course_id ORDER BY qb.question_id DESC LIMIT 50')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Question Bank</h2>
            <p>Create reusable MCQs. Course and your ID are pre-filled &mdash; just pick a topic and start adding questions.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="teacher-home.php">&larr; Dashboard</a>
            <a class="btn btn-primary" href="exams.php">Next: Create Exam &rarr;</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['question_id'] ? 'Edit Question' : 'Add New Question' ?></h3>
            <p class="small" style="margin:0 0 4px;">Course and topic stay filled after saving so you can add multiple questions quickly.</p>

            <form method="post">
                <input type="hidden" name="question_id" value="<?= e((string) old($form, 'question_id', '')) ?>">
                <input type="hidden" name="teacher_id" value="<?= (int) $teacherId ?>">

                <div class="form-group-title">Course & Topic</div>
                <div class="form-grid">
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
                        <label>Topic</label>
                        <input type="text" name="topic" required value="<?= e((string) old($form, 'topic')) ?>" placeholder="e.g. Normalization, Loops, Inheritance">
                    </div>
                    <div class="field">
                        <label>Difficulty</label>
                        <select name="difficulty_level" required>
                            <?php foreach (['Easy', 'Medium', 'Hard'] as $level): ?>
                                <option value="<?= e($level) ?>" <?= old($form, 'difficulty_level') === $level ? 'selected' : '' ?>><?= e($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-title">Question</div>
                <div class="field">
                    <label>Question Text</label>
                    <textarea name="question_text" required style="min-height:90px;"><?= e((string) old($form, 'question_text')) ?></textarea>
                </div>

                <div class="form-group-title">Answer Options</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Option A</label>
                        <input type="text" name="option_a" required value="<?= e((string) old($form, 'option_a')) ?>">
                    </div>
                    <div class="field">
                        <label>Option B</label>
                        <input type="text" name="option_b" required value="<?= e((string) old($form, 'option_b')) ?>">
                    </div>
                    <div class="field">
                        <label>Option C</label>
                        <input type="text" name="option_c" required value="<?= e((string) old($form, 'option_c')) ?>">
                    </div>
                    <div class="field">
                        <label>Option D</label>
                        <input type="text" name="option_d" required value="<?= e((string) old($form, 'option_d')) ?>">
                    </div>
                </div>

                <div class="form-group-title">Settings</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Correct Answer</label>
                        <select name="correct_option" id="correct_option" required>
                            <?php
                            $opts = [
                                'A' => old($form, 'option_a'),
                                'B' => old($form, 'option_b'),
                                'C' => old($form, 'option_c'),
                                'D' => old($form, 'option_d'),
                            ];
                            foreach (['A', 'B', 'C', 'D'] as $letter):
                                $label = $opts[$letter] !== '' ? $letter . ' — ' . mb_strimwidth((string)$opts[$letter], 0, 40, '...') : $letter;
                            ?>
                                <option value="<?= $letter ?>" <?= old($form, 'correct_option') === $letter ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Marks</label>
                        <input type="number" step="0.5" min="0.5" name="marks" required value="<?= e((string) old($form, 'marks')) ?>">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Explanation <span class="small">(optional — shown after submission)</span></label>
                        <textarea name="explanation" style="min-height:60px;"><?= e((string) old($form, 'explanation')) ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['question_id'] ? 'Update Question' : 'Save Question' ?></button>
                    <?php if ($form['question_id']): ?>
                        <a class="btn btn-ghost" href="question-bank.php">Cancel</a>
                    <?php else: ?>
                        <span class="small" style="color:var(--text-muted); align-self:center;">Course & topic stay filled for the next question</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-card">
            <h3 style="margin:0 0 4px;">Latest Questions</h3>
            <p class="small" style="margin:0 0 16px;">Showing the 50 most recent MCQs.</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course</th>
                            <th>Topic</th>
                            <th>Question</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <span class="empty-icon">&#128233;</span>
                                <p>No questions yet. Add one using the form.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td class="small">#<?= (int) $question['question_id'] ?></td>
                                <td class="small"><span class="badge badge-manual"><?= e($question['course_code'] ?? '?') ?></span></td>
                                <td style="font-weight:600; color:var(--text-strong);"><?= e($question['topic']) ?></td>
                                <td class="small"><?= e(mb_strimwidth($question['question_text'], 0, 40, '...')) ?></td>
                                <td class="small"><?= e(number_format((float) $question['marks'], 1)) ?></td>
                                <td><span class="badge badge-<?= e(strtolower($question['status'])) ?>"><?= e($question['status']) ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $question['question_id'] ?>">Edit</a>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="question_id" value="<?= (int) $question['question_id'] ?>">
                                            <button class="btn btn-sm <?= $question['status'] === 'Active' ? 'btn-warning' : 'btn-ghost' ?>" type="submit">
                                                <?= $question['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('correct_option');
    if (!sel) return;
    var fields = {
        A: document.querySelector('[name="option_a"]'),
        B: document.querySelector('[name="option_b"]'),
        C: document.querySelector('[name="option_c"]'),
        D: document.querySelector('[name="option_d"]')
    };
    function updateLabels() {
        var selected = sel.value;
        for (var k in fields) {
            var txt = fields[k].value.trim();
            var label = txt ? k + ' \u2014 ' + txt.substring(0, 40) : k;
            var opt = sel.querySelector('option[value="' + k + '"]');
            if (opt) opt.textContent = label;
        }
    }
    for (var k in fields) {
        fields[k].addEventListener('input', updateLabels);
    }
    sel.addEventListener('focus', updateLabels);
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
