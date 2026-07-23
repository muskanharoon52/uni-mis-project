<?php
// includes/auth.php - Simple Login (Plain Password)

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isSSO() {
    if (!isLoggedIn()) return false;
    $role = $_SESSION['role_name'] ?? '';
    return $role == 'sso' || $role == 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireSSO() {
    requireLogin();
    if (!isSSO()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getConnection();
    $user_id = (int)$_SESSION['user_id'];
    $query = "SELECT u.*, r.role_name FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              WHERE u.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// ============================================
// SIMPLE LOGIN - Plain Password Check
// ============================================
function loginUser($email, $password) {
    $conn = getConnection();
    $email = mysqli_real_escape_string($conn, $email);
    $password = mysqli_real_escape_string($conn, $password);
    
    $query = "SELECT u.*, r.role_name FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              WHERE u.email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        return false;
    }
    
    // ✅ DIRECT COMPARE - Plain password
    if ($password === $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_name'] = $user['role_name'] ?? 'user';
        $_SESSION['full_name'] = $user['full_name'] ?? 'User';
        return true;
    }
    
    return false;
}

function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>