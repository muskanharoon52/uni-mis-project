<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "university_mis";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Uncomment below line to test connection
// echo "Database connected successfully!";
?>