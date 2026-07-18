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

$sql = "SELECT * FROM fee_heads WHERE deleted_at IS NULL ORDER BY fee_head_id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-money-bill-wave text-primary"></i> Fee Heads</h2>
    <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Fee Head</a>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list"></i> Fee Heads List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Fee Head Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
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
                        <td><strong><?php echo htmlspecialchars($row['fee_head_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if($row['status'] == 'Active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['fee_head_id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete.php?id=<?php echo $row['fee_head_id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this fee head?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No fee heads found. <a href="add.php">Add one now!</a></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>