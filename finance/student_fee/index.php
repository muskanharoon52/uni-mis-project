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
        sf.student_fee_id,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
        sf.status,
        sf.generated_at,
        s.full_name,
        s.roll_no,
        sm.semester_name,
        ses.session_name
        FROM student_fee sf
        JOIN students s ON s.student_id = sf.student_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        ORDER BY sf.student_fee_id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-user-graduate text-primary"></i> Student Fee Records</h2>
    <div class="btn-group">
        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-plus"></i> Student Fee
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="generate.php"><i class="fas fa-file-invoice text-success"></i> Generate Fee</a></li>
            <li><a class="dropdown-item" href="index.php"><i class="fas fa-list text-primary"></i> View All Fees</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../dashboard.php"><i class="fas fa-arrow-left text-secondary"></i> Back to Dashboard</a></li>
        </ul>
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
        <i class="fas fa-list"></i> Student Fees List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th class="text-end">Total (PKR)</th>
                        <th class="text-end">Paid (PKR)</th>
                        <th class="text-end">Remaining (PKR)</th>
                        <th>Status</th>
                        <th>Generated</th>
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
                        <td><?php echo htmlspecialchars($row['semester_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['session_name']); ?></td>
                        <td class="text-end"><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($row['paid_amount'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($row['remaining_amount'], 2); ?></td>
                        <td>
                            <?php 
                            $status = $row['status'];
                            $badge = 'secondary';
                            if($status == 'Paid') $badge = 'success';
                            elseif($status == 'Partially Paid') $badge = 'warning';
                            elseif($status == 'Overdue') $badge = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                        </td>
                        <td><?php echo date('d-M-Y', strtotime($row['generated_at'])); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $row['student_fee_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">No student fees found. <a href="generate.php">Generate one now!</a></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>