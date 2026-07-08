
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
include '../includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

$error = '';
$success = '';

// Get fee head ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid fee head ID");
    exit();
}

$fee_head_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing data
$sql = "SELECT * FROM fee_heads WHERE fee_head_id = '$fee_head_id' AND deleted_at IS NULL";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Fee head not found");
    exit();
}

$row = mysqli_fetch_assoc($result);

// Update logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_head_name = mysqli_real_escape_string($conn, $_POST['fee_head_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $updated_by = $_SESSION['user_id'] ?? 1;  // Session se user_id lein

    if (empty($fee_head_name)) {
        $error = "Fee Head Name is required!";
    } else {
        // Check for duplicate (excluding current record)
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
                // Refresh data
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fee Head - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4><i class="fas fa-edit"></i> Edit Fee Head</h4>
                </div>
                <div class="card-body">
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

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

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update Fee Head</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>