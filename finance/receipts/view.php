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
        r.receipt_id,
        r.receipt_no,
        r.issued_at,
        p.payment_id,
        p.amount_paid,
        p.payment_method,
        p.transaction_ref,
        p.payment_date,
        s.full_name,
        s.roll_no,
        s.father_name,
        s.email,
        s.contact_no,
        d.department_name,
        sm.semester_name,
        ses.session_name,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-receipt text-primary"></i> Receipt Details</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
</div>

<div class="card shadow receipt-border" style="border: 2px solid #198754; border-radius: 10px; padding: 20px;">
    <div class="card-header bg-success text-white text-center">
        <h3><i class="fas fa-university"></i> University MIS</h3>
        <h5>Official Fee Receipt</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>Receipt No:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($receipt['receipt_no']); ?></span></p>
                <p><strong>Date:</strong> <?php echo date('d-M-Y h:i A', strtotime($receipt['issued_at'])); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($receipt['payment_method']); ?></p>
                <p><strong>Transaction Ref:</strong> <?php echo htmlspecialchars($receipt['transaction_ref'] ?? 'N/A'); ?></p>
            </div>
        </div>

        <hr>

        <div class="row mb-3">
            <div class="col-md-6">
                <h6><i class="fas fa-user-graduate"></i> Student Information</h6>
                <p>
                    <strong>Name:</strong> <?php echo htmlspecialchars($receipt['full_name']); ?><br>
                    <strong>Father Name:</strong> <?php echo htmlspecialchars($receipt['father_name'] ?? 'N/A'); ?><br>
                    <strong>Roll No:</strong> <?php echo htmlspecialchars($receipt['roll_no'] ?? 'N/A'); ?><br>
                    <strong>Program:</strong> <?php echo htmlspecialchars($receipt['department_name']); ?>
                </p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle"></i> Fee Details</h6>
                <p>
                    <strong>Semester:</strong> <?php echo htmlspecialchars($receipt['semester_name']); ?><br>
                    <strong>Session:</strong> <?php echo htmlspecialchars($receipt['session_name']); ?><br>
                    <strong>Total Fee:</strong> PKR <?php echo number_format($receipt['total_amount'], 2); ?><br>
                    <strong>Paid Amount:</strong> PKR <?php echo number_format($receipt['paid_amount'], 2); ?>
                </p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-12">
                <h6><i class="fas fa-money-bill-wave"></i> Payment Summary</h6>
                <table class="table table-bordered">
                    <tr><th style="width:50%;">Amount Paid</th><td><strong>PKR <?php echo number_format($receipt['amount_paid'], 2); ?></strong></td></tr>
                    <tr><th>Remaining Amount</th><td><strong>PKR <?php echo number_format($receipt['remaining_amount'], 2); ?></strong></td></tr>
                    <tr><th>Payment Status</th><td>
                        <?php 
                        if($receipt['remaining_amount'] == 0):
                            echo '<span class="badge bg-success">Fully Paid</span>';
                        elseif($receipt['paid_amount'] > 0):
                            echo '<span class="badge bg-warning">Partially Paid</span>';
                        else:
                            echo '<span class="badge bg-danger">Unpaid</span>';
                        endif; ?>
                    </td></tr>
                </table>
            </div>
        </div>

        <div class="text-center text-muted mt-3">
            <small>This is a system-generated receipt. Valid without signature.</small><br>
            <small>Generated on: <?php echo date('d-M-Y h:i A'); ?></small>
        </div>
    </div>
</div>

<div class="mt-3 text-end">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>