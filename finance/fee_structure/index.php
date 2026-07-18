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

$sql = "SELECT fs.*, 
        d.department_name, 
        s.session_name, 
        sm.semester_name 
        FROM fee_structures fs
        JOIN departments d ON d.department_id = fs.program_id
        JOIN sessions s ON s.session_id = fs.session_id
        JOIN semesters sm ON sm.semester_id = fs.semester_id
        WHERE fs.status = 'Active'
        ORDER BY fs.fee_structure_id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-layer-group text-primary"></i> Fee Structures</h2>
    <span class="badge bg-secondary"><i class="fas fa-lock"></i> Read-Only</span>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-header bg-secondary text-white">
        <i class="fas fa-list"></i> Fee Structures List (Pre-defined by SSO)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Program</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Total Amount</th>
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
                        <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['session_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester_name']); ?></td>
                        <td><strong>PKR <?php echo number_format($row['total_amount'], 2); ?></strong></td>
                        <td>
                            <?php if($row['status'] == 'Active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No fee structures found. SSO module will create them.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">
        <i class="fas fa-info-circle"></i> Note: Fee structures are created by SSO module. Finance module has read-only access.
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>