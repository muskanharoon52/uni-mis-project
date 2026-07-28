<?php
$pageTitle = 'Print Receipt';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid receipt ID");
    exit();
}

$receipt_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT r.receipt_no, r.issued_at,
               p.amount_paid, p.payment_method, p.transaction_ref,
               s.full_name, s.roll_no, s.father_name,
               d.department_name, sm.semester_name, ses.session_name,
               sf.total_amount, sf.paid_amount, sf.remaining_amount
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        WHERE r.receipt_id = '$receipt_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Receipt not found");
    exit();
}

$receipt = mysqli_fetch_assoc($result);
?>

<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .content { margin-left: 0 !important; padding: 0 !important; }
    .receipt-box { border: 2px solid #000 !important; box-shadow: none !important; }
}
</style>

<div class="no-print" style="margin-bottom:16px;display:flex;gap:8px;">
    <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
    <a href="index.php" class="btn btn-ghost">&#8592; Back</a>
</div>

<div class="card receipt-box" style="border:2px solid var(--success);max-width:640px;margin:0 auto;">
    <div style="padding:28px;">
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:1.2rem;font-weight:700;">University MIS</div>
            <div style="font-size:.88rem;color:var(--muted);">Official Fee Receipt</div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;margin-bottom:14px;">
                <div><span class="muted" style="font-size:.82rem;display:block;">Receipt No</span><strong><?= htmlspecialchars($receipt['receipt_no']) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Date</span><?= date('M j, Y g:i A', strtotime($receipt['issued_at'])) ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Payment Method</span><?= htmlspecialchars($receipt['payment_method']) ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Transaction Ref</span><?= htmlspecialchars($receipt['transaction_ref'] ?? 'N/A') ?></div>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                    <div>
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:6px;">Student Information</div>
                        <div style="line-height:1.8;">
                            <strong>Name:</strong> <?= htmlspecialchars($receipt['full_name']) ?><br>
                            <strong>Father Name:</strong> <?= htmlspecialchars($receipt['father_name'] ?? 'N/A') ?><br>
                            <strong>Roll No:</strong> <?= htmlspecialchars($receipt['roll_no'] ?? 'N/A') ?><br>
                            <strong>Program:</strong> <?= htmlspecialchars($receipt['department_name']) ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:6px;">Fee Details</div>
                        <div style="line-height:1.8;">
                            <strong>Semester:</strong> <?= htmlspecialchars($receipt['semester_name']) ?><br>
                            <strong>Total Fee:</strong> PKR <?= number_format($receipt['total_amount'], 2) ?><br>
                            <strong>Amount Paid:</strong> PKR <?= number_format($receipt['amount_paid'], 2) ?><br>
                            <strong>Remaining:</strong> PKR <?= number_format($receipt['remaining_amount'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align:center;font-size:.82rem;color:var(--muted);border-top:1px solid var(--border);padding-top:14px;">
                This is a system-generated receipt. Valid without signature.<br>
                Generated on: <?= date('M j, Y g:i A') ?>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
