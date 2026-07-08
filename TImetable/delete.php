<?php
session_start();
include '../includes/db.php';

$conn = getConnection();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Check if class exists
$check_query = "SELECT id FROM timetable WHERE id = $id";
$check_result = mysqli_query($conn, $check_query);

if (!$check_result || mysqli_num_rows($check_result) == 0) {
    $_SESSION['message'] = "Class not found!";
    $_SESSION['message_type'] = "danger";
    header('Location: index.php');
    exit();
}

// Delete the record
$query = "DELETE FROM timetable WHERE id = $id";

if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = "✅ Class deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting record: " . mysqli_error($conn);
    $_SESSION['message_type'] = "danger";
}

// Redirect back to index
header('Location: index.php');
exit();
?>