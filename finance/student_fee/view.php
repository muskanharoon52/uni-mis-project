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
    echo '<script>window.location.href="index.php?error=Invalid fee record ID";</script>';
    exit();
}

$student_fee_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT 
        sf.student_fee_id,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
        sf.status,
        sf.generated_at,
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
        WHERE sf.student_fee_id = '$student_fee_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo '<script>window.location.href="index.php?error=Fee record not found";</script>';
    exit();
}

$fee = mysqli_fetch_assoc($result);

$detail_sql = "SELECT 
               fh.fee_head_name,
               sfd.amount,
               sfd.discount_amount,
               sfd.net_amount
               FROM student_fee_details sfd
               JOIN fee_heads fh ON fh.fee_head_id = sfd.fee_head_id
               WHERE sfd.student_fee_id = '$student_fee_id'";
$detail_result = mysqli_query($conn, $detail_sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-file-invoice text-primary"></i> Student Fee Details</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
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
                    <tr><th>Student Name</th><td><strong><?php echo htmlspecialchars($fee['full_name']); ?></strong></td></tr>
                    <tr><th>Roll No</th><td><?php echo htmlspecialchars($fee['roll_no'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Father Name</th><td><?php echo htmlspecialchars($fee['father_name'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Program</th><td><?php echo htmlspecialchars($fee['department_name']); ?></td></tr>
                    <tr><th>Semester</th><td><?php echo htmlspecialchars($fee['semester_name']); ?></td></tr>
                    <tr><th>Session</th><td><?php echo htmlspecialchars($fee['session_name']); ?></td></tr>
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
                    <tr><th>Generated Date</th><td><?php echo date('d-M-Y h:i A', strtotime($fee['generated_at'])); ?></td></tr>
                    <tr><th>Due Date</th><td><?php echo $fee['due_date'] ? date('d-M-Y', strtotime($fee['due_date'])) : 'N/A'; ?></td></tr>
                    <tr><th>Total Amount</th><td><strong>PKR <?php echo number_format($fee['total_amount'], 2); ?></strong></td></tr>
                    <tr><th>Paid Amount</th><td><strong>PKR <?php echo number_format($fee['paid_amount'], 2); ?></strong></td></tr>
                    <tr><th>Remaining Amount</th><td><strong>PKR <?php echo number_format($fee['remaining_amount'], 2); ?></strong></td></tr>
                    <tr><th>Status</th><td>
                        <?php 
                        $status = $fee['status'];
                        $badge = 'secondary';
                        if($status == 'Paid') $badge = 'success';
                        elseif($status == 'Partially Paid') $badge = 'warning';
                        elseif($status == 'Overdue') $badge = 'danger';
                        ?>
                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
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
                if(mysqli_num_rows($detail_result) > 0): 
                    while($row = mysqli_fetch_assoc($detail_result)): 
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($row['fee_head_name']); ?></td>
                    <td class="text-end"><?php echo number_format($row['amount'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($row['discount_amount'], 2); ?></td>
                    <td class="text-end"><strong><?php echo number_format($row['net_amount'], 2); ?></strong></td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr><td colspan="5" class="text-center">No fee details found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>