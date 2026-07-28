<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Student']);

$db = db();
$pageTitle = 'Student Dashboard';
$activePage = 'dashboard';
$student = current_user();
$studentId = (int) $student['login_id'];
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$todayDate = date('Y-m-d');

$totalExamsQuery = $db->prepare("SELECT COUNT(DISTINCT es.exam_id) FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE e.status = 'Published' AND es.status IN ('Scheduled','Ongoing')");
$totalExamsQuery->execute();
$totalExamsAvailable = (int) $totalExamsQuery->fetchColumn();

$completedQuery = $db->prepare("SELECT COUNT(*) FROM sbe_student_exams WHERE student_id = :student_id AND status IN ('Submitted','Auto Submitted')");
$completedQuery->execute([':student_id' => $studentId]);
$completedCount = (int) $completedQuery->fetchColumn();

$avgScoreQuery = $db->prepare("SELECT AVG(percentage) FROM sbe_student_exams WHERE student_id = :student_id AND status IN ('Submitted','Auto Submitted')");
$avgScoreQuery->execute([':student_id' => $studentId]);
$avgScore = $avgScoreQuery->fetchColumn();
$avgScoreValue = $avgScore !== null ? (float) $avgScore : 0.0;

$passCountQuery = $db->prepare("SELECT COUNT(*) FROM sbe_student_exams WHERE student_id = :student_id AND pass_fail_status = 'Pass' AND status IN ('Submitted','Auto Submitted')");
$passCountQuery->execute([':student_id' => $studentId]);
$passCount = (int) $passCountQuery->fetchColumn();
$passRate = $completedCount > 0 ? round(($passCount / $completedCount) * 100) : 0;

$bestScoreQuery = $db->prepare("SELECT MAX(percentage) FROM sbe_student_exams WHERE student_id = :student_id AND status IN ('Submitted','Auto Submitted')");
$bestScoreQuery->execute([':student_id' => $studentId]);
$bestScore = $bestScoreQuery->fetchColumn();
$bestScoreVal = $bestScore !== null ? round((float) $bestScore, 1) : 0.0;

$todayExamStmt = $db->prepare('SELECT es.schedule_id, es.start_time, es.end_time, es.location, e.exam_code, e.title, e.duration_minutes, e.total_questions FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE es.exam_date = :today AND es.status = \'Ongoing\' AND e.status = \'Published\' ORDER BY es.start_time ASC LIMIT 1');
$todayExamStmt->execute([':today' => $todayDate]);
$todayExam = $todayExamStmt->fetch();

$upcomingStmt = $db->prepare('SELECT es.schedule_id, es.exam_date, es.start_time, es.end_time, es.location, es.status AS sched_status, e.exam_code, e.title, e.duration_minutes FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE e.status = \'Published\' AND es.status IN (\'Scheduled\',\'Ongoing\') AND es.exam_date >= :today ORDER BY es.exam_date ASC, es.start_time ASC LIMIT 5');
$upcomingStmt->execute([':today' => $todayDate]);
$upcoming = $upcomingStmt->fetchAll();

$recentResults = $db->prepare('SELECT er.obtained_marks, er.total_marks, er.percentage, er.pass_fail_status, e.exam_code, e.title, er.published_at FROM sbe_exam_results er INNER JOIN sbe_exams e ON e.exam_id = er.exam_id WHERE er.student_id = :student_id ORDER BY er.published_at DESC LIMIT 5');
$recentResults->execute([':student_id' => $studentId]);
$recentResults = $recentResults->fetchAll();

