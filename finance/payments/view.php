<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
// Check if user is Finance Officer (role_id = 3)
if ($_SESSION['role_id'] != 3) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}
// ... baaki codes
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid payment ID");
    exit();
}

$payment_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT 
        p.payment_id,
        p.amount_paid,
        p.payment_method,
        p.transaction_ref,
        p.payment_date,
        p.status,
        s.full_name,
        s.roll_no,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-money-bill-wave"></i> Payment Details</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Payment ID</th><td>#<?php echo $payment['payment_id']; ?></td></tr>
                        <tr><th>Student Name</th><td><strong><?php echo htmlspecialchars($payment['full_name']); ?></strong></td></tr>
                        <tr><th>Roll No</th><td><?php echo htmlspecialchars($payment['roll_no'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Amount Paid</th><td><strong>PKR <?php echo number_format($payment['amount_paid'], 2); ?></strong></td></tr>
                        <tr><th>Payment Method</th><td><?php echo htmlspecialchars($payment['payment_method']); ?></td></tr>
                        <tr><th>Transaction Ref</th><td><?php echo htmlspecialchars($payment['transaction_ref'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Payment Date</th><td><?php echo date('d-M-Y h:i A', strtotime($payment['payment_date'])); ?></td></tr>
                        <tr><th>Status</th><td><?php if($payment['status'] == 'Success'): ?><span class="badge bg-success">Success</span><?php else: ?><span class="badge bg-danger"><?php echo $payment['status']; ?></span><?php endif; ?></td></tr>
                        <tr><th>Received By</th><td><?php echo htmlspecialchars($payment['received_by_name'] ?? 'System'); ?></td></tr>
                        <tr><th>Total Fee</th><td>PKR <?php echo number_format($payment['total_amount'], 2); ?></td></tr>
                        <tr><th>Total Paid</th><td>PKR <?php echo number_format($payment['paid_amount'], 2); ?></td></tr>
                        <tr><th>Remaining</th><td><strong>PKR <?php echo number_format($payment['remaining_amount'], 2); ?></strong></td></tr>
                    </table>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Payments</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>