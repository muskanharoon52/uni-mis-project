<?php
// 15. Includes/auth.php
// Authentication and authorization functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once 'db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the given roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
      header("Location: /MIS/Authentication/login.php");
        exit;
    }
}

/**
 * Require specific role - redirect if not authorized
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
       header("Location: /MIS/Dashboard.php?error=unauthorized");
        exit;
    }
}

/**
 * Require any of the given roles
 */
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header("Location: /University%20MIS/2.%20Dashboard/index.php?error=unauthorized");
        exit;
    }
}

/**
 * Login user
 */
function loginUser($user_id, $username, $role, $full_name) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');
    
    // Update last login
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    return true;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('SuperAdmin') || hasRole('Admin');
}

/**
 * Check if user is SSO Staff
 */
function isSSOStaff() {
    return hasRole('SSOStaff') || isAdmin();
}

/**
 * Check if user is Teacher
 */
function isTeacher() {
    return hasRole('Teacher') || isAdmin();
}

/**
 * Sanitize input for security
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           isset($token) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user permissions
 */
function getUserPermissions() {
    $user = getCurrentUser();
    if (!$user) return [];
    
    // Define permissions based on role
    $permissions = [
        'SuperAdmin' => ['all'],
        'Admin' => ['all'],
        'SSOStaff' => [
            'manage_courses', 'manage_teachers', 'manage_timetable',
            'view_students', 'manage_applications', 'view_reports'
        ],
        'Teacher' => [
            'mark_attendance', 'view_timetable', 'view_students'
        ],
        'Student' => [
            'view_timetable', 'view_attendance', 'submit_applications'
        ]
    ];
    
    return isset($permissions[$user['role']]) ? $permissions[$user['role']] : [];
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    $permissions = getUserPermissions();
    return in_array('all', $permissions) || in_array($permission, $permissions);
}
?>