$performanceByExam = $db->prepare('SELECT e.exam_code, e.title, se.percentage, se.pass_fail_status FROM sbe_student_exams se INNER JOIN sbe_exams e ON e.exam_id = se.exam_id WHERE se.student_id = :student_id AND se.status IN (\'Submitted\',\'Auto Submitted\') ORDER BY se.student_exam_id DESC LIMIT 8');
$performanceByExam->execute([':student_id' => $studentId]);
$performanceByExam = $performanceByExam->fetchAll();

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="greeting-card">
        <div class="greeting-eyebrow">Student Portal</div>
        <h2><?= e($greeting) ?>, <?= e($student['display_name']) ?>!</h2>
        <p>View your upcoming exams, track performance, and launch active test sessions.</p>
        <div class="greeting-actions">
            <a class="btn btn-solid" href="student-start-exam.php">Enter Exam Room</a>
        </div>
    </div>

    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon blue">&#128197;</div>
            <div class="stat-label">Available Exams</div>
            <div class="stat-value"><?= $totalExamsAvailable ?></div>
            <div class="stat-trend neutral">Scheduled sessions</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon purple">&#9998;</div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= $completedCount ?></div>
            <div class="stat-trend up"><?= $passRate ?>% pass rate</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon green">&#127942;</div>
            <div class="stat-label">Average Score</div>
            <div class="stat-value"><?= number_format($avgScoreValue, 1) ?>%</div>
            <div class="stat-trend <?= $avgScoreValue >= 50 ? 'up' : 'down' ?>">Overall performance</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon amber">&#127775;</div>
            <div class="stat-label">Best Score</div>
            <div class="stat-value"><?= number_format($bestScoreVal, 1) ?>%</div>
            <div class="stat-trend up">Personal best</div>
        </div>
    </div>

    <?php if ($todayExam): ?>
        <div class="card page-section animate-in animate-delay-2" style="border-left:5px solid var(--warning); background:linear-gradient(135deg,#fffbeb,#fef3c7);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                <div>
                    <div class="countdown" style="margin-bottom:8px;">
                        <span class="countdown-pulse"></span>
                        Exam is live now!
                    </div>
                    <h3 style="margin:0 0 6px; font-size:1.2rem; color:var(--text-strong);"><?= e($todayExam['exam_code']) ?> &mdash; <?= e($todayExam['title']) ?></h3>
                    <p class="small" style="margin:0;">
                        <?= e(substr((string) $todayExam['start_time'], 0, 5)) ?> &ndash; <?= e(substr((string) $todayExam['end_time'], 0, 5)) ?>
                        &nbsp;|&nbsp; <?= e($todayExam['location']) ?>
                        &nbsp;|&nbsp; <?= (int) $todayExam['duration_minutes'] ?> mins
                        &nbsp;|&nbsp; <?= (int) $todayExam['total_questions'] ?> questions
                    </p>
                </div>
                <a class="btn btn-primary" href="take-exam.php?schedule_id=<?= (int) $todayExam['schedule_id'] ?>" style="background:linear-gradient(135deg,var(--warning),#d97706); box-shadow:0 4px 14px rgba(217,119,6,0.25);">Start Exam &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid-2 page-section animate-in animate-delay-2">

        <div class="table-card">
            <div class="card-header">
                <h3>Upcoming Exams</h3>
                <p>Scheduled sessions from today onwards</p>
            </div>
            <div class="table-wrapper" style="margin-top:10px;">
                <table>
                    <thead><tr><th>Exam</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($upcoming)): ?>
                        <tr><td colspan="4"><div class="empty-state"><p>No upcoming exams.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($upcoming as $u): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($u['exam_code']) ?></span>
                                    <div class="small fw-700" style="margin-top:2px;"><?= e(mb_strimwidth($u['title'],0,28,'...')) ?></div>
                                </td>
                                <td class="small"><?= e($u['exam_date']) ?></td>
                                <td class="small"><?= e(substr((string) $u['start_time'],0,5)) ?> &ndash; <?= e(substr((string) $u['end_time'],0,5)) ?></td>
                                <td><span class="badge <?= e(strtolower($u['sched_status'])) ?>"><?= e($u['sched_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Performance Trend</h3>
                <p>Your scores across recent exams</p>
            </div>
            <div style="margin-top:16px;">
                <?php if (empty($performanceByExam)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">&#128233;</span>
                        <p>Complete an exam to see your trend.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid; gap:12px;">
                        <?php foreach ($performanceByExam as $pe):
                            $pct = (float) $pe['percentage'];
                        ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span class="small fw-700" style="color:var(--text-strong);"><?= e($pe['exam_code']) ?> &mdash; <?= e(mb_strimwidth($pe['title'],0,24,'...')) ?></span>
                                    <span class="small fw-700" style="color:<?= $pct >= 50 ? 'var(--success)' : 'var(--danger)' ?>;"><?= number_format($pct,1) ?>%</span>
                                </div>
                                <div style="height:8px; border-radius:99px; background:var(--border); overflow:hidden;">
                                    <div style="height:100%; width:<?= min($pct,100) ?>%; border-radius:99px; background:<?= $pct >= 50 ? 'linear-gradient(90deg,#10b981,#059669)' : 'linear-gradient(90deg,#f87171,#ef4444)' ?>; transition:width 0.5s;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="grid-2-equal page-section animate-in animate-delay-3">

        <div class="table-card">
            <div class="card-header">
                <h3>Recent Results</h3>
                <p>Latest published grades from your teacher</p>
            </div>
            <div class="table-wrapper" style="margin-top:10px;">
                <table>
                    <thead><tr><th>Exam</th><th>Score</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentResults)): ?>
                        <tr><td colspan="3"><div class="empty-state"><p>No results published yet.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentResults as $r): ?>
                            <tr>
                                <td>
                                    <span class="badge manual"><?= e($r['exam_code']) ?></span>
                                    <div class="small fw-700" style="margin-top:2px;"><?= e(mb_strimwidth($r['title'],0,28,'...')) ?></div>
                                </td>
                                <td class="fw-700"><?= number_format((float) $r['percentage'],1) ?>%</td>
                                <td><span class="badge <?= e(strtolower($r['pass_fail_status'])) ?>"><?= e($r['pass_fail_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
                <p>Shortcuts to key areas</p>
            </div>
            <div style="display:grid; gap:10px; margin-top:16px;">
                <a href="student-start-exam.php" style="display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1px solid var(--border); background:var(--bg-card); text-decoration:none; transition: all 0.15s; color:var(--text-strong);" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <span style="width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,var(--accent),var(--accent-2)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">&#9654;</span>
                    <div>
                        <strong style="display:block;">Enter Exam Room</strong>
                        <small style="color:var(--text-muted);">Start or continue an active exam</small>
                    </div>
                </a>
                <a href="profile.php" style="display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1px solid var(--border); background:var(--bg-card); text-decoration:none; transition: all 0.15s; color:var(--text-strong);" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <span style="width:40px; height:40px; border-radius:10px; background:#f5f3ff; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">&#9881;</span>
                    <div>
                        <strong style="display:block;">My Profile</strong>
                        <small style="color:var(--text-muted);">View your account details</small>
                    </div>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
