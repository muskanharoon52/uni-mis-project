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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_head_name = mysqli_real_escape_string($conn, $_POST['fee_head_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $created_by = $_SESSION['user_id'] ?? 1;

    if (empty($fee_head_name)) {
        $error = "Fee Head Name is required!";
    } else {
        $check_sql = "SELECT * FROM fee_heads WHERE fee_head_name = '$fee_head_name' AND deleted_at IS NULL";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Fee head '$fee_head_name' already exists!";
        } else {
            $sql = "INSERT INTO fee_heads (fee_head_name, description, status, created_by) 
                    VALUES ('$fee_head_name', '$description', '$status', '$created_by')";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Fee head added successfully!";
                header("refresh:2;url=index.php");
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-plus-circle text-success"></i> Add New Fee Head</h2>
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
                       placeholder="e.g. Tuition Fee, Admission Fee, Library Fee" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          placeholder="Optional: Describe this fee head"></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Fee Head</button>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
        </form>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>