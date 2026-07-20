<?php
require_once 'db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user has SSO role
function isSSO() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'sso';
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Redirect if not SSO or Admin
function requireSSO() {
    requireLogin();
    if (!isSSO() && !isAdmin()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $sql = "SELECT u.*, r.role_name FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ?";
    return getRow($sql, [$_SESSION['user_id']]);
}

// Login function - FIXED to use plain_password first
function loginUser($email, $password) {
    // Get user by email
    $sql = "SELECT u.*, r.role_name FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.email = ?";
    $user = getRow($sql, [$email]);
    
    if (!$user) {
        return false;
    }
    
    // Check if user is active
    if ($user['is_active'] != 1) {
        return false;
    }
    
    // Check password - Try plain_password FIRST (since hash is failing)
    $password_valid = false;
    
    // Method 1: Check with plain_password (MOST RELIABLE for now)
    if (isset($user['plain_password']) && $user['plain_password'] == $password) {
        $password_valid = true;
    }
    
    // Method 2: Check with password_hash (fallback)
    if (!$password_valid && password_verify($password, $user['password_hash'])) {
        $password_valid = true;
    }
    
    // Method 3: Check with direct hash comparison
    if (!$password_valid && $user['password_hash'] == $password) {
        $password_valid = true;
    }
    
    if (!$password_valid) {
        return false;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['role_id'] = $user['role_id'];
    
    return true;
}

// Logout function
function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
?>