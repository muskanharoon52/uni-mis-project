<?php
$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';

$sql = "SELECT p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.status,
               s.full_name, s.roll_no, sf.total_amount, sf.paid_amount, sf.remaining_amount
        FROM payments p
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        ORDER BY p.payment_id DESC";
$result = mysqli_query($conn, $sql);
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>Payment Records</h3>
            <a href="add.php" class="btn btn-primary">+ Receive Payment</a>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th style="text-align:right">Amount (PKR)</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td style="text-align:right;font-weight:700;"><?= number_format($row['amount_paid'], 2) ?></td>
                            <td><span class="badge badge-outline"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                            <td class="muted"><?= date('M j, Y', strtotime($row['payment_date'])) ?></td>
                            <td><span class="badge <?= $row['status'] === 'Success' ? 'badge-active' : 'badge-inactive' ?>"><?= $row['status'] ?></span></td>
                            <td><a href="view.php?id=<?= $row['payment_id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="muted text-center" style="padding:24px;">No payments found. <a href="add.php" style="color:var(--accent);font-weight:600;">Receive a payment now.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
