<?php
// 15. Includes/db.php
// Database connection and configuration

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'university_mis');
define('DB_USER', 'root');
define('DB_PASS', '');

// Global connection variable
$conn = null;

/**
 * Get database connection
 */
function getConnection() {
    global $conn;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("❌ Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Close database connection
 */
function closeConnection() {
    global $conn;
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}

/**
 * Execute query with error handling
 */
function executeQuery($sql, $params = [], $types = "") {
    $conn = getConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("❌ Query preparation failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    } else {
        die("❌ Query execution failed: " . $stmt->error);
    }
}

/**
 * Get single record
 */
function getSingleRecord($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get all records
 */
function getAllRecords($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Insert record and return ID
 */
function insertRecord($sql, $params = [], $types = "") {
    $conn = getConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("❌ Query preparation failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    } else {
        die("❌ Insert failed: " . $stmt->error);
    }
}

/**
 * Update record
 */
function updateRecord($sql, $params = [], $types = "") {
    $conn = getConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("❌ Query preparation failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $result && $affected > 0;
}

/**
 * Delete record
 */
function deleteRecord($sql, $params = [], $types = "") {
    return updateRecord($sql, $params, $types);
}

/**
 * Get table count
 */
function getCount($table, $where = "", $params = [], $types = "") {
    $sql = "SELECT COUNT(*) as total FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $result = executeQuery($sql, $params, $types);
    if ($result) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    return 0;
}
?>