<?php
$pageTitle = 'Receipt Details';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid receipt ID");
    exit();
}

$receipt_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT r.receipt_id, r.receipt_no, r.issued_at,
               p.payment_id, p.amount_paid, p.payment_method, p.transaction_ref, p.payment_date,
               s.full_name, s.roll_no, s.father_name, d.department_name,
               sm.semester_name, ses.session_name,
               sf.total_amount, sf.paid_amount, sf.remaining_amount,
               u.full_name AS issued_by_name
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        LEFT JOIN users u ON u.user_id = r.issued_by
        WHERE r.receipt_id = '$receipt_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Receipt not found");
    exit();
}

$receipt = mysqli_fetch_assoc($result);

if ($receipt['remaining_amount'] == 0) $payBadge = 'badge-active';
elseif ($receipt['paid_amount'] > 0) $payBadge = 'badge-pending';
else $payBadge = 'badge-inactive';
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Receipts</a>
</div>

<div class="card" style="border:2px solid var(--success);max-width:720px;">
    <div class="card-header" style="background:var(--success);color:white;text-align:center;padding:20px;">
        <div style="font-size:1.1rem;font-weight:700;">University MIS</div>
        <div style="font-size:.88rem;opacity:.9;">Official Fee Receipt</div>
    </div>
    <div style="padding:24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;margin-bottom:16px;">
            <div><span class="muted" style="font-size:.82rem;display:block;">Receipt No</span><span class="badge badge-outline"><?= htmlspecialchars($receipt['receipt_no']) ?></span></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Date</span><?= date('M j, Y g:i A', strtotime($receipt['issued_at'])) ?></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Payment Method</span><?= htmlspecialchars($receipt['payment_method']) ?></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Transaction Ref</span><?= htmlspecialchars($receipt['transaction_ref'] ?? 'N/A') ?></div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:16px;margin-bottom:16px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                <div>
                    <div style="font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Student Information</div>
                    <div style="display:grid;gap:6px;">
                        <div><span class="muted" style="font-size:.78rem;display:block;">Name</span><strong><?= htmlspecialchars($receipt['full_name']) ?></strong></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Father Name</span><?= htmlspecialchars($receipt['father_name'] ?? 'N/A') ?></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Roll No</span><?= htmlspecialchars($receipt['roll_no'] ?? 'N/A') ?></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Program</span><?= htmlspecialchars($receipt['department_name']) ?></div>
                    </div>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Fee Details</div>
                    <div style="display:grid;gap:6px;">
                        <div><span class="muted" style="font-size:.78rem;display:block;">Semester</span><?= htmlspecialchars($receipt['semester_name']) ?></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Session</span><?= htmlspecialchars($receipt['session_name']) ?></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Total Fee</span>PKR <?= number_format($receipt['total_amount'], 2) ?></div>
                        <div><span class="muted" style="font-size:.78rem;display:block;">Paid</span>PKR <?= number_format($receipt['paid_amount'], 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:16px;">
            <div style="font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:8px;">Payment Summary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div><span class="muted" style="font-size:.78rem;display:block;">Amount Paid</span><strong>PKR <?= number_format($receipt['amount_paid'], 2) ?></strong></div>
                <div><span class="muted" style="font-size:.78rem;display:block;">Remaining</span><strong style="color:<?= $receipt['remaining_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">PKR <?= number_format($receipt['remaining_amount'], 2) ?></strong></div>
                <div><span class="muted" style="font-size:.78rem;display:block;">Status</span><span class="badge <?= $payBadge ?>">
                    <?= $receipt['remaining_amount'] == 0 ? 'Fully Paid' : ($receipt['paid_amount'] > 0 ? 'Partially Paid' : 'Unpaid') ?>
                </span></div>
            </div>
        </div>
        <div style="text-align:center;margin-top:20px;font-size:.82rem;color:var(--muted);">
            This is a system-generated receipt. Valid without signature.
        </div>
    </div>
</div>

<div style="margin-top:16px;text-align:right;">
    <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
