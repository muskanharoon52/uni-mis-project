<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['student']);

$db = db();
$pageTitle = 'Student Dashboard';
$activePage = 'dashboard';
$student = current_user();
$studentId = (int) $student['login_id'];
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Get all schedules
$availableSchedules = $db->query('SELECT es.schedule_id, es.exam_id, es.class_id, es.exam_date, es.start_time, es.end_time, es.location, e.exam_code, e.title, e.status AS exam_status FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id ORDER BY es.exam_date DESC, es.start_time DESC LIMIT 10')->fetchAll();

// Get attempts
$attemptStmt = $db->prepare('SELECT se.student_exam_id, se.exam_id, se.status, se.obtained_marks, se.percentage, se.pass_fail_status, e.exam_code, e.title FROM student_exams se INNER JOIN exams e ON e.exam_id = se.exam_id WHERE se.student_id = :student_id ORDER BY se.student_exam_id DESC LIMIT 6');
$attemptStmt->execute([':student_id' => $studentId]);
$myAttempts = $attemptStmt->fetchAll();

// Get results
$resultStmt = $db->prepare('SELECT er.exam_result_id, er.exam_id, er.obtained_marks, er.total_marks, er.percentage, er.pass_fail_status, er.status, e.exam_code, e.title FROM exam_results er INNER JOIN exams e ON e.exam_id = er.exam_id WHERE er.student_id = :student_id ORDER BY er.exam_result_id DESC LIMIT 6');
$resultStmt->execute([':student_id' => $studentId]);
$publishedResults = $resultStmt->fetchAll();

// Stats calculation
$avgScoreQuery = $db->prepare('SELECT AVG(percentage) FROM student_exams WHERE student_id = :student_id AND status IN ("submitted", "auto_submitted")');
$avgScoreQuery->execute([':student_id' => $studentId]);
$avgScore = $avgScoreQuery->fetchColumn();
$avgScoreValue = $avgScore !== null ? (float) $avgScore : 0.0;

$completedQuery = $db->prepare('SELECT COUNT(*) FROM student_exams WHERE student_id = :student_id AND status IN ("submitted", "auto_submitted")');
$completedQuery->execute([':student_id' => $studentId]);
$completedCount = (int) $completedQuery->fetchColumn();

// Check if any schedule is scheduled for today
$todayDate = date('Y-m-d');
$todayExamStmt = $db->prepare('SELECT es.schedule_id, es.start_time, es.end_time, es.location, e.exam_code, e.title, e.duration_minutes FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id WHERE es.exam_date = :today ORDER BY es.start_time ASC LIMIT 1');
$todayExamStmt->execute([':today' => $todayDate]);
$todayExam = $todayExamStmt->fetch();

