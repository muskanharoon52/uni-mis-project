<?php
// config/db.php

$host = "localhost";
$user = "root";
$password = "";
$database = "university_mis";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ✅ CORRECT BASE_URL for your project
define('BASE_URL', 'http://localhost/university_mis/');

function getConnection() {
    global $conn;
    return $conn;
}
?>