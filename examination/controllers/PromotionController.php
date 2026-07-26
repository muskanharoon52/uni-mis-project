<?php
require_once '../models/StudentPromotion.php';

class PromotionController {
    private $model;
    
    public function __construct() {
        $this->model = new StudentPromotion();
    }
    
    public function index() {
        $conn = getConnection();
        $programs = $conn->query("SELECT program_id, program_name FROM programs ORDER BY program_name");
        include '../promote/index.php';
    }
    
    public function promote() {
        $conn = getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_students'])) {
            $student_ids = $_POST['student_ids'] ?? [];
            $next_semester = $_POST['next_semester'] ?? 1;
            
            if (!empty($student_ids)) {
                if ($this->model->promote($student_ids, $next_semester)) {
                    $_SESSION['success'] = count($student_ids) . " students promoted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to promote students!";
                }
            } else {
                $_SESSION['error'] = "Please select at least one student to promote!";
            }
            header("Location: index.php");
            exit();
        }
        
        // Get programs for dropdown
        $programs = $conn->query("SELECT program_id, program_name FROM programs ORDER BY program_name");
        
        $students = [];
        $selected_program = null;
        $selected_semester = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_id']) && isset($_POST['semester'])) {
            $selected_program = $_POST['program_id'];
            $selected_semester = $_POST['semester'];
            $students = $this->model->getEligibleStudents($selected_program, $selected_semester);
        }
        
        include '../promote/promote.php';
    }
}

// Handle routing
$controller = new PromotionController();

if (isset($_GET['action']) && $_GET['action'] === 'promote') {
    $controller->promote();
} else {
    $controller->index();
}
?>