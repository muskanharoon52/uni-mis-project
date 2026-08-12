<?php
// examination/results/index.php - SBE exam results for the Examination module
// Filters by department + session, drills into a student's paper, and lets the
// examiner accept a result and request SSO to publish it to the student LMS.

$page_title = 'Exam Results';

require_once '../../config/db_connect.php';
require_once '../../modules/sbe/config/database.php';
require_once '../../modules/sbe/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();

$departmentId = !empty($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
$sessionId    = !empty($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$submitted    = isset($_GET['department_id']) || isset($_GET['session_id']);

$departments = $db->query('SELECT department_id, department_name FROM departments WHERE status = "Active" ORDER BY department_name')->fetchAll();
$sessions    = $db->query('SELECT session_id, session_name FROM sessions WHERE status = "Active" ORDER BY start_date ASC')->fetchAll();

$conds  = [];
$params = [];
if ($departmentId > 0) { $conds[] = 'p.department_id = :dept'; $params[':dept'] = $departmentId; }
if ($sessionId > 0)    { $conds[] = 's.current_session_id = :sess'; $params[':sess'] = $sessionId; }
$where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

$sql = "SELECT
            er.exam_result_id, er.obtained_marks, er.total_marks, er.percentage,
            er.pass_fail_status, er.rank_position, er.remarks,
            er.status AS result_status, er.published_at, er.created_at,
            e.exam_code, e.title AS exam_title, e.exam_type,
            s.student_id, s.roll_no, s.full_name, s.batch_year,
            sem.semester_name,
            p.program_name, d.department_name,
            app.id AS app_id, app.status AS app_status, app.requested_at, app.transcript_path
        FROM sbe_exam_results er
        INNER JOIN sbe_exams e ON e.exam_id = er.exam_id
        INNER JOIN students s ON s.student_id = er.student_id
        LEFT JOIN programs p ON p.program_id = s.program_id
        LEFT JOIN departments d ON d.department_id = p.department_id
        LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
        LEFT JOIN result_publish_applications app ON app.exam_result_id = er.exam_result_id
        $where
        ORDER BY er.exam_result_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$stats = [
    'total'        => count($results),
    'awaiting'     => 0,
    'pending'      => 0,
    'published'    => 0,
    'pass'         => 0,
    'fail'         => 0,
];
foreach ($results as $r) {
    if ($r['result_status'] === 'Pending') $stats['pending']++;
    if ($r['result_status'] === 'Published') $stats['published']++;
    if ($r['result_status'] === 'Draft') $stats['awaiting']++;
    if ($r['pass_fail_status'] === 'Pass') $stats['pass']++;
    else $stats['fail']++;
}
$stats['pass_rate'] = $stats['total'] > 0 ? round(($stats['pass'] / $stats['total']) * 100) : 0;

if (!function_exists('grade_letter')) {
    function grade_letter(?float $pct): string
    {
        if ($pct === null) return '-';
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        return 'F';
    }
}

function result_badge_style(string $status, ?string $appStatus): array
{
    switch ($status) {
        case 'Published':
            return ['label' => 'Published', 'style' => 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'];
        case 'Pending':
            return ['label' => 'Pending SSO Approval', 'style' => 'background:var(--info-bg);color:#1e40af;border:1px solid var(--info-border);'];
        case 'Draft':
            if ($appStatus === 'rejected') {
                return ['label' => 'Rejected by SSO', 'style' => 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);'];
            }
            return ['label' => 'Awaiting Acceptance', 'style' => 'background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border);'];
        default:
            return ['label' => ucfirst(strtolower((string) $status)), 'style' => 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);'];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Results</h4>
            <p style="color:var(--text-secondary);font-size:13px;margin:2px 0 0;">SBE examination results. Accept a result to send a publish request to SSO.</p>
        </div>
        <div class="page-header-actions">
            <a href="index.php" class="btn btn-outline">
                <i class="bi bi-arrow-clockwise"></i> Reset Filters
            </a>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-content">
            <form method="get" id="result-filter-form" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="min-width:200px;">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['department_id'] ?>" <?= $departmentId === (int) $dept['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width:180px;">
                    <label class="form-label">Session</label>
                    <select name="session_id" id="session_id" class="form-select">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= (int) $sess['session_id'] ?>" <?= $sessionId === (int) $sess['session_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sess['session_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter Results</button>
                <span style="color:var(--text-secondary);font-size:13px;padding-bottom:6px;">
                    Showing <?= number_format($stats['total']) ?> result<?= $stats['total'] === 1 ? '' : 's' ?>
                </span>
            </form>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:16px;margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon stat-card-primary">&#128202;</div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Results</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-warning">&#9203;</div>
            <div class="stat-number"><?= $stats['awaiting'] ?></div>
            <div class="stat-label">Awaiting Acceptance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-info">&#128190;</div>
            <div class="stat-number"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending SSO</div>
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
            <div class="stat-icon stat-card-primary">&#128200;</div>
            <div class="stat-number"><?= $stats['pass_rate'] ?>%</div>
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
                            <th>Dept / Session</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#128202;</div>
                                        <p class="empty-state-text">No results found</p>
                                        <p style="color:var(--text-secondary);font-size:13px;">Results appear here once students submit SBE exams.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($results as $r): ?>
                                <?php $badge = result_badge_style((string) $r['result_status'], $r['app_status']); ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                                        <small style="color:var(--text-secondary);">
                                            ID: <?= $r['student_id'] ?><?= $r['roll_no'] ? ' &middot; Roll: ' . htmlspecialchars((string) $r['roll_no']) : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-exam-quiz"><?= htmlspecialchars($r['exam_code']) ?></span><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars(mb_strimwidth((string) $r['exam_title'], 0, 30, '...')) ?></small>
                                    </td>
                                    <td><strong><?= number_format((float) $r['obtained_marks'], 2) ?></strong> / <?= number_format((float) $r['total_marks'], 2) ?></td>
                                    <td><strong><?= number_format((float) $r['percentage'], 1) ?>%</strong></td>
                                    <td>
                                        <?php
                                        $pct = (float) $r['percentage'];
                                        $gc = grade_letter($pct);
                                        if ($pct >= 80) { $gs = 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'; }
                                        elseif ($pct >= 70) { $gs = 'background:var(--info-bg);color:#1e40af;border:1px solid var(--info-border);'; }
                                        elseif ($pct >= 60) { $gs = 'background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border);'; }
                                        elseif ($pct >= 50) { $gs = 'background:#F0FDFA;color:#115E59;border:1px solid #99F6E4;'; }
                                        else { $gs = 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);'; }
                                        ?>
                                        <span class="status-badge" style="<?= $gs ?>"><?= $gc ?></span>
                                    </td>
                                    <td>
                                        <?php if ($r['department_name']): ?>
                                            <span style="font-size:12px;"><?= htmlspecialchars($r['department_name']) ?></span><br>
                                        <?php endif; ?>
                                        <small style="color:var(--text-secondary);">
                                            <?= $r['semester_name'] ? htmlspecialchars($r['semester_name']) : 'Sem -' ?>
                                            <?php if ($r['batch_year']): ?>&middot; Batch <?= (int) $r['batch_year'] ?><?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="<?= $badge['style'] ?>"><?= $badge['label'] ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                            <a class="btn btn-sm btn-outline-primary" href="paper.php?exam_result_id=<?= (int) $r['exam_result_id'] ?>" title="View Paper">
                                                <i class="bi bi-eye"></i> View Paper
                                            </a>
                                            <?php
                                            $canRequest = $r['result_status'] === 'Draft'
                                                && ($r['app_status'] === null || $r['app_status'] === 'rejected');
                                            ?>
                                            <?php if ($canRequest): ?>
                                                <form method="post" action="accept.php" style="display:inline;" onsubmit="return confirm('Accept this result and send a publish request to SSO?')">
                                                    <input type="hidden" name="exam_result_id" value="<?= (int) $r['exam_result_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" title="<?= $r['app_status'] === 'rejected' ? 'Re-request' : 'Accept & Send to SSO' ?>">
                                                        <i class="bi bi-send"></i> <?= $r['app_status'] === 'rejected' ? 'Re-request' : 'Send to SSO' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($r['result_status'] === 'Published' && $r['transcript_path']): ?>
                                                <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>result_publish_applications/transcript.php?app_id=<?= (int) $r['app_id'] ?>" title="View Transcript" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> Transcript
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
