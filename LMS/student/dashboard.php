<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'dashboard';
$pageTitle = 'User Dashboard';

$detailsStmt = db()->prepare(
    'SELECT COUNT(*) AS course_count
     FROM enrollments
     WHERE student_id = ?'
);
$detailsStmt->execute([$user['id']]);
$courseCount = (int) $detailsStmt->fetchColumn();

$internalMarks = internal_mark_rows_for_student((int) $user['id']);
$internalMarkTotals = array_map(static function (array $row): array {
    return [
        'code' => $row['code'],
        'title' => $row['title'],
        'total' => internal_mark_total($row),
        'status' => $row['is_finalized'] ? 'Finalized' : 'Not Finalized',
    ];
}, $internalMarks);

$attendanceStmt = db()->prepare(
    'SELECT
        c.code,
        c.title,
        COUNT(a.id) AS total_classes,
        SUM(a.status = "present") AS present_count,
        SUM(a.status = "late") AS late_count,
        SUM(a.status = "absent") AS absent_count
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     LEFT JOIN attendance a ON a.course_id = c.id AND a.student_id = e.student_id
     WHERE e.student_id = ?
     GROUP BY c.id, c.code, c.title
     ORDER BY c.code'
);
$attendanceStmt->execute([$user['id']]);
$attendanceRows = $attendanceStmt->fetchAll();

$feesStmt = db()->prepare('SELECT * FROM fee_records WHERE student_id = ? ORDER BY due_date DESC');
$feesStmt->execute([$user['id']]);
$feeRows = $feesStmt->fetchAll();
$totalAmount = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $feeRows));
$paidAmount = array_sum(array_map(static fn (array $row): float => (float) $row['paid_amount'], $feeRows));
$balance = $totalAmount - $paidAmount;

$admissionDate = date('d-M-Y', strtotime((string) $user['created_at']));
$completionDate = date('d-M-Y', strtotime('+4 years', strtotime((string) $user['created_at'])));
$studentCode = 'LMS-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT);
$initials = strtoupper(substr((string) $user['name'], 0, 1));

require_once __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-shell">
    <aside class="student-panel">
        <?php if ($user['profile_photo']): ?>
            <img class="profile-photo" src="<?= app_url($user['profile_photo']) ?>" alt="">
        <?php else: ?>
            <div class="student-avatar"><?= e($initials) ?></div>
        <?php endif; ?>
        <h2><?= e(strtoupper($user['name'])) ?></h2>
        <p><?= e($studentCode) ?></p>

        <dl class="student-details">
            <div><dt>Department</dt><dd><?= e($user['department'] ?: 'Not set') ?></dd></div>
            <div><dt>Program</dt><dd><?= e($user['program'] ?: 'Not set') ?></dd></div>
            <div><dt>Admission Date</dt><dd class="green-text"><?= e($admissionDate) ?></dd></div>
            <div><dt>Completion Date</dt><dd class="red-text"><?= e($completionDate) ?></dd></div>
            <div><dt>Registered Courses</dt><dd><?= $courseCount ?></dd></div>
            <div><dt>Semester</dt><dd>Spring 2026</dd></div>
            <div><dt>Shift</dt><dd>Morning</dd></div>
        </dl>
    </aside>

    <div class="dashboard-content">
        <section class="dashboard-section">
            <header>Internal Marks Overview (2026 Spring)</header>
            <div class="table-card compact-table">
                <table>
                    <tr>
                        <th>Course ID</th>
                        <th>Title</th>
                        <th>Total Marks</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($internalMarkTotals as $row): ?>
                        <tr>
                            <td><?= e($row['code']) ?></td>
                            <td><?= e($row['title']) ?></td>
                            <td><?= e(number_format((float) $row['total'], 2)) ?></td>
                            <td>
                                <span class="mini-badge <?= $row['status'] === 'Finalized' ? 'red' : 'green' ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$internalMarkTotals): ?>
                        <tr><td colspan="4" class="muted text-center">No internal marks records found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </section>

        <section class="dashboard-section">
            <header>Attendance Record (2026 Spring)</header>
            <div class="table-card compact-table">
                <table>
                    <tr>
                        <th>Course ID</th>
                        <th>Title</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Absent</th>
                        <th>Attendance</th>
                    </tr>
                    <?php foreach ($attendanceRows as $row): ?>
                        <?php
                        $total = (int) $row['total_classes'];
                        $present = (int) $row['present_count'] + (int) $row['late_count'];
                        $percent = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?= e($row['code']) ?></td>
                            <td><?= e($row['title']) ?></td>
                            <td><?= $total ?></td>
                            <td><span class="mini-badge green"><?= (int) $row['present_count'] ?></span></td>
                            <td><span class="mini-badge yellow"><?= (int) $row['late_count'] ?></span></td>
                            <td><span class="mini-badge red"><?= (int) $row['absent_count'] ?></span></td>
                            <td><?= e((string) $percent) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$attendanceRows): ?>
                        <tr><td colspan="7" class="muted text-center">No attendance records found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </section>

        <section class="dashboard-section">
            <header>Fee Status Summary</header>
            <div class="fee-summary-card">
                <div class="fee-summary-metrics">
                    <div class="fee-metric">
                        <span class="metric-label">Outstanding Balance</span>
                        <strong class="metric-val <?= $balance > 0 ? 'warning-text' : 'success-text' ?>">PKR <?= number_format($balance) ?></strong>
                    </div>
                    <?php if ($feeRows): ?>
                        <?php $latestFee = $feeRows[0]; ?>
                        <div class="fee-metric">
                            <span class="metric-label">Latest Installment</span>
                            <span class="metric-sub"><?= e($latestFee['description']) ?> (<?= e($latestFee['semester']) ?>)</span>
                            <strong class="metric-val-sm">PKR <?= number_format((float) $latestFee['amount']) ?></strong>
                            <span class="badge <?= $latestFee['status'] === 'paid' ? 'approved' : 'pending' ?>"><?= e($latestFee['status']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="fee-summary-actions">
                    <a class="btn secondary" href="<?= app_url('student/fees.php') ?>">View Full Fee Ledger</a>
                </div>
            </div>
        </section>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
