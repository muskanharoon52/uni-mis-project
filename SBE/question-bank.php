<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Question Bank';
$activePage = 'question_bank';
$db = db();
$message = null;

$form = [
    'question_id'     => null,
    'course_id'       => '',
    'teacher_id'      => '',
    'topic'           => '',
    'question_text'   => '',
    'option_a'        => '',
    'option_b'        => '',
    'option_c'        => '',
    'option_d'        => '',
    'correct_option'  => 'A',
    'explanation'     => '',
    'marks'           => '1.00',
    'difficulty_level'=> 'Medium',
    'status'          => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'toggle_status') {
        $stmt = $db->prepare('UPDATE question_bank SET status = CASE status WHEN "active" THEN "inactive" ELSE "active" END WHERE question_id = :id');
        $stmt->execute([':id' => (int) $_POST['question_id']]);
        $message = 'Question status updated.';
    } else {
        $data = [
            'course_id'       => (int) $_POST['course_id'],
            'teacher_id'      => (int) $_POST['teacher_id'],
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
            'status'          => (string) $_POST['status'],
        ];

        if (!empty($_POST['question_id'])) {
            $sql = 'UPDATE question_bank SET course_id = :course_id, teacher_id = :teacher_id, topic = :topic, question_text = :question_text, option_a = :option_a, option_b = :option_b, option_c = :option_c, option_d = :option_d, correct_option = :correct_option, explanation = :explanation, marks = :marks, difficulty_level = :difficulty_level, status = :status WHERE question_id = :question_id';
            $data['question_id'] = (int) $_POST['question_id'];
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            $message = 'Question updated successfully.';
        } else {
            $sql = 'INSERT INTO question_bank (course_id, teacher_id, topic, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, difficulty_level, status) VALUES (:course_id, :teacher_id, :topic, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option, :explanation, :marks, :difficulty_level, :status)';
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            $message = 'Question added successfully.';
        }
    }

    $_SESSION['message'] = $message;
    redirect('question-bank.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM question_bank WHERE question_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$questions = $db->query('SELECT * FROM question_bank ORDER BY question_id DESC LIMIT 50')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Question Bank</h2>
            <p>Create and maintain reusable MCQs for exams.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="index.php">← Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <!-- ── Form ── -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['question_id'] ? 'Edit Question' : 'Add New Question' ?></h3>
            <p class="small" style="margin:0 0 4px;">All fields follow the finalized schema.</p>

            <form method="post">
                <input type="hidden" name="question_id" value="<?= e((string) old($form, 'question_id', '')) ?>">

                <!-- Group: Identity -->
                <div class="form-group-title">📌 Identity</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Course ID</label>
                        <input type="number" name="course_id" required value="<?= e((string) old($form, 'course_id')) ?>" placeholder="From SSO">
                    </div>
                    <div class="field">
                        <label>Teacher ID</label>
                        <input type="number" name="teacher_id" required value="<?= e((string) old($form, 'teacher_id')) ?>" placeholder="From SSO">
                    </div>
                    <div class="field">
                        <label>Topic</label>
                        <input type="text" name="topic" required value="<?= e((string) old($form, 'topic')) ?>" placeholder="e.g. Normalization">
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

                <!-- Group: Question content -->
                <div class="form-group-title">❓ Question</div>
                <div class="field">
                    <label>Question Text</label>
                    <textarea name="question_text" required style="min-height:90px;"><?= e((string) old($form, 'question_text')) ?></textarea>
                </div>

                <!-- Group: Options -->
                <div class="form-group-title">🅐 Answer Options</div>
                <div class="form-grid">
                    <div class="field">
                        <label>🅐 Option A</label>
                        <input type="text" name="option_a" required value="<?= e((string) old($form, 'option_a')) ?>">
                    </div>
                    <div class="field">
                        <label>🅑 Option B</label>
                        <input type="text" name="option_b" required value="<?= e((string) old($form, 'option_b')) ?>">
                    </div>
                    <div class="field">
                        <label>🅒 Option C</label>
                        <input type="text" name="option_c" required value="<?= e((string) old($form, 'option_c')) ?>">
                    </div>
                    <div class="field">
                        <label>🅓 Option D</label>
                        <input type="text" name="option_d" required value="<?= e((string) old($form, 'option_d')) ?>">
                    </div>
                </div>

                <!-- Group: Settings -->
                <div class="form-group-title">⚙️ Settings</div>
                <div class="form-grid">
                    <div class="field">
                        <label>✅ Correct Option</label>
                        <select name="correct_option" required>
                            <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= old($form, 'correct_option') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Marks</label>
                        <input type="number" step="0.01" name="marks" required value="<?= e((string) old($form, 'marks')) ?>">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['active', 'inactive'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Explanation <span class="small">(optional)</span></label>
                        <textarea name="explanation" style="min-height:70px;"><?= e((string) old($form, 'explanation')) ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['question_id'] ? 'Update Question' : 'Save Question' ?></button>
                    <?php if ($form['question_id']): ?>
                        <a class="btn btn-ghost" href="question-bank.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── Table ── -->
        <div class="table-card">
            <h3 style="margin:0 0 4px;">Latest Questions</h3>
            <p class="small" style="margin:0 0 16px;">Showing the 50 most recent MCQs.</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Topic</th>
                            <th>Question</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No questions yet. Add one using the form.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td class="small">#<?= (int) $question['question_id'] ?></td>
                                <td style="font-weight:600; color:var(--text-strong);"><?= e($question['topic']) ?></td>
                                <td class="small"><?= e(mb_strimwidth($question['question_text'], 0, 50, '…')) ?></td>
                                <td class="small"><?= e(number_format((float) $question['marks'], 2)) ?></td>
                                <td><span class="badge <?= e($question['status']) ?>"><?= e($question['status']) ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $question['question_id'] ?>">Edit</a>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="question_id" value="<?= (int) $question['question_id'] ?>">
                                            <button class="btn btn-sm <?= $question['status'] === 'active' ? 'btn-warning' : 'btn-ghost' ?>" type="submit">
                                                <?= $question['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
