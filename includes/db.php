<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'university_mis');

// Base URL - CHANGE THIS TO YOUR PATH
define('BASE_URL', 'http://localhost/university_mis/');

// Create connection function
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Global connection object
$conn = getConnection();

// Function to execute queries
function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Function to get single row
function getRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if (!$stmt) return null;
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get multiple rows
function getRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if (!$stmt) return [];
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// Function to insert/update/delete
function executeStatement($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if (!$stmt) return false;
    return $stmt->affected_rows;
}

// Function to get last insert ID
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// Function to display messages
function showMessage($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-danger';
    return "<div class='alert $class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>