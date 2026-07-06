<?php
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

$sql = "SELECT * FROM fee_heads WHERE deleted_at IS NULL ORDER BY fee_head_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Heads - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-money-bill-wave text-primary"></i> Finance Module - Fee Heads</h2>
            <p class="text-muted">Manage all fee categories like Admission Fee, Tuition Fee, Library Fee, etc.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Fee Head</a>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
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

    <div class="mt-3 text-center text-muted">
        <small>Finance Module - University MIS &copy; <?php echo date('Y'); ?></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>