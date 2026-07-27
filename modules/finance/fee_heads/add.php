<?php
$pageTitle = 'Add Fee Head';
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_head_name = mysqli_real_escape_string($conn, $_POST['fee_head_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    if (empty($fee_head_name)) {
        $error = "Fee Head Name is required.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM fee_heads WHERE fee_head_name = '$fee_head_name' AND deleted_at IS NULL");
        if (mysqli_num_rows($check) > 0) {
            $error = "Fee head '$fee_head_name' already exists.";
        } else {
            $sql = "INSERT INTO fee_heads (fee_head_name, description, status) VALUES ('$fee_head_name', '$description', '$status')";
            if (mysqli_query($conn, $sql)) {
                $success = "Fee head added successfully.";
                header("refresh:1;url=index.php");
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
        <h3>Add New Fee Head</h3>
    </div>
    <form method="post">
        <div style="padding:22px;">
            <div class="field">
                <label>Fee Head Name <span style="color:var(--danger);">*</span></label>
                <input type="text" name="fee_head_name" required placeholder="e.g. Tuition Fee, Library Fee">
                <p class="muted" style="margin-top:4px;">Enter a unique name for this fee head.</p>
            </div>
            <div class="field">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Optional description..."></textarea>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button class="btn btn-primary" type="submit">Save Fee Head</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
