<?php
// modules/finance/includes/auth.php
// Finance module auth - uses SSO session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';

function fin_isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function fin_requireLogin() {
    if (!fin_isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/sso/login.php');
        exit();
    }
}

function fin_isFinanceOrAdmin() {
    return isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 1);
}

function fin_requireFinance() {
    fin_requireLogin();
    if (!fin_isFinanceOrAdmin()) {
        header('Location: ' . BASE_URL . 'modules/sso/login.php?error=Access denied');
        exit();
    }
}

function isLoggedIn() {
    return fin_isLoggedIn();
}

function requireLogin() {
    fin_requireLogin();
}

function fin_getCurrentUser() {
    if (!fin_isLoggedIn()) return null;
    global $conn;
    $user_id = (int)$_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = $user_id");
    return mysqli_fetch_assoc($result);
}
?>
