<?php
include '../../config/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid fee head ID");
    exit();
}

$fee_head_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if fee head exists
$check_sql = "SELECT * FROM fee_heads WHERE fee_head_id = '$fee_head_id' AND deleted_at IS NULL";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: index.php?error=Fee head not found");
    exit();
}

// Soft delete (set deleted_at timestamp)
$sql = "UPDATE fee_heads SET deleted_at = NOW() WHERE fee_head_id = '$fee_head_id'";

if (mysqli_query($conn, $sql)) {
    // Activity log will be automatically inserted by the database trigger!
    header("Location: index.php?msg=Fee head deleted successfully!");
} else {
    header("Location: index.php?error=Error deleting fee head: " . mysqli_error($conn));
}

exit();
?>