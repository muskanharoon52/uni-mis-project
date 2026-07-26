<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'fees';
$pageTitle = 'Fees';

$feesStmt = db()->prepare('SELECT * FROM lms_fees WHERE student_user_id = ? ORDER BY due_date DESC');
$feesStmt->execute([$user['id']]);
$feeRows = $feesStmt->fetchAll();
$totalAmount = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $feeRows));
$paidAmount = array_sum(array_map(static fn (array $row): float => $row['status'] === 'paid' ? (float) $row['amount'] : ($row['status'] === 'partial' ? (float) $row['amount'] * 0.5 : 0.0), $feeRows));
$balance = $totalAmount - $paidAmount;

require_once __DIR__ . '/../includes/header.php';
?>
<div class="stat-row">
    <div class="stat-card-v2"><div class="stat-label">Total Fee</div><div class="stat-number">PKR <?= number_format($totalAmount) ?></div></div>
    <div class="stat-card-v2"><div class="stat-label">Balance</div><div class="stat-number <?= $balance > 0 ? 'warning-text' : 'success-text' ?>">PKR <?= number_format($balance) ?></div><div class="stat-hint"><span class="badge badge-<?= $balance <= 0 ? 'active' : 'draft' ?>"><?= $balance <= 0 ? 'Cleared' : 'Pending' ?></span></div></div>
</div>
<div class="card">
    <div class="card-header"><h3>Fee Records</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Course ID</th><th>Amount</th><th>Due Date</th><th>Status</th></tr>
            <?php foreach ($feeRows as $fee): ?>
                <tr>
                    <td><?= e((string) $fee['course_id']) ?></td>
                    <td>PKR <?= number_format((float) $fee['amount']) ?></td>
                    <td><?= e($fee['due_date']) ?></td>
                    <td><span class="badge badge-<?= $fee['status'] === 'paid' ? 'active' : 'draft' ?>"><?= e($fee['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
