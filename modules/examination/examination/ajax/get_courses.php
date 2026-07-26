<?php
require_once '../../config/database.php';

$conn = getConnection();

if (isset($_GET['program_id'])) {
    $program_id = $_GET['program_id'];
    $sql = "SELECT course_id, course_code, course_name FROM courses WHERE program_id = ? ORDER BY course_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($courses);
}
?>