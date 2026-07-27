<?php
$pageTitle = 'Receipts';
include __DIR__ . '/../includes/header.php';

$sql = "SELECT r.receipt_id, r.receipt_no, r.issued_at, p.payment_id, p.amount_paid, p.payment_method, p.payment_date,
               s.full_name, s.roll_no, sf.total_amount, sf.paid_amount, sf.remaining_amount
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        ORDER BY r.receipt_id DESC";
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
            <h3>Receipts</h3>
            <a href="../payments/index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Payments</a>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Receipt No</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th style="text-align:right">Amount (PKR)</th>
                    <th>Method</th>
                    <th>Issued</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['receipt_no']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td style="text-align:right;font-weight:700;">PKR <?= number_format($row['amount_paid'], 2) ?></td>
                            <td><span class="badge badge-outline"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                            <td class="muted"><?= date('M j, g:i A', strtotime($row['issued_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="view.php?id=<?= $row['receipt_id'] ?>" class="btn btn-sm btn-outline">View</a>
                                    <a href="print.php?id=<?= $row['receipt_id'] ?>" class="btn btn-sm btn-primary" target="_blank">Print</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="muted text-center" style="padding:24px;">No receipts found. Payments auto-generate receipts.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
