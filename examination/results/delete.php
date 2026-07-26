<?php
require_once '../../../../config/db_connect.php';
require_once '../models/ExamResult.php';

$model = new ExamResult();

if (isset($_GET['id'])) {
    if ($model->delete($_GET['id'])) {
        $_SESSION['success'] = "Result deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete result!";
    }
} else {
    $_SESSION['error'] = "Invalid request!";
}

header("Location: index.php");
exit();
?>