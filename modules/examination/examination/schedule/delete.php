<?php
require_once '../../config/database.php';
require_once '../models/ExamSchedule.php';

$model = new ExamSchedule();

if (isset($_GET['id'])) {
    $exam_id = $_GET['id'];
    
    if ($model->delete($exam_id)) {
        $_SESSION['success'] = "Exam schedule deleted successfully!";
    } else {
        if (!isset($_SESSION['error'])) {
            $_SESSION['error'] = "Failed to delete exam schedule!";
        }
    }
} else {
    $_SESSION['error'] = "Invalid request!";
}

header("Location: index.php");
exit();
?>