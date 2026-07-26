<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login();
$db = db();

$search = trim((string) ($_GET['search'] ?? ''));
$filterExam = trim((string) ($_GET['exam'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$sql = 'SELECT er.exam_result_id, er.obtained_marks, er.total_marks, er.percentage, er.pass_fail_status, er.published_at,
               se.student_id, se.attempt_no, se.started_at, se.submitted_at,
               e.exam_id, e.exam_code, e.title AS exam_title, e.exam_type, e.total_marks AS exam_total_marks,
               s.full_name, s.roll_no
        FROM sbe_exam_results er
        INNER JOIN sbe_student_exams se ON se.student_exam_id = er.student_exam_id
        INNER JOIN sbe_exams e ON e.exam_id = er.exam_id
        INNER JOIN students s ON s.student_id = se.student_id';

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(s.full_name LIKE :search OR CAST(se.student_id AS CHAR) LIKE :search OR e.exam_code LIKE :search OR s.roll_no LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($filterExam !== '') {
    $conditions[] = 'e.exam_id = :exam_id';
    $params[':exam_id'] = $filterExam;
}

if ($filterStatus !== '') {
    $conditions[] = 'er.pass_fail_status = :status';
    $params[':status'] = $filterStatus;
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY er.published_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$exams = $db->query('SELECT exam_id, exam_code, title FROM sbe_exams ORDER BY exam_code')->fetchAll();

$pageTitle = 'View Results';
$activePage = 'view_results';
require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">

    <div class="page-head">
        <div>
            <h2>View Results</h2>
            <p>Browse and filter student examination results from the SBE module.</p>
        </div>
    </div>

    <div class="card page-section animate-in animate-delay-1">
        <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div class="field" style="flex:1; min-width:200px; margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" placeholder="Student name, ID, roll no, or exam code..." value="<?= e($search) ?>">
            </div>
            <div class="field" style="min-width:180px; margin-bottom:0;">
                <label>Exam</label>
                <select name="exam">
                    <option value="">All Exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= e((string) $exam['exam_id']) ?>" <?= $filterExam === (string) $exam['exam_id'] ? 'selected' : '' ?>><?= e($exam['exam_code']) ?> — <?= e(mb_strimwidth($exam['title'], 0, 20, '...')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="min-width:140px; margin-bottom:0;">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="Pass" <?= $filterStatus === 'Pass' ? 'selected' : '' ?>>Pass</option>
                    <option value="Fail" <?= $filterStatus === 'Fail' ? 'selected' : '' ?>>Fail</option>
                </select>
            </div>
            <div class="actions" style="margin-bottom:0;">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-ghost" href="view-results.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-card page-section animate-in animate-delay-2">
        <div class="card-header">
            <h3>Results <span class="badge" style="margin-left:8px;"><?= count($results) ?></span></h3>
            <p>All matching examination records</p>
        </div>
        <div class="table-wrapper" style="margin-top:10px;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Type</th>
                        <th>Marks</th>
                        <th>Percentage</th>
                        <th>Attempt</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="9"><div class="empty-state"><span class="empty-icon">&#128233;</span><p>No results found matching your criteria.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($results as $i => $r): ?>
                        <tr>
                            <td class="small"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-700" style="font-size:.88rem;"><?= e($r['full_name']) ?></div>
                                <div class="small">#<?= (int) $r['student_id'] ?> &middot; <?= e($r['roll_no'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="badge manual"><?= e($r['exam_code']) ?></span>
                                <div class="small" style="margin-top:2px;"><?= e(mb_strimwidth($r['exam_title'], 0, 22, '...')) ?></div>
                            </td>
                            <td><span class="badge teacher"><?= e($r['exam_type']) ?></span></td>
                            <td class="fw-700"><?= number_format((float) $r['obtained_marks'], 1) ?> / <?= number_format((float) $r['total_marks'], 1) ?></td>
                            <td class="fw-700" style="color:<?= (float) $r['percentage'] >= 50 ? 'var(--success)' : 'var(--danger)' ?>;"><?= number_format((float) $r['percentage'], 1) ?>%</td>
                            <td class="small">Attempt <?= (int) $r['attempt_no'] ?></td>
                            <td><span class="badge <?= e(strtolower($r['pass_fail_status'])) ?>"><?= e($r['pass_fail_status']) ?></span></td>
                            <td class="small"><?= e($r['published_at'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
