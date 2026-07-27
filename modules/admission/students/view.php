<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'View Student';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name 
        FROM admission_students s 
        LEFT JOIN departments d ON s.program_id = d.department_id 
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        setFlash('error', 'Student not found');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    setFlash('error', 'Error loading student: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Rest of your view code...
?>