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

$sql = "SELECT 
        r.receipt_id,
        r.receipt_no,
        r.issued_at,
        p.payment_id,
        p.amount_paid,
        p.payment_method,
        p.payment_date,
        s.full_name,
        s.roll_no,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        ORDER BY r.receipt_id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-receipt text-primary"></i> Receipts</h2>
    <a href="../payments/index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Payments
    </a>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list"></i> Receipts List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Receipt No</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th class="text-end">Amount (PKR)</th>
                        <th>Payment Method</th>
                        <th>Issued At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    if(mysqli_num_rows($result) > 0): 
                        while($row = mysqli_fetch_assoc($result)): 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['receipt_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></td>
                        <td class="text-end"><strong>PKR <?php echo number_format($row['amount_paid'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><?php echo date('d-M-Y h:i A', strtotime($row['issued_at'])); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $row['receipt_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="print.php?id=<?php echo $row['receipt_id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No receipts found. Payments will auto-generate receipts.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>