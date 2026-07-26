<?php
// Courses/delete.php - Delete Course (COMPLETE FIX)

require_once __DIR__ . '../config/db_connect.php';
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

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id == 0) {
    header('Location: index.php?error=Invalid course ID');
    exit;
}

// ✅ DISABLE FOREIGN KEY CHECKS
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Delete from ALL related tables
$related_tables = [
    'assignments',
    'semester_courses',
    'section_courses', 
    'teacher_courses',
    'timetable',
    'attendance',
    'student_courses',
    'course_fees',
    'fee_per_course',
    'student_course_fees'
];

foreach ($related_tables as $table) {
    // Check if table exists
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        // Check if course_id column exists
        $col_check = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE 'course_id'");
        if (mysqli_num_rows($col_check) > 0) {
            mysqli_query($conn, "DELETE FROM $table WHERE course_id = $course_id");
        }
    }
}

// ✅ Delete the course
$result = mysqli_query($conn, "DELETE FROM courses WHERE course_id = $course_id");

// ✅ ENABLE FOREIGN KEY CHECKS BACK
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

if ($result && mysqli_affected_rows($conn) > 0) {
    header('Location: index.php?success=Course deleted successfully');
    exit;
} else {
    $error = mysqli_error($conn);
    header('Location: index.php?error=Failed to delete: ' . urlencode($error));
    exit;
}
?>