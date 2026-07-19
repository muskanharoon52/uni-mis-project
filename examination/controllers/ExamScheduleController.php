<?php
require_once '../models/ExamSchedule.php';

class ExamScheduleController {
    private $model;
    private $conn;
    
    public function __construct() {
        $this->model = new ExamSchedule();
        $this->conn = getConnection();
    }
    
    /**
     * Display all schedules
     */
    public function index() {
        $schedules = $this->model->getAll();
        include '../schedule/index.php';
    }
    
    /**
     * Show add form and handle form submission
     */
    public function create() {
        // Get courses for dropdown
        $courses = $this->conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_name");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'course_id' => $_POST['course_id'] ?? null,
                'exam_type' => $_POST['exam_type'] ?? null,
                'date' => $_POST['date'] ?? null,
                'start_time' => $_POST['start_time'] ?? null,
                'end_time' => $_POST['end_time'] ?? null,
                'room' => $_POST['room'] ?? null
            ];
            
            // Validate
            $errors = $this->validateScheduleData($data);
            
            if (empty($errors)) {
                if ($this->model->add($data)) {
                    $_SESSION['success'] = "Exam schedule added successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to add exam schedule. Please check for conflicts.";
                }
            } else {
                $_SESSION['error'] = implode("<br>", $errors);
            }
        }
        
        include '../schedule/add.php';
    }
    
    /**
     * Show edit form and handle form submission
     */
    public function edit($exam_id) {
        // Get schedule data
        $schedule = $this->model->getById($exam_id);
        
        if (!$schedule) {
            $_SESSION['error'] = "Schedule not found!";
            header("Location: index.php");
            exit();
        }
        
        // Get courses for dropdown
        $courses = $this->conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_name");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'course_id' => $_POST['course_id'] ?? null,
                'exam_type' => $_POST['exam_type'] ?? null,
                'date' => $_POST['date'] ?? null,
                'start_time' => $_POST['start_time'] ?? null,
                'end_time' => $_POST['end_time'] ?? null,
                'room' => $_POST['room'] ?? null
            ];
            
            // Validate
            $errors = $this->validateScheduleData($data);
            
            if (empty($errors)) {
                if ($this->model->update($exam_id, $data)) {
                    $_SESSION['success'] = "Exam schedule updated successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update exam schedule. Please check for conflicts.";
                }
            } else {
                $_SESSION['error'] = implode("<br>", $errors);
            }
        }
        
        include '../schedule/edit.php';
    }
    
    /**
     * Delete schedule
     */
    public function delete($exam_id) {
        if ($this->model->delete($exam_id)) {
            $_SESSION['success'] = "Exam schedule deleted successfully!";
        } else {
            if (!isset($_SESSION['error'])) {
                $_SESSION['error'] = "Failed to delete exam schedule!";
            }
        }
        header("Location: index.php");
        exit();
    }
    
    /**
     * View schedule details
     */
    public function view($exam_id) {
        $schedule = $this->model->getById($exam_id);
        
        if (!$schedule) {
            $_SESSION['error'] = "Schedule not found!";
            header("Location: index.php");
            exit();
        }
        
        include '../schedule/view.php';
    }
    
    /**
     * Validate schedule data
     */
    private function validateScheduleData($data) {
        $errors = [];
        
        if (empty($data['course_id'])) {
            $errors[] = "Course is required.";
        }
        
        if (empty($data['exam_type'])) {
            $errors[] = "Exam type is required.";
        }
        
        if (empty($data['date'])) {
            $errors[] = "Date is required.";
        } elseif (strtotime($data['date']) < strtotime(date('Y-m-d'))) {
            $errors[] = "Date cannot be in the past.";
        }
        
        if (empty($data['start_time'])) {
            $errors[] = "Start time is required.";
        }
        
        if (empty($data['end_time'])) {
            $errors[] = "End time is required.";
        } elseif (!empty($data['start_time']) && $data['start_time'] >= $data['end_time']) {
            $errors[] = "End time must be after start time.";
        }
        
        if (empty($data['room'])) {
            $errors[] = "Room is required.";
        }
        
        return $errors;
    }
}

// Handle routing
$controller = new ExamScheduleController();

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['exam_id']) ? $_GET['exam_id'] : null);
    
    switch ($action) {
        case 'add':
            $controller->create();
            break;
        case 'edit':
            if ($id) {
                $controller->edit($id);
            } else {
                $_SESSION['error'] = "Invalid schedule ID!";
                header("Location: index.php");
                exit();
            }
            break;
        case 'delete':
            if ($id) {
                $controller->delete($id);
            } else {
                $_SESSION['error'] = "Invalid schedule ID!";
                header("Location: index.php");
                exit();
            }
            break;
        case 'view':
            if ($id) {
                $controller->view($id);
            } else {
                $_SESSION['error'] = "Invalid schedule ID!";
                header("Location: index.php");
                exit();
            }
            break;
        default:
            $controller->index();
    }
} else {
    $controller->index();
}
?>