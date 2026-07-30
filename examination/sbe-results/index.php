<?php
$page_title = 'SBE Exam Results';

require_once '../../config/db_connect.php';
require_once '../../modules/sbe/config/database.php';
require_once '../../modules/sbe/includes/helpers.php';

$conn = getConnection();
$db = db();

$results = $db->query("
    SELECT 
        er.exam_result_id,
        er.obtained_marks,
        er.total_marks,
        er.percentage,
        er.pass_fail_status,
        er.rank_position,
        er.remarks,
        er.status,
        er.published_at,
        er.created_at,
        e.exam_code,
        e.title AS exam_title,
        s.full_name AS student_name,
        s.student_id,
        s.roll_no
    FROM sbe_exam_results er
    INNER JOIN sbe_exams e ON e.exam_id = er.exam_id
    INNER JOIN students s ON s.student_id = er.student_id
    ORDER BY er.exam_result_id DESC
")->fetchAll();

$stats = [
    'total' => count($results),
    'published' => 0,
    'pass' => 0,
    'fail' => 0,
];
foreach ($results as $r) {
    if ($r['status'] === 'Published') $stats['published']++;
    if ($r['pass_fail_status'] === 'Pass') $stats['pass']++;
    else $stats['fail']++;
}
$passRate = $stats['total'] > 0 ? round(($stats['pass'] / $stats['total']) * 100) : 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>SBE Exam Results</h4>
            <p style="color:var(--text-secondary);font-size:13px;margin:2px 0 0;">Results from Student-Based Examinations (SBE module)</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>modules/sbe/exam-results.php" class="btn btn-outline">
                <i class="bi bi-box-arrow-up-right"></i> Manage in SBE Module
            </a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon stat-card-primary">&#128202;</div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Results</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-success">&#9989;</div>
            <div class="stat-number"><?= $stats['published'] ?></div>
            <div class="stat-label">Published</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-success">&#127942;</div>
            <div class="stat-number"><?= $stats['pass'] ?></div>
            <div class="stat-label">Passed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-warning">&#128200;</div>
            <div class="stat-number"><?= $passRate ?>%</div>
            <div class="stat-label">Pass Rate</div>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th>%</th>
                            <th>Grade</th>
                            <th>Rank</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#128202;</div>
                                        <p class="empty-state-text">No SBE results found</p>
                                        <p style="color:var(--text-secondary);font-size:13px;">Results will appear here once teachers publish them in the SBE module.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($results as $r): ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                                        <small style="color:var(--text-secondary);">ID: <?= $r['student_id'] ?><?= $r['roll_no'] ? ' &middot; Roll: ' . htmlspecialchars($r['roll_no']) : '' ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-exam-quiz"><?= htmlspecialchars($r['exam_code']) ?></span><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars(mb_strimwidth($r['exam_title'], 0, 30, '...')) ?></small>
                                    </td>
                                    <td><strong><?= number_format((float) $r['obtained_marks'], 2) ?></strong> / <?= number_format((float) $r['total_marks'], 2) ?></td>
                                    <td><strong><?= number_format((float) $r['percentage'], 1) ?>%</strong></td>
                                    <td>
                                        <?php
                                        $pct = (float) $r['percentage'];
                                        if ($pct >= 90) { $gc = 'A+'; $gs = 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'; }
                                        elseif ($pct >= 80) { $gc = 'A'; $gs = 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'; }
                                        elseif ($pct >= 70) { $gc = 'B'; $gs = 'background:var(--info-bg);color:#1e40af;border:1px solid var(--info-border);'; }
                                        elseif ($pct >= 60) { $gc = 'C'; $gs = 'background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border);'; }
                                        elseif ($pct >= 50) { $gc = 'D'; $gs = 'background:#F0FDFA;color:#115E59;border:1px solid #99F6E4;'; }
                                        else { $gc = 'F'; $gs = 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);'; }
                                        ?>
                                        <span class="status-badge" style="<?= $gs ?>"><?= $gc ?></span>
                                    </td>
                                    <td><?= $r['rank_position'] ? '#' . $r['rank_position'] : '-' ?></td>
                                    <td>
                                        <?php
                                        $isPublished = $r['status'] === 'Published';
                                        $statusStyle = $isPublished
                                            ? 'background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);'
                                            : 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);';
                                        ?>
                                        <span class="status-badge" style="<?= $statusStyle ?>"><?= htmlspecialchars($r['status']) ?></span>
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

<?php include '../includes/footer.php'; ?>
