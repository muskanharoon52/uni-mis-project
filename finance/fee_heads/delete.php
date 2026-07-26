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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid fee head ID");
    exit();
}

$fee_head_id = mysqli_real_escape_string($conn, $_GET['id']);
$deleted_by = $_SESSION['user_id'] ?? 1;

$check_sql = "SELECT * FROM fee_heads WHERE fee_head_id = '$fee_head_id' AND deleted_at IS NULL";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: index.php?error=Fee head not found");
    exit();
}

$sql = "UPDATE fee_heads SET deleted_at = NOW(), deleted_by = '$deleted_by' WHERE fee_head_id = '$fee_head_id'";

if (mysqli_query($conn, $sql)) {
    header("Location: index.php?msg=Fee head deleted successfully!");
} else {
    header("Location: index.php?error=Error deleting fee head: " . mysqli_error($conn));
}
exit();
?>