// Greeting message
$hour = (int) date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- Greeting Card -->
    <div class="greeting-card">
        <div class="greeting-eyebrow">Student Center</div>
        <h2><?= e($greeting) ?>, <?= e($student['display_name']) ?>!</h2>
        <p>Welcome to your exam portal. You can view all upcoming classes, test schedules, launch online tests, and access verified grading scales.</p>
        <div class="greeting-actions">
            <a class="btn btn-solid" href="student-start-exam.php">▶ Enter Exam Room</a>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon blue">📅</div>
            <div class="stat-label">Total Sessions</div>
            <div class="stat-value"><?= number_format(count($availableSchedules)) ?></div>
            <div class="stat-trend neutral">All classes</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon purple">✍️</div>
            <div class="stat-label">Exams Completed</div>
            <div class="stat-value"><?= $completedCount ?></div>
            <div class="stat-trend up">Attempt snapshots</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon green">🏆</div>
            <div class="stat-label">Average Score</div>
            <div class="stat-value"><?= number_format($avgScoreValue, 1) ?>%</div>
            <div class="stat-trend <?= $avgScoreValue >= 50.0 ? 'up' : 'down' ?>">Overall grade</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon rose">🎓</div>
            <div class="stat-label">Student ID</div>
            <div class="stat-value" style="font-size: 1.4rem; padding-top: 5px;"><?= e($student['login_id']) ?></div>
            <div class="stat-trend neutral">ERP verified</div>
        </div>
    </div>

    <?php if ($todayExam): ?>
        <!-- Highlighted Today's Exam Card -->
        <div class="card page-section animate-in animate-delay-2" style="border-left: 5px solid var(--warning); background: #fffdf5;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div class="countdown" style="margin-bottom: 8px;">
                        <span class="countdown-pulse"></span>
                        Exam scheduled for today
                    </div>
                    <h3 style="margin: 0 0 6px; font-size: 1.2rem; color: var(--text-strong);"><?= e($todayExam['exam_code']) ?> — <?= e($todayExam['title']) ?></h3>
                    <p class="small" style="margin: 0; color: var(--text);">
                        ⏰ Timing: <strong><?= e(substr((string) $todayExam['start_time'], 0, 5)) ?> – <?= e(substr((string) $todayExam['end_time'], 0, 5)) ?></strong> &nbsp;|&nbsp; 
                        📍 Lab: <strong><?= e($todayExam['location']) ?></strong> &nbsp;|&nbsp; 
                        ⏱️ Duration: <strong><?= (int) $todayExam['duration_minutes'] ?> mins</strong>
                    </p>
                </div>
                <div>
                    <a class="btn btn-primary" href="student-start-exam.php" style="background: linear-gradient(135deg, var(--warning), #d97706); box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25);">Proceed to Exam Room</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Grid layout -->
    <div class="grid-2 page-section animate-in animate-delay-2">

        <!-- Available sessions list -->
        <div class="table-card">
            <div class="card-header">
                <h3>Available Exam Schedule</h3>
                <p>Latest exam sessions announced for your section</p>
            </div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($availableSchedules)): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No active schedules mapped to your class.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($availableSchedules as $row): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($row['exam_code']) ?></span>
                                    <div class="small fw-700 text-strong" style="margin-top: 3px;"><?= e($row['title']) ?></div>
                                </td>
                                <td class="small"><?= e($row['exam_date']) ?></td>
                                <td class="small"><?= e(substr((string) $row['start_time'], 0, 5)) ?> – <?= e(substr((string) $row['end_time'], 0, 5)) ?></td>
                                <td class="small"><span class="badge manual"><?= e($row['location']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grade progression CSS chart -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Grade Breakdown</h3>
                <p>Visual percentages of your recent attempts</p>
            </div>
            <div class="bar-chart" style="margin-top: 18px;">
                <?php if (empty($myAttempts)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">📭</span>
                        <p>No exams attempts registered yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($myAttempts as $attempt): 
                        $percentage = (float) ($attempt['percentage'] ?? 0.0);
                        $fillClass = $percentage >= 50.0 ? 'green' : 'rose';
                    ?>
                        <div class="bar-row">
                            <div class="bar-label" title="<?= e($attempt['title']) ?>"><?= e($attempt['exam_code']) ?></div>
                            <div class="bar-track">
                                <div class="bar-fill <?= $fillClass ?>" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <div class="bar-value"><?= number_format($percentage, 1) ?>%</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Attempts and Published snapshot results -->
    <div class="grid-2-equal page-section animate-in animate-delay-3">

        <!-- My Attempts Table -->
        <div class="table-card">
            <div class="card-header">
                <h3>My Attempts Record</h3>
                <p>History of all exam attempts and states</p>
            </div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr><th>Exam</th><th>Status</th><th>Score</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($myAttempts)): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No attempts recorded.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($myAttempts as $attempt): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($attempt['exam_code']) ?></span>
                                    <div class="small fw-700 text-strong" style="margin-top: 3px;"><?= e($attempt['title']) ?></div>
                                </td>
                                <td><span class="badge <?= e($attempt['status']) ?>"><?= e($attempt['status']) ?></span></td>
                                <td class="fw-700"><?= number_format((float) ($attempt['percentage'] ?? 0.0), 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Published Results Table -->
        <div class="table-card">
            <div class="card-header">
                <h3>Verified Grading Records</h3>
                <p>Official gradeheets published by your instructor</p>
            </div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr><th>Exam</th><th>Percentage</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($publishedResults)): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No published gradeheets available yet.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($publishedResults as $result): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($result['exam_code']) ?></span>
                                    <div class="small fw-700 text-strong" style="margin-top: 3px;"><?= e($result['title']) ?></div>
                                </td>
                                <td class="fw-700"><?= number_format((float) $result['percentage'], 1) ?>%</td>
                                <td><span class="badge <?= e($result['pass_fail_status']) ?>"><?= e($result['pass_fail_status']) ?></span></td>
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
