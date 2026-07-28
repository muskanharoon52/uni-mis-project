<?php
// modules/sso/logout.php - Logout Handler

session_start();

// Clear all session variables
$_SESSION = array();

// If session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to the root index.php
header("Location: ../../index.php");
exit();
?>