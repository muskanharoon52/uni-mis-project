<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'dashboard';
$pageTitle = 'Student Dashboard';
$now = new DateTimeImmutable('now');

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

$totalPresent = 0;
$totalClasses = 0;
foreach ($attendanceRows as $row) {
    $totalClasses += (int) $row['total_classes'];
    $totalPresent += (int) $row['present_count'] + (int) $row['late_count'];
}
$overallAttendance = $totalClasses > 0 ? round(($totalPresent / $totalClasses) * 100, 1) : 0;

$feesStmt = db()->prepare('SELECT * FROM fee_records WHERE student_id = ? ORDER BY due_date DESC');
$feesStmt->execute([$user['id']]);
$feeRows = $feesStmt->fetchAll();
$totalAmount = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $feeRows));
$paidAmount = array_sum(array_map(static fn (array $row): float => (float) $row['paid_amount'], $feeRows));
$balance = $totalAmount - $paidAmount;

$studentCode = 'LMS-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT);
$initials = strtoupper(substr((string) $user['name'], 0, 1));

$greetingHour = (int) $now->format('G');
$greeting = $greetingHour < 12 ? 'Good Morning' : ($greetingHour < 17 ? 'Good Afternoon' : 'Good Evening');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="greeting-card">
    <div class="greeting-card-body">
        <span class="eyebrow">Student Portal &middot; Spring 2026</span>
        <h1><?= e($greeting . ', ' . $user['name']) ?></h1>
        <p class="muted" style="margin-top:4px;"><?= e($now->format('l, F j, Y')) ?> &middot; <?= e($studentCode) ?></p>
    </div>
    <div class="greeting-card-avatar"><?= e($initials) ?></div>
</div>

<div class="stat-row">
    <div class="stat-card-v2"><div class="stat-label">Courses</div><div class="stat-number"><?= $courseCount ?></div><div class="stat-hint">Registered this semester</div></div>
    <div class="stat-card-v2"><div class="stat-label">Attendance</div><div class="stat-number"><?= $overallAttendance ?>%</div><div class="stat-hint">Overall average</div></div>
    <div class="stat-card-v2"><div class="stat-label">Balance</div><div class="stat-number <?= $balance > 0 ? 'warning-text' : 'success-text' ?>">PKR <?= number_format($balance) ?></div><div class="stat-hint">Outstanding fees</div></div>
    <div class="stat-card-v2"><div class="stat-label">Internal Marks</div><div class="stat-number"><?= count($internalMarkTotals) ?></div><div class="stat-hint">Courses with marks</div></div>
</div>

<div class="action-cards">
    <a class="action-card" href="<?= app_url('student/courses.php') ?>">
        <span class="action-card-icon">&#128218;</span>
        <div class="action-card-title">My Courses</div>
        <div class="action-card-desc"><?= $courseCount ?> enrolled course<?= $courseCount !== 1 ? 's' : '' ?></div>
    </a>
    <a class="action-card" href="<?= app_url('student/attendance.php') ?>">
        <span class="action-card-icon">&#128197;</span>
        <div class="action-card-title">Attendance</div>
        <div class="action-card-desc"><?= $overallAttendance ?>% overall</div>
    </a>
    <a class="action-card" href="<?= app_url('student/marks.php') ?>">
        <span class="action-card-icon">&#128200;</span>
        <div class="action-card-title">Internal Marks</div>
        <div class="action-card-desc">View marks &amp; grades</div>
    </a>
    <a class="action-card" href="<?= app_url('student/fees.php') ?>">
        <span class="action-card-icon">&#128176;</span>
        <div class="action-card-title">Fees</div>
        <div class="action-card-desc">View fee status &amp; payments</div>
    </a>
    <a class="action-card" href="<?= app_url('student/queries.php') ?>">
        <span class="action-card-icon">&#10067;</span>
        <div class="action-card-title">Queries</div>
        <div class="action-card-desc">Ask a question</div>
    </a>
    <a class="action-card" href="<?= app_url('student/applications.php') ?>">
        <span class="action-card-icon">&#128203;</span>
        <div class="action-card-title">Applications</div>
        <div class="action-card-desc">Submit applications</div>
    </a>
</div>

<?php if ($internalMarkTotals): ?>
<div class="card mt-4">
    <div class="card-header">
        <h3>Internal Marks Overview</h3>
        <a class="btn btn-sm btn-outline" href="<?= app_url('student/marks.php') ?>">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Course</th><th>Title</th><th>Total Marks</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($internalMarkTotals as $row): ?>
                    <tr>
                        <td><span class="badge badge-outline"><?= e($row['code']) ?></span></td>
                        <td><?= e($row['title']) ?></td>
                        <td><strong><?= e(number_format((float) $row['total'], 2)) ?></strong></td>
                        <td><span class="badge badge-<?= $row['status'] === 'Finalized' ? 'active' : 'draft' ?>"><?= e($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($attendanceRows): ?>
<div class="card mt-4">
    <div class="card-header">
        <h3>Attendance Record</h3>
        <a class="btn btn-sm btn-outline" href="<?= app_url('student/attendance.php') ?>">Details</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Course</th><th>Title</th><th>Total</th><th>Present</th><th>Late</th><th>Absent</th><th>Rate</th></tr>
            </thead>
            <tbody>
                <?php foreach ($attendanceRows as $row): ?>
                    <?php
                    $total = (int) $row['total_classes'];
                    $present = (int) $row['present_count'] + (int) $row['late_count'];
                    $percent = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><span class="badge badge-outline"><?= e($row['code']) ?></span></td>
                        <td><?= e($row['title']) ?></td>
                        <td><?= $total ?></td>
                        <td><span class="badge badge-active"><?= (int) $row['present_count'] ?></span></td>
                        <td><span class="badge badge-draft"><?= (int) $row['late_count'] ?></span></td>
                        <td><span class="badge badge-inactive"><?= (int) $row['absent_count'] ?></span></td>
                        <td><strong><?= e((string) $percent) ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-header">
        <h3>Fee Status</h3>
        <a class="btn btn-sm btn-outline" href="<?= app_url('student/fees.php') ?>">View Ledger</a>
    </div>
    <div style="padding:1.5rem;display:flex;gap:1.5rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <div class="stat-label">Outstanding Balance</div>
            <div class="stat-number <?= $balance > 0 ? 'warning-text' : 'success-text' ?>">PKR <?= number_format($balance) ?></div>
        </div>
        <?php if ($feeRows): ?>
            <?php $latestFee = $feeRows[0]; ?>
            <div style="flex:1;min-width:180px;">
                <div class="stat-label">Latest Installment</div>
                <div class="muted" style="font-size:.85rem;"><?= e($latestFee['description']) ?> (<?= e($latestFee['semester']) ?>)</div>
                <div class="stat-hint">PKR <?= number_format((float) $latestFee['amount']) ?></div>
                <span class="badge badge-<?= $latestFee['status'] === 'paid' ? 'active' : 'draft' ?>" style="margin-top:4px;"><?= e($latestFee['status']) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
