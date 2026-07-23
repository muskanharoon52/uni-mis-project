<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$config = require __DIR__ . '/config/app.php';

if (!current_user()) {
    redirect('login.php');
}

$user = current_user();
$db = db();

$totalStudents = $db->query('SELECT COUNT(*) FROM students WHERE status = \'Active\'')->fetchColumn();
$totalResults = $db->query('SELECT COUNT(*) FROM sbe_exam_results')->fetchColumn();
$passCount = $db->query('SELECT COUNT(*) FROM sbe_exam_results WHERE pass_fail_status = \'Pass\'')->fetchColumn();
$failCount = $db->query('SELECT COUNT(*) FROM sbe_exam_results WHERE pass_fail_status = \'Fail\'')->fetchColumn();
$totalPromotions = $db->query('SELECT COUNT(*) FROM student_promotions')->fetchColumn();
$pendingPromotions = (int) $totalStudents - (int) $totalPromotions;
$totalSchedules = $db->query('SELECT COUNT(*) FROM sbe_exam_schedule')->fetchColumn();
$ongoingSchedules = $db->query("SELECT COUNT(*) FROM sbe_exam_schedule WHERE status = 'Ongoing'")->fetchColumn();

$recentResults = $db->query('SELECT er.obtained_marks, er.total_marks, er.percentage, er.pass_fail_status, er.published_at, se.student_id, e.exam_code, e.title, s.full_name FROM sbe_exam_results er INNER JOIN sbe_student_exams se ON se.student_exam_id = er.student_exam_id INNER JOIN sbe_exams e ON e.exam_id = er.exam_id INNER JOIN students s ON s.student_id = se.student_id ORDER BY er.published_at DESC LIMIT 8')->fetchAll();

$passRate = $totalResults > 0 ? round(($passCount / $totalResults) * 100) : 0;

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle = 'Examiner Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">

    <div class="greeting-card">
        <div class="greeting-eyebrow">Examination Portal</div>
        <h2><?= e($greeting) ?>, <?= e($user['display_name']) ?>!</h2>
        <p>Review student results from SBE exams, schedule exams, and manage semester promotions from a single dashboard.</p>
        <div class="greeting-actions">
            <a class="btn btn-solid" href="view-results.php">View Results</a>
            <a class="btn" href="exam-schedule.php">Schedule Exam</a>
            <a class="btn" href="promote-students.php">Promote Students</a>
        </div>
    </div>

    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon purple">&#128101;</div>
            <div class="stat-label">Active Students</div>
            <div class="stat-value"><?= number_format((int) $totalStudents) ?></div>
            <div class="stat-trend neutral">Enrolled</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon green">&#127942;</div>
            <div class="stat-label">Exam Results</div>
            <div class="stat-value"><?= number_format((int) $totalResults) ?></div>
            <div class="stat-trend up"><?= $passRate ?>% pass rate</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon blue">&#128197;</div>
            <div class="stat-label">Schedules</div>
            <div class="stat-value"><?= number_format((int) $totalSchedules) ?></div>
            <div class="stat-trend up"><?= $ongoingSchedules ?> ongoing</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon amber">&#11014;</div>
            <div class="stat-label">Promotions</div>
            <div class="stat-value"><?= number_format((int) $totalPromotions) ?></div>
            <div class="stat-trend neutral"><?= $pendingPromotions > 0 ? $pendingPromotions . ' pending' : 'All promoted' ?></div>
        </div>
    </div>

    <div class="grid-2 page-section animate-in animate-delay-2">

        <div class="table-card">
            <div class="card-header">
                <h3>Recent Exam Results</h3>
                <p>Latest graded submissions from SBE module</p>
            </div>
            <div class="table-wrapper" style="margin-top:10px;">
                <table>
                    <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentResults)): ?>
                        <tr><td colspan="4"><div class="empty-state"><p>No results recorded yet.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentResults as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-700" style="font-size:.88rem;"><?= e($r['full_name']) ?></div>
                                    <div class="small">#<?= (int) $r['student_id'] ?></div>
                                </td>
                                <td>
                                    <span class="badge manual"><?= e($r['exam_code']) ?></span>
                                    <div class="small" style="margin-top:2px;"><?= e(mb_strimwidth($r['title'], 0, 24, '...')) ?></div>
                                </td>
                                <td class="fw-700"><?= number_format((float) $r['percentage'], 1) ?>%</td>
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
                <p>Common examination tasks</p>
            </div>
            <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
                <a class="action-card" href="view-results.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
                    <div class="action-icon" style="background:#ecfdf5; color:#059669;">&#127942;</div>
                    <div>
                        <strong style="color:var(--text-strong);">View All Results</strong>
                        <small style="display:block; margin-top:2px; color:var(--text-muted);">Browse SBE exam results by student or exam</small>
                    </div>
                </a>
                <a class="action-card" href="exam-schedule.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
                    <div class="action-icon" style="background:#eff6ff; color:#3b82f6;">&#128197;</div>
                    <div>
                        <strong style="color:var(--text-strong);">Exam Schedule</strong>
                        <small style="display:block; margin-top:2px; color:var(--text-muted);">Schedule and manage exam delivery windows</small>
                    </div>
                </a>
                <a class="action-card" href="promote-students.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
                    <div class="action-icon" style="background:#fffbeb; color:#f59e0b;">&#11014;</div>
                    <div>
                        <strong style="color:var(--text-strong);">Promote Students</strong>
                        <small style="display:block; margin-top:2px; color:var(--text-muted);">Move students to the next semester</small>
                    </div>
                </a>
                <a class="action-card" href="profile.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
                    <div class="action-icon" style="background:#eef2ff; color:#6366f1;">&#9881;</div>
                    <div>
                        <strong style="color:var(--text-strong);">My Profile</strong>
                        <small style="display:block; margin-top:2px; color:var(--text-muted);">Manage your account settings</small>
                    </div>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
