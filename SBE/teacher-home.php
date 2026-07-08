<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Teacher Dashboard';
$activePage = 'dashboard';
$user = current_user();
$db = db();

// Get count metrics
$questionsCount = table_count('question_bank');
$examsCount     = table_count('exams');
$scheduleCount  = table_count('exam_schedule');
$resultsCount   = table_count('exam_results');

// Exam status distribution for donut chart
$statusCounts = $db->query("SELECT status, COUNT(*) AS total FROM exams GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$draftExams = (int) ($statusCounts['draft'] ?? 0);
$publishedExamsCount = (int) ($statusCounts['published'] ?? 0);
$closedExamsCount = (int) ($statusCounts['closed'] ?? 0);
$totalExamsCount = $draftExams + $publishedExamsCount + $closedExamsCount;

// Top exam types for bar chart
$examTypes = $db->query("SELECT exam_type, COUNT(*) AS total FROM exams GROUP BY exam_type ORDER BY total DESC LIMIT 4")->fetchAll();

// Unified recent activities timeline
$activitiesQuery = "
    (SELECT 'question' AS act_type, CONCAT('New question on \"', topic, '\" pool created') AS act_desc, created_at FROM question_bank)
    UNION ALL
    (SELECT 'exam' AS act_type, CONCAT('Exam definition \"', title, '\" (', exam_code, ') added') AS act_desc, created_at FROM exams)
    UNION ALL
    (SELECT 'schedule' AS act_type, CONCAT('Exam Scheduled in ', location, ' for Class ', class_id) AS act_desc, created_at FROM exam_schedule)
    UNION ALL
    (SELECT 'result' AS act_type, CONCAT('Grade snapshot generated for Student #', student_id) AS act_desc, created_at FROM exam_results)
    ORDER BY created_at DESC LIMIT 5
";
try {
    $activities = $db->query($activitiesQuery)->fetchAll();
} catch (Throwable $e) {
    $activities = [];
}

// Recent exams list
$myExams = $db->query("SELECT exam_id, exam_code, title, status, selection_mode FROM exams ORDER BY exam_id DESC LIMIT 4")->fetchAll();

// Time-based greeting helper
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

    <!-- Premium Greeting Header -->
    <div class="greeting-card">
        <div class="greeting-eyebrow">Overview Panel</div>
        <h2><?= e($greeting) ?>, Professor <?= e($user['display_name']) ?>!</h2>
        <p>Your examination portal workspace is loaded. Monitor active schedules, build your question pool, and snap final student gradeheets below.</p>
        <div class="greeting-actions">
            <a class="btn btn-solid" href="question-bank.php">+ Add New Question</a>
            <a class="btn" href="exams.php">Design Exams</a>
        </div>
    </div>

    <!-- Analytics Stat Cards -->
    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-v2">
            <div class="stat-icon purple">📚</div>
            <div class="stat-label">Question Pool</div>
            <div class="stat-value"><?= number_format($questionsCount) ?></div>
            <div class="stat-trend neutral">Active MCQs</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon green">📝</div>
            <div class="stat-label">Total Exams</div>
            <div class="stat-value"><?= number_format($examsCount) ?></div>
            <div class="stat-trend up">
                <?php if ($totalExamsCount > 0): ?>
                    <?= round(($publishedExamsCount / $totalExamsCount) * 100) ?>% Published
                <?php else: ?>
                    0% Published
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon amber">📅</div>
            <div class="stat-label">Active Schedules</div>
            <div class="stat-value"><?= number_format($scheduleCount) ?></div>
            <div class="stat-trend neutral">Class Groups</div>
        </div>
        <div class="stat-card-v2">
            <div class="stat-icon rose">🏆</div>
            <div class="stat-label">Grade Sheets</div>
            <div class="stat-value"><?= number_format($resultsCount) ?></div>
            <div class="stat-trend up">Published snapshots</div>
        </div>
    </div>

    <!-- Visual Analytics: Charts and Timeline Row -->
    <div class="grid-2 page-section animate-in animate-delay-2">
        
        <!-- Interactive Analytics Card (CSS Donut + Bar Chart) -->
        <div class="card">
            <div class="card-header">
                <h3>Exam Registry Analytics</h3>
                <p>Distribution by Status and Categories</p>
            </div>
            
            <div class="grid-2-equal" style="margin-top: 14px; align-items: center;">
                <div>
                    <!-- Donut Chart -->
                    <div class="donut-chart">
                        <svg viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="#f1f5f9" />
                            <?php
                            $publishedPercent = $totalExamsCount > 0 ? $publishedExamsCount / $totalExamsCount : 0;
                            $dashArray = 314.16;
                            $dashOffset = $dashArray * (1 - $publishedPercent);
                            ?>
                            <circle cx="60" cy="60" r="50" class="ring-fill" stroke="var(--accent)" stroke-dasharray="<?= $dashArray ?>" stroke-dashoffset="<?= $dashOffset ?>" />
                        </svg>
                        <div class="donut-center">
                            <span class="donut-value"><?= $totalExamsCount ?></span>
                            <span class="donut-label">Exams</span>
                        </div>
                    </div>
                    <div class="donut-legend">
                        <div class="donut-legend-item">
                            <span class="donut-legend-dot" style="background: var(--accent);"></span>
                            <span>Published (<?= $publishedExamsCount ?>)</span>
                        </div>
                        <div class="donut-legend-item">
                            <span class="donut-legend-dot" style="background: var(--border-strong);"></span>
                            <span>Draft/Closed (<?= $totalExamsCount - $publishedExamsCount ?>)</span>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Bar Chart of Top Categories -->
                    <div class="bar-chart">
                        <?php if (empty($examTypes)): ?>
                            <p class="small text-strong text-center">No categories registered.</p>
                        <?php else: ?>
                            <?php 
                            $maxTotal = max(array_column($examTypes, 'total'));
                            foreach ($examTypes as $type): 
                                $percent = $maxTotal > 0 ? round(($type['total'] / $maxTotal) * 100) : 0;
                            ?>
                                <div class="bar-row">
                                    <div class="bar-label" title="<?= e($type['exam_type']) ?>"><?= e($type['exam_type']) ?></div>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <div class="bar-value"><?= (int) $type['total'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <h3>System Activity Feed</h3>
                <p>Real-time audit log of local SBE database triggers</p>
            </div>
            <div class="activity-timeline" style="margin-top: 14px;">
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">📭</span>
                        <p>No recent activity logged.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $act): 
                        $dotColor = 'purple';
                        if ($act['act_type'] === 'exam') $dotColor = 'green';
                        if ($act['act_type'] === 'schedule') $dotColor = 'amber';
                        if ($act['act_type'] === 'result') $dotColor = 'rose';
                    ?>
                        <div class="timeline-item">
                            <span class="timeline-dot <?= $dotColor ?>"></span>
                            <div class="timeline-content">
                                <strong><?= e($act['act_desc']) ?></strong>
                                <span class="small"><?= e($act['created_at']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Quick Action Cards & Recent Exams Row -->
    <div class="grid-2 page-section animate-in animate-delay-3">

        <!-- Recent Exams Registry -->
        <div class="table-card">
            <div class="card-header">
                <h3>Active Exam Configurations</h3>
                <p>Latest exam configurations saved to the registry</p>
            </div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr><th>Code</th><th>Title</th><th>Mode</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($myExams)): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No exams created yet. <a href="exams.php" style="color: var(--accent);">Create one →</a></p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($myExams as $exam): ?>
                            <tr>
                                <td><span class="badge manual"><?= e($exam['exam_code']) ?></span></td>
                                <td class="text-strong fw-700"><?= e($exam['title']) ?></td>
                                <td><span class="badge manual" style="text-transform: capitalize;"><?= e($exam['selection_mode']) ?></span></td>
                                <td><span class="badge <?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Access Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Management Links</h3>
                <p>Direct routes to key administrative modules</p>
            </div>
            <div class="action-cards" style="margin-top: 14px; grid-template-columns: 1fr; gap: 10px;">
                <a class="action-card" href="exams.php" style="flex-direction: row; align-items: center; gap: 14px;">
                    <div class="action-icon">📝</div>
                    <div>
                        <strong>Create & Manage Exams</strong>
                        <small style="display: block; margin-top: 2px;">Design exam structures, set durations, scoring rules and pass thresholds.</small>
                    </div>
                </a>
                <a class="action-card" href="exam-schedule.php" style="flex-direction: row; align-items: center; gap: 14px;">
                    <div class="action-icon" style="background: #fffbeb; color: #f59e0b;">📅</div>
                    <div>
                        <strong>Schedule Exam Sessions</strong>
                        <small style="display: block; margin-top: 2px;">Assign exam definitions to class sections, specify locations and set test windows.</small>
                    </div>
                </a>
                <a class="action-card" href="exam-results.php" style="flex-direction: row; align-items: center; gap: 14px;">
                    <div class="action-icon" style="background: #fff1f2; color: #f43f5e;">🏆</div>
                    <div>
                        <strong>Grade Registry & Snapshots</strong>
                        <small style="display: block; margin-top: 2px;">Publish graded attempts, manage grading scales and download results sheets.</small>
                    </div>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
