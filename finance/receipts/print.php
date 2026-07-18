<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid receipt ID");
    exit();
}

$receipt_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT 
        r.receipt_no,
        r.issued_at,
        p.amount_paid,
        p.payment_method,
        p.transaction_ref,
        p.payment_date,
        s.full_name,
        s.roll_no,
        s.father_name,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
        d.department_name,
        sm.semester_name,
        ses.session_name
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

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h2><i class="fas fa-print text-success"></i> Print Receipt</h2>
    <div>
        <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> Print</button>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .receipt-box { border: 2px solid #000 !important; }
}
</style>

<div class="card shadow receipt-box" style="border: 2px solid #198754; border-radius: 10px; padding: 20px;">
    <div class="text-center">
        <h3><i class="fas fa-university"></i> University MIS</h3>
        <h5>Official Fee Receipt</h5>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <p><strong>Receipt No:</strong> <?php echo htmlspecialchars($receipt['receipt_no']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d-M-Y h:i A', strtotime($receipt['issued_at'])); ?></p>
        </div>
        <div class="col-md-6 text-end">
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($receipt['payment_method']); ?></p>
            <p><strong>Transaction Ref:</strong> <?php echo htmlspecialchars($receipt['transaction_ref'] ?? 'N/A'); ?></p>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <h6>Student Information</h6>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($receipt['full_name']); ?><br>
            <strong>Roll No:</strong> <?php echo htmlspecialchars($receipt['roll_no'] ?? 'N/A'); ?><br>
            <strong>Program:</strong> <?php echo htmlspecialchars($receipt['department_name']); ?></p>
        </div>
        <div class="col-md-6">
            <h6>Fee Details</h6>
            <p><strong>Total Fee:</strong> PKR <?php echo number_format($receipt['total_amount'], 2); ?><br>
            <strong>Amount Paid:</strong> PKR <?php echo number_format($receipt['amount_paid'], 2); ?><br>
            <strong>Remaining:</strong> PKR <?php echo number_format($receipt['remaining_amount'], 2); ?></p>
        </div>
    </div>
    <hr>
    <div class="text-center text-muted">
        <small>This is a system-generated receipt. Valid without signature.</small>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>