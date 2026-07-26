<?php
// student_enrollment/remove_from_section.php - Remove Single Student from Section

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();

// Get parameters
$student_id = isset($_GET['student_id']) ? mysqli_real_escape_string($conn, $_GET['student_id']) : '';
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (empty($student_id) || $section_id == 0) {
    header('Location: student_list.php?section=' . $section_id . '&error=Invalid request');
    exit;
}

// ✅ Remove student from section - set section_id to NULL
$update_query = "UPDATE students SET section_id = NULL, section = NULL WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $update_query);

if ($result) {
    // ✅ Update enrolled count in sections table
    $update_count = "UPDATE sections SET enrolled_count = enrolled_count - 1 WHERE section_id = $section_id";
    mysqli_query($conn, $update_count);
    
    header('Location: student_list.php?section=' . $section_id . '&success=Student removed from section successfully');
    exit;
} else {
    $error = mysqli_error($conn);
    header('Location: student_list.php?section=' . $section_id . '&error=Failed to remove: ' . urlencode($error));
    exit;
}
?>