<?php
include '../../config/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_head_name = mysqli_real_escape_string($conn, $_POST['fee_head_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validation
    if (empty($fee_head_name)) {
        $error = "Fee Head Name is required!";
    } else {
        // Check if fee head already exists
        $check_sql = "SELECT * FROM fee_heads WHERE fee_head_name = '$fee_head_name' AND deleted_at IS NULL";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Fee head '$fee_head_name' already exists!";
        } else {
            // Insert into database
            $sql = "INSERT INTO fee_heads (fee_head_name, description, status) 
                    VALUES ('$fee_head_name', '$description', '$status')";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Fee head added successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
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
    <title>Add Fee Head - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-plus-circle"></i> Add New Fee Head</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Error Message -->
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Success Message -->
                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
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

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Fee Head</button>
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