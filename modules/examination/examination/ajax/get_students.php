<?php
require_once '../../../../config/db_connect.php';

$conn = getConnection();

if (isset($_GET['program_id'])) {
    $program_id = $_GET['program_id'];
    $sql = "SELECT s.student_id, u.full_name 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.program_id = ? AND s.status = 'active'
            ORDER BY u.full_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($students);
}
?>