<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /uni-mis-project/modules/sso/login.php');
    exit();
}
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: /uni-mis-project/modules/sso/login.php?error=Access denied');
    exit();
}

// Include database connection
include __DIR__ . '/../../../config/db_connect.php';

// Include header
include __DIR__ . '/../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<script>window.location.href="index.php?error=Invalid fee record ID";</script>';
    exit();
}

$student_fee_id = mysqli_real_escape_string($conn, $_GET['id']);

// First, let's check what columns exist in student_fee table
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM student_fee");
$columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $columns[] = $col['Field'];
}

// Determine the correct primary key column name
$id_column = 'student_fee_id';
if (!in_array('student_fee_id', $columns)) {
    if (in_array('fee_id', $columns)) {
        $id_column = 'fee_id';
    } elseif (in_array('id', $columns)) {
        $id_column = 'id';
    } else {
        // If no matching column found, try to find the first column
        $id_column = $columns[0] ?? 'student_fee_id';
    }
}

// Check if remaining_amount column exists
$remaining_column = in_array('remaining_amount', $columns) ? 'remaining_amount' : 'total_amount - paid_amount as remaining_amount';

// Check if generated_at column exists
$generated_column = in_array('generated_at', $columns) ? 'generated_at' : (in_array('created_at', $columns) ? 'created_at' : 'created_at');

// Build the main query
$sql = "SELECT 
        sf.{$id_column} as student_fee_id,
        sf.student_id,
        sf.total_amount,
        sf.paid_amount,
        {$remaining_column} as remaining_amount,
        sf.status,
        sf.{$generated_column} as generated_at,
        sf.due_date,
        s.full_name,
        s.roll_no,
        s.father_name,
        s.email,
        s.contact_no,
        d.department_name,
        sm.semester_name,
        ses.session_name
        FROM student_fee sf
        JOIN students s ON s.student_id = sf.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        WHERE sf.{$id_column} = '$student_fee_id'";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<script>window.location.href="index.php?error=Fee record not found or invalid ID";</script>';
    exit();
}

$fee = mysqli_fetch_assoc($result);

// Get fee details - handle if student_fee_details table exists
$detail_sql = "SELECT 
               fh.fee_head_name,
               sfd.amount,
               sfd.discount_amount,
               sfd.net_amount
               FROM student_fee_details sfd
               JOIN fee_heads fh ON fh.fee_head_id = sfd.fee_head_id
               WHERE sfd.student_fee_id = '$student_fee_id'";

$detail_result = mysqli_query($conn, $detail_sql);

// If student_fee_details doesn't exist or has no data, try fee_structure_details
if (!$detail_result || mysqli_num_rows($detail_result) == 0) {
    // Try alternative query using fee_structure_details
    $detail_sql = "SELECT 
                   fh.fee_head_name,
                   fsd.amount,
                   0 as discount_amount,
                   fsd.amount as net_amount
                   FROM fee_structure_details fsd
                   JOIN fee_heads fh ON fh.fee_head_id = fsd.fee_head_id
                   JOIN student_fee sf ON sf.fee_structure_id = fsd.fee_structure_id
                   WHERE sf.{$id_column} = '$student_fee_id'";
    $detail_result = mysqli_query($conn, $detail_sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Fee Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-file-invoice text-primary"></i> Student Fee Details</h2>
        <div>
            <a href="generate.php" class="btn btn-success me-2"><i class="fas fa-plus"></i> New Fee</a>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <!-- Student Info -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-user-graduate"></i> Student Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Student Name</th><td><strong><?php echo htmlspecialchars($fee['full_name'] ?? 'N/A'); ?></strong></td></tr>
                        <tr><th>Roll No</th><td><?php echo htmlspecialchars($fee['roll_no'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Father Name</th><td><?php echo htmlspecialchars($fee['father_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Program</th><td><?php echo htmlspecialchars($fee['department_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Semester</th><td><?php echo htmlspecialchars($fee['semester_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Session</th><td><?php echo htmlspecialchars($fee['session_name'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-file-invoice-dollar"></i> Fee Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Generated Date</th><td><?php echo isset($fee['generated_at']) ? date('d-M-Y h:i A', strtotime($fee['generated_at'])) : 'N/A'; ?></td></tr>
                        <tr><th>Due Date</th><td><?php echo isset($fee['due_date']) && $fee['due_date'] ? date('d-M-Y', strtotime($fee['due_date'])) : 'N/A'; ?></td></tr>
                        <tr><th>Total Amount</th><td><strong>PKR <?php echo number_format($fee['total_amount'] ?? 0, 2); ?></strong></td></tr>
                        <tr><th>Paid Amount</th><td><strong>PKR <?php echo number_format($fee['paid_amount'] ?? 0, 2); ?></strong></td></tr>
                        <tr><th>Remaining Amount</th><td><strong>PKR <?php echo number_format($fee['remaining_amount'] ?? 0, 2); ?></strong></td></tr>
                        <tr><th>Status</th><td>
                            <?php 
                            $status = $fee['status'] ?? 'Pending';
                            $badge = 'secondary';
                            if($status == 'Paid' || $status == 'paid') $badge = 'success';
                            elseif($status == 'Partially Paid' || $status == 'partial') $badge = 'warning';
                            elseif($status == 'Overdue' || $status == 'overdue') $badge = 'danger';
                            elseif($status == 'Pending' || $status == 'pending') $badge = 'warning';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($status); ?></span>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Breakdown -->
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <h5><i class="fas fa-list"></i> Fee Breakdown</h5>
        </div>
        <div class="card-body">
            <?php if ($detail_result && mysqli_num_rows($detail_result) > 0): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Fee Head</th>
                        <th class="text-end">Amount (PKR)</th>
                        <th class="text-end">Discount (PKR)</th>
                        <th class="text-end">Net (PKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    while($row = mysqli_fetch_assoc($detail_result)): 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($row['fee_head_name'] ?? 'N/A'); ?></td>
                        <td class="text-end"><?php echo number_format($row['amount'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($row['discount_amount'] ?? 0, 2); ?></td>
                        <td class="text-end"><strong><?php echo number_format($row['net_amount'] ?? $row['amount'] ?? 0, 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="4" class="text-end">Total Amount:</th>
                        <th class="text-end">PKR <?php echo number_format($fee['total_amount'] ?? 0, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No fee breakdown details found for this record.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment History (Optional) -->
    <?php
    // Check if payments table exists and get payment history
    $payments_sql = "SHOW TABLES LIKE 'payments'";
    $payments_check = mysqli_query($conn, $payments_sql);
    if (mysqli_num_rows($payments_check) > 0) {
        $payment_history_sql = "SELECT * FROM payments WHERE student_fee_id = '$student_fee_id' ORDER BY payment_date DESC";
        $payment_history = mysqli_query($conn, $payment_history_sql);
        if (mysqli_num_rows($payment_history) > 0):
    ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h5><i class="fas fa-hand-holding-usd"></i> Payment History</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    while($payment = mysqli_fetch_assoc($payment_history)): 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo date('d-M-Y', strtotime($payment['payment_date'] ?? $payment['created_at'] ?? 'now')); ?></td>
                        <td class="text-end">PKR <?php echo number_format($payment['amount_paid'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_ref'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php 
        endif;
    } 
    ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>