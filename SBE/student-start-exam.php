<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['student']);

$db = db();
$student = current_user();
$studentId = (int) $student['login_id'];
$pageTitle = 'Start Exam Room';
$activePage = 'student_start_exam';

// Fetch schedules
$available = $db->query("SELECT es.schedule_id, es.exam_id, es.class_id, es.exam_date, es.start_time, es.end_time, es.location, e.exam_code, e.title, e.duration_minutes, e.total_questions, e.total_marks FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id WHERE e.status = 'published' AND es.status = 'ongoing' ORDER BY es.exam_date DESC, es.start_time DESC")->fetchAll();

// Fetch attempts
$attemptStmt = $db->prepare('SELECT se.student_exam_id, se.exam_id, se.status, se.started_at, se.percentage, e.exam_code, e.title FROM student_exams se INNER JOIN exams e ON e.exam_id = se.exam_id WHERE se.student_id = :student_id ORDER BY se.student_exam_id DESC LIMIT 8');
$attemptStmt->execute([':student_id' => $studentId]);
$myAttempts = $attemptStmt->fetchAll();

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <?php if ($message): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>
    <div class="page-head">
        <div>
            <h2>Start Exam Room</h2>
            <p>Select your scheduled exam session below to begin your online paper attempt.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-home.php">← Student Dashboard</a>
        </div>
    </div>

    <!-- Active Schedules Container -->
    <div class="page-section">
        <h3 class="mb-16">Scheduled Exam Sessions</h3>
        
        <?php if (empty($available)): ?>
            <div class="card">
                <div class="empty-state">
                    <span class="empty-icon">📭</span>
                    <p>No exams currently scheduled for your section.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="exam-cards">
                <?php foreach ($available as $row): 
                    $isToday = $row['exam_date'] === date('Y-m-d');
                    $accentStyle = $isToday ? 'border-top: 4px solid var(--warning);' : 'border-top: 4px solid var(--accent);';
                ?>
                    <div class="exam-card" style="<?= $accentStyle ?>">
                        <div class="exam-card-header">
                            <div>
                                <span class="badge manual" style="margin-bottom: 6px;"><?= e($row['exam_code']) ?></span>
                                <h4 class="exam-card-title"><?= e($row['title']) ?></h4>
                            </div>
                            <?php if ($isToday): ?>
                                <span class="badge ongoing">Today</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="exam-card-meta">
                            <span>📅 <strong>Date:</strong> <?= e($row['exam_date']) ?></span>
                            <span>⏰ <strong>Time:</strong> <?= e(substr((string) $row['start_time'], 0, 5)) ?> – <?= e(substr((string) $row['end_time'], 0, 5)) ?></span>
                            <span>📍 <strong>Location:</strong> <?= e($row['location']) ?></span>
                            <span>⏱️ <strong>Duration:</strong> <?= (int) $row['duration_minutes'] ?> mins</span>
                            <span>❓ <strong>Questions:</strong> <?= (int) $row['total_questions'] ?> MCQs</span>
                            <span>📊 <strong>Marks:</strong> <?= number_format((float) $row['total_marks'], 2) ?></span>
                        </div>
                        
                        <div class="exam-card-actions">
                            <a class="btn btn-primary" href="take-exam.php?schedule_id=<?= (int) $row['schedule_id'] ?>" style="width: 100%; justify-content: center; background: <?= $isToday ? 'linear-gradient(135deg, var(--warning), #d97706)' : 'linear-gradient(135deg, var(--accent), var(--accent-2))' ?>;">
                                Launch Exam Room →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- My Recent Attempts Table -->
    <div class="table-card page-section">
        <div class="card-header">
            <h3>My Recent Attempt Logs</h3>
            <p>Verification record of your completed and in-progress attempts</p>
        </div>
        <div class="table-wrapper" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Attempt ID</th>
                        <th>Exam Definition</th>
                        <th>Status</th>
                        <th>Obtained Percentage</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($myAttempts)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>You have not made any exam attempts yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myAttempts as $attempt): ?>
                        <tr>
                            <td class="small">#<?= (int) $attempt['student_exam_id'] ?></td>
                            <td>
                                <span class="badge manual"><?= e($attempt['exam_code']) ?></span>
                                <div class="small fw-700 text-strong" style="margin-top: 3px;"><?= e($attempt['title']) ?></div>
                            </td>
                            <td><span class="badge <?= e($attempt['status']) ?>"><?= e($attempt['status']) ?></span></td>
                            <td class="fw-700"><?= e(number_format((float) ($attempt['percentage'] ?? 0), 1)) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
