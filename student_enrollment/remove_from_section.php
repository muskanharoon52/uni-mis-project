<?php
// student_enrollment/remove_from_section.php - Remove Student from Section

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (empty($student_id) || $section_id <= 0) {
    header("Location: index.php?error=Invalid parameters");
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if student is enrolled
    $check_query = "SELECT enrollment_id FROM student_enrollments 
                    WHERE student_id = ? AND section_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("si", $student_id, $section_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        throw new Exception("Student is not enrolled in this section");
    }
    $check_stmt->close();

    // Delete from student_enrollments
    $delete_query = "DELETE FROM student_enrollments 
                     WHERE student_id = ? AND section_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("si", $student_id, $section_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Update section enrolled count
    $update_query = "UPDATE sections SET enrolled_count = enrolled_count - 1 
                     WHERE section_id = ? AND enrolled_count > 0";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $section_id);
    $update_stmt->execute();
    $update_stmt->close();

    mysqli_commit($conn);

    header("Location: student_list.php?section=$section_id&success=Student removed from section successfully");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: student_list.php?section=$section_id&error=" . urlencode($e->getMessage()));
    exit;
}
?>