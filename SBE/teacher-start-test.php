<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$db = db();
$pageTitle = 'Start Test';
$activePage = 'dashboard';
$teacher = current_user();

$scheduleOptions = $db->query("SELECT es.schedule_id, es.exam_id, es.class_id, es.exam_date, es.start_time, es.end_time, es.location, e.exam_code, e.title FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id WHERE e.selection_mode = 'manual' OR e.selection_mode = 'random' ORDER BY es.schedule_id DESC")->fetchAll();
$testSessions = $db->query('SELECT se.student_exam_id, se.exam_id, se.student_id, se.status, se.started_at, e.exam_code, e.title FROM student_exams se INNER JOIN exams e ON e.exam_id = se.exam_id ORDER BY se.student_exam_id DESC LIMIT 12')->fetchAll();
$latestQuestions = $db->query('SELECT question_id, topic, difficulty_level, status, question_text FROM question_bank ORDER BY question_id DESC LIMIT 12')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="page-head">
        <div>
            <h2>Start Test</h2>
            <p>Review exam structures and run trial previews before releasing to students.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="teacher-home.php">← Teacher Portal</a>
            <a class="btn btn-primary" href="login.php">Switch Role</a>
        </div>
    </div>

    <div class="grid-2 page-section">
        
        <div class="table-card">
            <h3 style="margin:0 0 4px;">Available Exam Sessions</h3>
            <p class="small" style="margin:0 0 16px;">Scheduled exams ready for active validation.</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($scheduleOptions)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <span class="empty-icon">📭</span>
                                    <p>No exam sessions scheduled.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($scheduleOptions as $row): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($row['exam_code']) ?></span>
                                    <div class="small" style="margin-top:3px;"><?= e($row['title']) ?></div>
                                </td>
                                <td class="small">Class <?= (int) $row['class_id'] ?></td>
                                <td class="small"><?= e($row['exam_date']) ?></td>
                                <td class="small"><?= e(substr((string) $row['start_time'], 0, 5)) ?> – <?= e(substr((string) $row['end_time'], 0, 5)) ?></td>
                                <td class="small"><?= e($row['location']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <h3 style="margin:0 0 4px;">Question Preview</h3>
            <p class="small" style="margin:0 0 16px;">Recent additions from the MCQ question bank.</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Topic</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($latestQuestions)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <span class="empty-icon">📭</span>
                                    <p>No questions inside question bank.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($latestQuestions as $question): ?>
                            <tr>
                                <td class="small">#<?= (int) $question['question_id'] ?></td>
                                <td style="font-weight:600; color:var(--text-strong);"><?= e($question['topic']) ?></td>
                                <td><span class="badge manual"><?= e($question['difficulty_level']) ?></span></td>
                                <td><span class="badge <?= e($question['status']) ?>"><?= e($question['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Recent Test Runs</h3>
        <p class="small" style="margin:0 0 16px;">Overview of all test attempts simulated by students/teachers.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Exam</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Started</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($testSessions)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No recent test runs detected.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testSessions as $session): ?>
                        <tr>
                            <td class="small">Attempt #<?= (int) $session['student_exam_id'] ?></td>
                            <td>
                                <span class="badge manual"><?= e($session['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($session['title']) ?></div>
                            </td>
                            <td class="small">Student #<?= (int) $session['student_id'] ?></td>
                            <td><span class="badge <?= e($session['status']) ?>"><?= e($session['status']) ?></span></td>
                            <td class="small"><?= e($session['started_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
