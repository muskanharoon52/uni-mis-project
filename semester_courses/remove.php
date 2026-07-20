<?php
// semester_courses/remove.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
    // Delete the assignment
    $sql = "DELETE FROM semester_courses WHERE id = ?";
    $result = executeQuery($sql, [$id]);
    
    if($result) {
        $_SESSION['message'] = 'Course removed from semester successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error removing course: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
}

// Redirect back
header('Location: index.php');
exit;
?>