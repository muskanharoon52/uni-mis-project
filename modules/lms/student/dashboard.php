<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'dashboard';
$pageTitle = 'Student Dashboard';
$now = new DateTimeImmutable('now');

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
    "SELECT c.course_code, c.course_title, COUNT(a.attendance_id) AS total_classes,
            SUM(a.status = 'Present') AS present_count, SUM(a.status = 'Absent') AS absent_count
     FROM lms_enrollments e
     JOIN courses c ON c.course_id = e.course_id
     LEFT JOIN attendance a ON a.course_id = c.course_id AND a.student_id = e.student_user_id
     WHERE e.student_user_id = ?
     GROUP BY c.course_id, c.course_code, c.course_title ORDER BY c.course_code"
);
$attendanceStmt->execute([$user['id']]);
$attendanceRows = $attendanceStmt->fetchAll();

$feesStmt = db()->prepare('SELECT * FROM lms_fees WHERE student_user_id = ? ORDER BY due_date DESC');
$feesStmt->execute([$user['id']]);
$feeRows = $feesStmt->fetchAll();
$totalAmount = array_sum(array_map(static fn ($r) => (float) $r['amount'], $feeRows));
$paidAmount = array_sum(array_map(static fn ($r) => $r['status'] === 'paid' ? (float) $r['amount'] : ($r['status'] === 'partial' ? (float) $r['amount'] * 0.5 : 0.0), $feeRows));
$balance = $totalAmount - $paidAmount;

$studentCode = 'LMS-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT);
$initials = strtoupper(substr((string) $user['name'], 0, 1));
$greetingHour = (int) $now->format('G');
$greeting = $greetingHour < 12 ? 'Good Morning' : ($greetingHour < 17 ? 'Good Afternoon' : 'Good Evening');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="greeting-card">
    <?php if ($user['profile_photo']): ?>
        <img class="dashboard-avatar-img" src="<?= app_url($user['profile_photo']) ?>" alt="">
    <?php else: ?>
        <div class="greeting-card-avatar"><?= e($initials) ?></div>
    <?php endif; ?>
    <div class="greeting-card-body">
        <span class="eyebrow">Student Portal &middot; Spring 2026</span>
        <h1><?= e($greeting . ', ' . $user['name']) ?></h1>
        <div class="student-info-row">
            <span><strong>ID:</strong> <?= e($studentCode) ?></span>
            <span><strong>Department:</strong> <?= e($user['department'] ?: 'N/A') ?></span>
            <span><strong>Status:</strong> <span class="badge badge-active"><?= e($user['status'] ?? 'Active') ?></span></span>
        </div>
        <p class="dashboard-date"><?= e($now->format('l, F j, Y')) ?></p>
    </div>
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
                        <td style="font-weight:500;"><?= e($row['title']) ?></td>
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
                <tr><th>Course</th><th>Title</th><th>Total</th><th>Present</th><th>Absent</th><th>Rate</th></tr>
            </thead>
            <tbody>
                <?php foreach ($attendanceRows as $row): ?>
                    <?php $total = (int) $row['total_classes']; $present = (int) $row['present_count']; $percent = $total > 0 ? round(($present / $total) * 100, 1) : 0; ?>
                    <tr>
                        <td><span class="badge badge-outline"><?= e($row['course_code']) ?></span></td>
                        <td style="font-weight:500;"><?= e($row['course_title']) ?></td>
                        <td><?= $total ?></td>
                        <td><span class="badge badge-active"><?= (int) $row['present_count'] ?></span></td>
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
    <div style="padding:1.5rem;display:flex;gap:2rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <div class="stat-label">Outstanding Balance</div>
            <div class="stat-number <?= $balance > 0 ? 'warning-text' : 'success-text' ?>">PKR <?= number_format($balance) ?></div>
        </div>
        <?php if ($feeRows): ?>
            <?php $latestFee = $feeRows[0]; ?>
            <div style="flex:1;min-width:180px;">
                <div class="stat-label">Latest Fee</div>
                <div class="stat-hint">Due: <?= e($latestFee['due_date'] ?? 'N/A') ?></div>
                <div style="font-size:1.2rem;font-weight:700;margin-top:2px;">PKR <?= number_format((float) $latestFee['amount']) ?></div>
                <span class="badge badge-<?= $latestFee['status'] === 'paid' ? 'active' : 'draft' ?>" style="margin-top:6px;"><?= e(ucfirst($latestFee['status'])) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.student-info-row { display: flex; flex-wrap: wrap; gap: 8px 20px; margin-top: 6px; font-size: 13px; color: rgba(255,255,255,0.8); }
.student-info-row strong { color: #fff; font-weight: 600; }
.dashboard-date { margin-top: 4px; font-size: 12px; color: rgba(255,255,255,0.6); margin-bottom: 0; }
.dashboard-avatar-img { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); flex-shrink: 0; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>