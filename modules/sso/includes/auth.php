<?php
// includes/auth.php - Simple Login (Plain Password)

require_once __DIR__ . '/../../../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isSSO() {
    if (!isLoggedIn()) return false;
    $role = strtolower($_SESSION['role_name'] ?? '');
    return $role == 'sso' || $role == 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/sso/login.php');
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
function loginUser($username, $password) {
    $conn = getConnection();
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);
    
    $query = "SELECT u.*, r.role_name FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              WHERE u.username = '$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        return false;
    }
    
    // Check with password_verify (bcrypt) or fallback to plain text
    if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'] ?? 0;
        $_SESSION['role_name'] = $user['role_name'] ?? 'user';
        $_SESSION['full_name'] = $user['full_name'] ?? 'User';
        $_SESSION['username'] = $user['username'] ?? '';
        $_SESSION['login_id'] = $user['login_id'] ?? '';
        return true;
    }
    
    return false;
}

function loginUserById($loginId, $password) {
    $conn = getConnection();
    $loginId = mysqli_real_escape_string($conn, $loginId);
    $password = mysqli_real_escape_string($conn, $password);

    $query = "SELECT u.*, r.role_name FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              WHERE u.login_id = '$loginId' OR u.username = '$loginId'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        return false;
    }

    if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'] ?? 0;
        $_SESSION['role_name'] = $user['role_name'] ?? 'user';
        $_SESSION['full_name'] = $user['full_name'] ?? 'User';
        $_SESSION['username'] = $user['username'] ?? '';
        $_SESSION['login_id'] = $user['login_id'] ?? '';
        return $user;
    }

    return false;
}

function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . 'modules/sso/login.php');
    exit;
}
?>
