<?php
// config/db_connect.php

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "university_mis";

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// ✅ CORRECT BASE_URL for your project - Check if already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/uni-mis-project/');
}

// Check if function already exists before declaring
if (!function_exists('getConnection')) {
    function getConnection() {
        global $conn;
        if (!$conn) {
            die("Database connection lost. Please check your configuration.");
        }
        return $conn;
    }
}

// Optional: Add a function to check if connection is active
if (!function_exists('checkConnection')) {
    function checkConnection() {
        global $conn;
        if (!$conn) {
            die("Database connection lost. Please check your configuration.");
        }
        return $conn;
    }
}

// Alternative: Return connection directly if needed
if (!function_exists('db_connect')) {
    function db_connect() {
        global $conn;
        return $conn;
    }
}

// Helper function to check if table exists
if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}
?>