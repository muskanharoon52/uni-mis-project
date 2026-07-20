<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'university_mis');

// Create connection
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");

        return $conn;
    } catch (Exception $e) {
        die("Database Connection Error: " . $e->getMessage());
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function hasRole($role_name) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role_name'] === $role_name;
}

// Redirect to login if not logged in, except on login.php and logout.php
$current_script = basename($_SERVER['SCRIPT_NAME']);
if (!isLoggedIn() && $current_script !== 'login.php' && $current_script !== 'logout.php') {
    // If it's an AJAX request, return unauthorized status instead of redirecting
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('HTTP/1.1 401 Unauthorized');
        exit("Unauthorized");
    }
    header("Location: /uni-mis-project/login.php");
    exit();
}
// Helper function to get grade color class
function getGradeColor($grade) {
    $colors = [
        'A' => 'bg-success',
        'B' => 'bg-primary',
        'C' => 'bg-warning',
        'D' => 'bg-info',
        'F' => 'bg-danger'
    ];
    return $colors[$grade] ?? 'bg-secondary';
}

// Helper function to get exam type badge class
function getExamTypeBadgeClass($type) {
    $classes = [
        'final' => 'badge-exam-final',
        'mid' => 'badge-exam-mid',
        'quiz' => 'badge-exam-quiz',
        'lab' => 'badge-exam-lab'
    ];
    return $classes[$type] ?? 'bg-secondary';
}
?>