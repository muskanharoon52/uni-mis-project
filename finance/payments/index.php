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
// ... baaki code
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

$sql = "SELECT 
        p.payment_id,
        p.amount_paid,
        p.payment_method,
        p.payment_date,
        p.status,
        s.full_name,
        s.roll_no,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount
        FROM payments p
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        ORDER BY p.payment_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-money-bill-wave text-success"></i> Payment Records</h2>
            <p class="text-muted">View all payment transactions</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Receive Payment</a>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list"></i> Payments List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th class="text-end">Amount (PKR)</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
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
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></td>
                            <td class="text-end"><strong>PKR <?php echo number_format($row['amount_paid'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($row['payment_date'])); ?></td>
                            <td>
                                <?php if($row['status'] == 'Success'): ?>
                                    <span class="badge bg-success">Success</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo $row['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No payments found. <a href="add.php">Receive a payment now!</a></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>