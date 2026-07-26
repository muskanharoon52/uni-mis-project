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
        fs.fee_structure_id,
        fs.total_amount,
        fs.status,
        d.department_name,
        s.session_name,
        sm.semester_name,
        fsd.fee_head_id,
        fsd.amount,
        fh.fee_head_name
        FROM fee_structures fs
        JOIN departments d ON d.department_id = fs.program_id
        JOIN sessions s ON s.session_id = fs.session_id
        JOIN semesters sm ON sm.semester_id = fs.semester_id
        JOIN fee_structure_details fsd ON fsd.fee_structure_id = fs.fee_structure_id
        JOIN fee_heads fh ON fh.fee_head_id = fsd.fee_head_id
        WHERE fs.status = 'Active'
        ORDER BY fs.fee_structure_id DESC, fsd.fee_head_id";
$result = mysqli_query($conn, $sql);

$structures = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['fee_structure_id'];
    if (!isset($structures[$id])) {
        $structures[$id] = [
            'program' => $row['department_name'],
            'session' => $row['session_name'],
            'semester' => $row['semester_name'],
            'total' => $row['total_amount'],
            'status' => $row['status'],
            'heads' => []
        ];
    }
    $structures[$id]['heads'][] = [
        'name' => $row['fee_head_name'],
        'amount' => $row['amount']
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-eye text-primary"></i> Fee Structures (Read-Only)</h2>
    <span class="badge bg-info text-white"><i class="fas fa-info-circle"></i> Read-Only Mode</span>
</div>

<?php if(empty($structures)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No fee structures found. SSO module will create them.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach($structures as $id => $data): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-university"></i> <?php echo htmlspecialchars($data['program']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>Session:</strong> <?php echo htmlspecialchars($data['session']); ?><br>
                            <strong>Semester:</strong> <?php echo htmlspecialchars($data['semester']); ?><br>
                            <strong>Status:</strong> 
                            <span class="badge bg-success"><?php echo $data['status']; ?></span>
                        </p>
                        <hr>
                        <h6><i class="fas fa-list"></i> Fee Heads</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Fee Head</th>
                                    <th class="text-end">Amount (PKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['heads'] as $head): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($head['name']); ?></td>
                                        <td class="text-end"><?php echo number_format($head['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th><strong>Total</strong></th>
                                    <th class="text-end"><?php echo number_format($data['total'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer text-muted">
                        <small><i class="fas fa-lock"></i> Read-only. To modify, contact SSO staff.</small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="mt-3">
    <a href="../fee_heads/index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Fee Heads
    </a>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>