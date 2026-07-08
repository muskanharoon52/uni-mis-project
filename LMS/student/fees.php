<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'fees';
$pageTitle = 'Fees';

$feesStmt = db()->prepare('SELECT * FROM fee_records WHERE student_id = ? ORDER BY due_date DESC');
$feesStmt->execute([$user['id']]);
$feeRows = $feesStmt->fetchAll();
$totalAmount = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $feeRows));
$paidAmount = array_sum(array_map(static fn (array $row): float => (float) $row['paid_amount'], $feeRows));
$balance = $totalAmount - $paidAmount;

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Fees</h1></div>
<section class="grid">
    <div class="card"><h2>Total Fee</h2><div class="stat">PKR <?= number_format($totalAmount) ?></div></div>
    <div class="card"><h2>Balance</h2><div class="stat">PKR <?= number_format($balance) ?></div><p><span class="badge <?= $balance <= 0 ? 'approved' : 'pending' ?>"><?= $balance <= 0 ? 'Cleared' : 'Pending' ?></span></p></div>
</section>
<section class="dashboard-section">
    <header>Fee Record</header>
    <div class="table-card compact-table">
        <table>
            <tr><th>Semester</th><th>Description</th><th>Amount</th><th>Paid</th><th>Due Date</th><th>Status</th></tr>
            <?php foreach ($feeRows as $fee): ?>
                <tr>
                    <td><?= e($fee['semester']) ?></td>
                    <td><?= e($fee['description']) ?></td>
                    <td>PKR <?= number_format((float) $fee['amount']) ?></td>
                    <td>PKR <?= number_format((float) $fee['paid_amount']) ?></td>
                    <td><?= e($fee['due_date']) ?></td>
                    <td><span class="mini-badge <?= $fee['status'] === 'paid' ? 'green' : 'yellow' ?>"><?= e($fee['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
