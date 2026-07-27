<?php
$pageTitle = 'Payment Details';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid payment ID");
    exit();
}

$payment_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT p.payment_id, p.amount_paid, p.payment_method, p.transaction_ref, p.payment_date, p.status,
               s.full_name, s.roll_no,
               sf.total_amount, sf.paid_amount, sf.remaining_amount,
               u.full_name AS received_by_name
        FROM payments p
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        LEFT JOIN users u ON u.user_id = p.received_by
        WHERE p.payment_id = '$payment_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Payment not found");
    exit();
}

$payment = mysqli_fetch_assoc($result);
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Payments</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-header">
        <h3>Payment #<?= $payment['payment_id'] ?></h3>
    </div>
    <div style="padding:22px;">
        <div class="info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px;margin-bottom:18px;">
            <div><span class="muted" style="font-size:.82rem;display:block;">Student</span><strong><?= htmlspecialchars($payment['full_name']) ?></strong></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Roll No</span><?= htmlspecialchars($payment['roll_no'] ?? 'N/A') ?></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Amount Paid</span><strong style="font-size:1.1rem;">PKR <?= number_format($payment['amount_paid'], 2) ?></strong></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Payment Method</span><span class="badge badge-outline"><?= htmlspecialchars($payment['payment_method']) ?></span></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Transaction Ref</span><?= htmlspecialchars($payment['transaction_ref'] ?? 'N/A') ?></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Payment Date</span><?= date('M j, Y g:i A', strtotime($payment['payment_date'])) ?></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Status</span>
                <span class="badge <?= $payment['status'] === 'Success' ? 'badge-active' : 'badge-inactive' ?>"><?= $payment['status'] ?></span>
            </div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Received By</span><?= htmlspecialchars($payment['received_by_name'] ?? 'System') ?></div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:16px;">
            <div style="font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Fee Summary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div><span class="muted" style="font-size:.78rem;display:block;">Total Fee</span>PKR <?= number_format($payment['total_amount'], 2) ?></div>
                <div><span class="muted" style="font-size:.78rem;display:block;">Total Paid</span>PKR <?= number_format($payment['paid_amount'], 2) ?></div>
                <div><span class="muted" style="font-size:.78rem;display:block;">Remaining</span><strong style="color:<?= $payment['remaining_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">PKR <?= number_format($payment['remaining_amount'], 2) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
