<?php
$pageTitle = 'Edit Fee Head';
include_once __DIR__ . '/../includes/header.php';

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
                          updated_at = NOW()
                          WHERE fee_head_id = '$fee_head_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = "Fee head updated successfully!";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                header("refresh:1;url=index.php?msg=Fee head updated successfully!");
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Heads</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card" style="max-width:560px;">
    <div class="card-header">
        <h3>Edit Fee Head</h3>
    </div>
    <form method="post">
        <div style="padding:22px;">
            <div class="field">
                <label>Fee Head Name <span style="color:var(--danger);">*</span></label>
                <input type="text" name="fee_head_name" required value="<?= htmlspecialchars($row['fee_head_name']) ?>">
            </div>
            <div class="field">
                <label>Description</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($row['description']) ?></textarea>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="Active" <?= $row['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $row['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button class="btn btn-primary" type="submit">Update Fee Head</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
