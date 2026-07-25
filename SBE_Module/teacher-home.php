<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Teacher Dashboard';
$activePage = 'dashboard';
$user = current_user();
$db = db();

$questionsCount = table_count('sbe_question_bank');
$examsCount     = table_count('sbe_exams');

$statusCounts = $db->query("SELECT status, COUNT(*) AS total FROM sbe_exams GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$draftExams = (int) ($statusCounts['Draft'] ?? 0);
$publishedExamsCount = (int) ($statusCounts['Published'] ?? 0);
$totalExamsCount = array_sum($statusCounts);

$totalAttempts = $db->query("SELECT COUNT(*) FROM sbe_student_exams")->fetchColumn();
$submittedAttempts = $db->query("SELECT COUNT(*) FROM sbe_student_exams WHERE status IN ('Submitted','Auto Submitted')")->fetchColumn();
$avgScore = $db->query("SELECT AVG(percentage) FROM sbe_student_exams WHERE status IN ('Submitted','Auto Submitted')")->fetchColumn();
$avgScoreVal = $avgScore !== null ? round((float) $avgScore, 1) : 0.0;
$passRate = $submittedAttempts > 0 ? round(($db->query("SELECT COUNT(*) FROM sbe_student_exams WHERE pass_fail_status = 'Pass' AND status IN ('Submitted','Auto Submitted')")->fetchColumn() / $submittedAttempts) * 100) : 0;

$recentResults = $db->query('SELECT er.obtained_marks, er.total_marks, er.percentage, er.pass_fail_status, er.published_at, se.student_id, e.exam_code, e.title FROM sbe_exam_results er INNER JOIN sbe_student_exams se ON se.student_exam_id = er.student_exam_id INNER JOIN sbe_exams e ON e.exam_id = er.exam_id ORDER BY er.published_at DESC LIMIT 6')->fetchAll();

$topStudents = $db->query('SELECT se.student_id, ROUND(AVG(se.percentage),1) AS avg_pct, COUNT(*) AS attempts FROM sbe_student_exams se WHERE se.status IN (\'Submitted\',\'Auto Submitted\') GROUP BY se.student_id ORDER BY avg_pct DESC LIMIT 5')->fetchAll();

$questionTopics = $db->query('SELECT topic, COUNT(*) AS total FROM sbe_question_bank GROUP BY topic ORDER BY total DESC LIMIT 5')->fetchAll();

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">

    <div class="greeting-card">
        <div class="greeting-eyebrow">Teacher Portal</div>
        <h2><?= e($greeting) ?>, <?= e($user['display_name']) ?>!</h2>
        <p>Your SBE workspace. Create exams, manage questions, and track student performance.</p>
        <div class="greeting-actions">
            <a class="btn btn-solid" href="question-bank.php">+ Add Questions</a>
            <a class="btn" href="exams.php">Create Exam</a>
        </div>
    </div>

    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon purple">&#128218;</div>
            <div class="stat-label">Question Bank</div>
            <div class="stat-value"><?= number_format($questionsCount) ?></div>
            <div class="stat-trend neutral">MCQs available</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon green">&#128221;</div>
            <div class="stat-label">Exams</div>
            <div class="stat-value"><?= number_format($examsCount) ?></div>
            <div class="stat-trend up"><?= $publishedExamsCount ?> published</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon amber">&#128101;</div>
            <div class="stat-label">Attempts</div>
            <div class="stat-value"><?= number_format((int) $totalAttempts) ?></div>
            <div class="stat-trend up"><?= number_format((int) $submittedAttempts) ?> submitted</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon rose">&#127942;</div>
            <div class="stat-label">Pass Rate</div>
            <div class="stat-value"><?= $passRate ?>%</div>
            <div class="stat-trend <?= $passRate >= 50 ? 'up' : 'down' ?>">Avg: <?= $avgScoreVal ?>%</div>
        </div>
    </div>

    <div class="grid-2 page-section animate-in animate-delay-2">

        <div class="card">
            <div class="card-header">
                <h3>Exam Overview</h3>
                <p>Status distribution and exam types</p>
            </div>
            <div style="margin-top:16px;">
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <div style="flex:1; min-width:80px; text-align:center; padding:12px 8px; border-radius:10px; background:var(--bg-panel); border:1px solid var(--border);">
                            <div style="font-size:1.5rem; font-weight:700; color:var(--text-strong);"><?= (int) $count ?></div>
                            <div class="small" style="margin-top:2px;"><span class="badge <?= e(strtolower($status)) ?>"><?= e($status) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalExamsCount > 0): ?>
                <div style="height:8px; border-radius:99px; background:var(--border); overflow:hidden; display:flex; margin-bottom:16px;">
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <?php $w = round(((int) $count / $totalExamsCount) * 100); ?>
                        <div style="width:<?= $w ?>%; background:<?= $status === 'Published' ? 'var(--success)' : ($status === 'Draft' ? 'var(--warning)' : 'var(--text-muted)') ?>;" title="<?= e($status) ?>: <?= (int) $count ?>"></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($questionTopics)): ?>
                <h4 style="margin:0 0 10px; font-size:0.85rem; color:var(--text-muted);">Top Question Topics</h4>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <?php foreach ($questionTopics as $topic): ?>
                        <span class="badge manual"><?= e($topic['topic']) ?> <strong>&times;<?= (int) $topic['total'] ?></strong></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Top Performing Students</h3>
                <p>Highest average scores across all exams</p>
            </div>
            <div style="margin-top:16px;">
                <?php if (empty($topStudents)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">&#128233;</span>
                        <p>No student attempts yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($topStudents as $i => $student): ?>
                        <?php $pct = (float) $student['avg_pct']; ?>
                        <div style="display:flex; align-items:center; gap:12px; padding:10px 0; <?= $i < count($topStudents) - 1 ? 'border-bottom:1px solid var(--border);' : '' ?>">
                            <div style="width:32px; height:32px; border-radius:50%; background:<?= $i === 0 ? 'linear-gradient(135deg,#f59e0b,#d97706)' : ($i === 1 ? 'linear-gradient(135deg,#94a3b8,#64748b)' : ($i === 2 ? 'linear-gradient(135deg,#d97706,#b45309)' : 'var(--bg-panel)')) ?>; color:<?= $i < 3 ? '#fff' : 'var(--text-muted)' ?>; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                <?= $i + 1 ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:0.88rem; color:var(--text-strong);">Student #<?= (int) $student['student_id'] ?></div>
                                <div class="small" style="color:var(--text-muted);"><?= (int) $student['attempts'] ?> attempt(s)</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700; color:<?= $pct >= 50 ? 'var(--success)' : 'var(--danger)' ?>;"><?= number_format($pct, 1) ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="table-card page-section animate-in animate-delay-3">
        <div class="card-header">
            <h3>Recent Results</h3>
            <p>Latest graded submissions from students</p>
        </div>
        <div class="table-wrapper" style="margin-top:10px;">
            <table>
                <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentResults)): ?>
                    <tr><td colspan="4"><div class="empty-state"><p>No results yet.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($recentResults as $r): ?>
                        <tr>
                            <td class="small fw-700">#<?= (int) $r['student_id'] ?></td>
                            <td><span class="badge manual"><?= e($r['exam_code']) ?></span> <span class="small"><?= e(mb_strimwidth($r['title'],0,20,'...')) ?></span></td>
                            <td class="fw-700"><?= number_format((float) $r['percentage'], 1) ?>%</td>
                            <td><span class="badge <?= e(strtolower($r['pass_fail_status'])) ?>"><?= e($r['pass_fail_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid-3 page-section animate-in animate-delay-3">
        <a class="action-card" href="question-bank.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#f5f3ff; color:#7c3aed;">&#128218;</div>
            <div>
                <strong style="color:var(--text-strong);">Question Bank</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">Add and manage MCQs</small>
            </div>
        </a>
        <a class="action-card" href="exam-questions.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#ecfdf5; color:#059669;">&#128450;</div>
            <div>
                <strong style="color:var(--text-strong);">Map Questions</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">Bulk-assign questions to exams</small>
            </div>
        </a>
        <a class="action-card" href="exam-results.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#fff1f2; color:#f43f5e;">&#127942;</div>
            <div>
                <strong style="color:var(--text-strong);">Grade Results</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">View all graded attempts</small>
            </div>
        </a>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
