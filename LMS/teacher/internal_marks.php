<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'internal_marks';
$pageTitle = 'Internal Marks';
$message = '';
$error = '';
$components = internal_mark_components();
$componentTotals = [
    'assignment_1' => 5,
    'assignment_2' => 5,
    'assignment_3' => 5,
    'test_1' => 5,
    'test_2' => 5,
    'test_3' => 5,
    'presentation' => 10,
    'major_assignment' => 10,
    'mid_term' => 25,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = isset($_POST['finalize_row']) ? 'finalize' : (string) ($_POST['action'] ?? 'save_marks');

        if ($action === 'finalize') {
            [$courseId, $studentId] = array_map('intval', explode(':', (string) $_POST['finalize_row']));
                    if (!teacher_owns_course((int) $user['id'], $courseId)) {
                throw new RuntimeException('You cannot finalize this marks row.');
            }
            $stmt = db()->prepare(
                'INSERT INTO lms_mark_finalizations (course_id, student_user_id, is_finalized, finalized_at)
                 VALUES (?, ?, 1, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE is_finalized = 1, finalized_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$courseId, $studentId]);
            $message = 'Marks finalized.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO lms_marks (course_id, student_user_id, component, marks_obtained, total_marks)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), total_marks = VALUES(total_marks)'
            );

            foreach ($_POST['marks'] ?? [] as $studentId => $courseRows) {
                foreach ($courseRows as $courseId => $componentValues) {
                    $studentId = (int) $studentId;
                    $courseId = (int) $courseId;
            if (!teacher_owns_course((int) $user['id'], $courseId)) {
                        continue;
                    }
                    $finalizedStmt = db()->prepare('SELECT COUNT(*) FROM lms_mark_finalizations WHERE course_id = ? AND student_user_id = ? AND is_finalized = 1');
                    $finalizedStmt->execute([$courseId, $studentId]);
                    if ((int) $finalizedStmt->fetchColumn() > 0) {
                        continue;
                    }

                    foreach ($components as $component => $label) {
                        $maxMarks = (float) ($componentTotals[$component] ?? 100);
                        $marks = (float) ($componentValues[$component] ?? 0);
                        if ($marks < 0 || $marks > $maxMarks) {
                            throw new RuntimeException($label . ' must be between 0 and ' . $maxMarks . '.');
                        }
                        $stmt->execute([$courseId, $studentId, $component, $marks, $maxMarks]);
                    }
                }
            }
            $message = 'Internal marks saved.';
        }
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$rows = internal_mark_rows_for_teacher((int) $user['id']);

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Internal Marks — Spring 2026</h3></div>
    <div class="table-responsive">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_marks">
        <table class="marks-table">
            <tr>
                <th rowspan="2">Course ID</th>
                <th rowspan="2">Title</th>
                <th rowspan="2">Student</th>
                <th colspan="3">Assignments</th>
                <th colspan="3">Tests</th>
                <th rowspan="2">Presentation</th>
                <th rowspan="2">Major Assignment</th>
                <th rowspan="2">Mid Term</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Status</th>
                <th rowspan="2">Finalize</th>
            </tr>
            <tr>
                <th>1</th><th>2</th><th>3</th><th>1</th><th>2</th><th>3</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['code']) ?></td>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['student_name']) ?></td>
                    <?php foreach ($components as $component => $label): ?>
                        <td>
                            <input
                                class="marks-input"
                                name="marks[<?= (int) $row['student_id'] ?>][<?= (int) $row['course_id'] ?>][<?= e($component) ?>]"
                                type="number"
                                step="0.01"
                                min="0"
                                max="<?= e((string) ($componentTotals[$component] ?? 100)) ?>"
                                value="<?= e((string) $row['marks'][$component]) ?>"
                                <?= $row['is_finalized'] ? 'readonly' : '' ?>
                            >
                        </td>
                    <?php endforeach; ?>
                    <td><?= e((string) internal_mark_total($row)) ?></td>
                    <td><span class="badge <?= $row['is_finalized'] ? 'badge-inactive' : 'badge-active' ?>"><?= $row['is_finalized'] ? 'Finalized' : 'Not Finalized' ?></span></td>
                    <td>
                        <?php if (!$row['is_finalized']): ?>
                            <button class="btn btn-outline btn-sm" type="submit" name="finalize_row" value="<?= (int) $row['course_id'] ?>:<?= (int) $row['student_id'] ?>">Finalize</button>
                        <?php else: ?>
                            <span class="muted">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <button class="btn btn-primary" type="submit">Save Marks</button>
    </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
