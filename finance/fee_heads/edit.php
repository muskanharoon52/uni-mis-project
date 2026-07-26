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

$error = '';
$success = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid fee head ID");
    exit();
}

$fee_head_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM fee_heads WHERE fee_head_id = '$fee_head_id' AND deleted_at IS NULL";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Fee head not found");
    exit();
}

$row = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_head_name = mysqli_real_escape_string($conn, $_POST['fee_head_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $updated_by = $_SESSION['user_id'] ?? 1;

    if (empty($fee_head_name)) {
        $error = "Fee Head Name is required!";
    } else {
        $check_sql = "SELECT * FROM fee_heads WHERE fee_head_name = '$fee_head_name' 
                      AND fee_head_id != '$fee_head_id' AND deleted_at IS NULL";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Fee head '$fee_head_name' already exists!";
        } else {
            $update_sql = "UPDATE fee_heads SET 
                          fee_head_name = '$fee_head_name',
                          description = '$description',
                          status = '$status',
                          updated_by = '$updated_by',
                          updated_at = NOW()
                          WHERE fee_head_id = '$fee_head_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = "Fee head updated successfully!";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                header("refresh:2;url=index.php?msg=Fee head updated successfully!");
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-edit text-warning"></i> Edit Fee Head</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
</div>

<?php if(!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if(!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="fee_head_name" class="form-label">Fee Head Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="fee_head_name" name="fee_head_name" 
                       value="<?php echo htmlspecialchars($row['fee_head_name']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="Active" <?php echo ($row['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($row['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update Fee Head</button>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
        </form>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>