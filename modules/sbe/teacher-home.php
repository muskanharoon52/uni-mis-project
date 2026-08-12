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
$scheduleCount  = table_count('sbe_exam_schedule');

$statusCounts = $db->query("SELECT status, COUNT(*) AS total FROM sbe_exams GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$draftExams = (int) ($statusCounts['Draft'] ?? 0);
$publishedExamsCount = (int) ($statusCounts['Published'] ?? 0);
$totalExamsCount = array_sum($statusCounts);

$totalAttempts = $db->query("SELECT COUNT(*) FROM sbe_student_exams")->fetchColumn();
$submittedAttempts = $db->query("SELECT COUNT(*) FROM sbe_student_exams WHERE status IN ('Submitted','Auto Submitted')")->fetchColumn();
$avgScore = $db->query("SELECT AVG(percentage) FROM sbe_student_exams WHERE status IN ('Submitted','Auto Submitted')")->fetchColumn();
$avgScoreVal = $avgScore !== null ? round((float) $avgScore, 1) : 0.0;
$passRate = $submittedAttempts > 0 ? round(($db->query("SELECT COUNT(*) FROM sbe_student_exams WHERE pass_fail_status = 'Pass' AND status IN ('Submitted','Auto Submitted')")->fetchColumn() / $submittedAttempts) * 100) : 0;

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
            <a class="btn btn-solid" href="exams.php">+ Create Exam</a>
            <a class="btn" href="exam-questions.php">Add Questions</a>
            <a class="btn" href="schedule.php">Schedule Exam</a>
        </div>
    </div>

    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon purple">&#128218;</div>
            <div class="stat-label">Questions</div>
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

    <div class="page-section animate-in animate-delay-2">
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
    </div>

    <div class="grid-3 page-section animate-in animate-delay-3">
        <a class="action-card" href="exams.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#f5f3ff; color:#7c3aed;">&#128218;</div>
            <div>
                <strong style="color:var(--text-strong);">Create Exam</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">New exam with dept, section, batch &amp; subject</small>
            </div>
        </a>
        <a class="action-card" href="exam-questions.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#ecfdf5; color:#059669;">&#128450;</div>
            <div>
                <strong style="color:var(--text-strong);">Add Questions</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">Upload PDF or add manually</small>
            </div>
        </a>
        <a class="action-card" href="view-results.php" style="flex-direction:row; align-items:center; gap:14px; text-decoration:none;">
            <div class="action-icon" style="background:#fff1f2; color:#f43f5e;">&#127942;</div>
            <div>
                <strong style="color:var(--text-strong);">View Results</strong>
                <small style="display:block; margin-top:2px; color:var(--text-muted);">Student results for each subject</small>
            </div>
        </a>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>