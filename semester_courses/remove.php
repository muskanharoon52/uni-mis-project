<?php
// semester_courses/remove.php - Remove course from semester

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

// Get parameters
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($assignment_id == 0) {
    header('Location: index.php?error=Invalid assignment ID');
    exit;
}

// ✅ FIRST CHECK: What is the primary key column name?
// Try different column names
$column_check = $conn->query("SHOW COLUMNS FROM semester_courses");
$columns = [];
while ($col = $column_check->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Find the primary key column
$pk_column = 'id'; // default
if (in_array('semester_course_id', $columns)) {
    $pk_column = 'semester_course_id';
} elseif (in_array('assignment_id', $columns)) {
    $pk_column = 'assignment_id';
} elseif (in_array('sc_id', $columns)) {
    $pk_column = 'sc_id';
}

// ✅ DELETE using the correct column name
$delete_query = "DELETE FROM semester_courses WHERE $pk_column = ?";
$delete_stmt = $conn->prepare($delete_query);

if ($delete_stmt === false) {
    // If prepare fails, try direct query
    $delete_query_direct = "DELETE FROM semester_courses WHERE $pk_column = $assignment_id";
    if (mysqli_query($conn, $delete_query_direct)) {
        header('Location: view.php?course_id=' . $course_id . '&success=Course removed from semester successfully');
        exit;
    } else {
        header('Location: view.php?course_id=' . $course_id . '&error=Failed to remove: ' . mysqli_error($conn));
        exit;
    }
}

$delete_stmt->bind_param("i", $assignment_id);

if ($delete_stmt->execute()) {
    header('Location: view.php?course_id=' . $course_id . '&success=Course removed from semester successfully');
    exit;
} else {
    header('Location: view.php?course_id=' . $course_id . '&error=Failed to remove: ' . $delete_stmt->error);
    exit;
}

$delete_stmt->close();
?>