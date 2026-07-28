<?php
require_once '../models/ExamResult.php';

class ExamResultController {
    private $model;
    
    public function __construct() {
        $this->model = new ExamResult();
    }
    
    public function index() {
        $results = $this->model->getAll();
        include '../results/index.php';
    }
    
    public function create() {
        $conn = getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if result already exists
            $check = $conn->prepare("SELECT result_id FROM exam_results WHERE student_id = ? AND exam_id = ?");
            $check->bind_param("si", $_POST['student_id'], $_POST['exam_id']);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $_SESSION['error'] = "Result already exists for this student and exam!";
            } else {
                $data = [
                    'student_id' => $_POST['student_id'],
                    'exam_id' => $_POST['exam_id'],
                    'marks_obtained' => $_POST['marks_obtained'],
                    'total_marks' => $_POST['total_marks'],
                    'grade' => $_POST['grade']
                ];
                
                if ($this->model->add($data)) {
                    $_SESSION['success'] = "Result added successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to add result!";
                }
            }
        }
        
        // Get students and exams for dropdowns
        $students = $conn->query("
            SELECT s.student_id, u.full_name 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.status = 'active'
            ORDER BY u.full_name
        ");
        $exams = $conn->query("
            SELECT es.exam_id, c.course_code, c.course_name, es.exam_type, es.date
            FROM exam_schedules es
            JOIN courses c ON es.course_id = c.course_id
            WHERE es.date <= CURDATE()
            ORDER BY es.date DESC
        ");
        include '../results/add.php';
    }
    
    public function edit($result_id) {
        $conn = getConnection();
        $result = $this->model->getById($result_id);
        
        if (!$result) {
            $_SESSION['error'] = "Result not found!";
            header("Location: index.php");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'marks_obtained' => $_POST['marks_obtained'],
                'total_marks' => $_POST['total_marks'],
                'grade' => $_POST['grade']
            ];
            
            if ($this->model->update($result_id, $data)) {
                $_SESSION['success'] = "Result updated successfully!";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to update result!";
            }
        }
        
        $students = $conn->query("
            SELECT s.student_id, u.full_name 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.status = 'active'
            ORDER BY u.full_name
        ");
        $exams = $conn->query("
            SELECT es.exam_id, c.course_code, c.course_name, es.exam_type, es.date
            FROM exam_schedules es
            JOIN courses c ON es.course_id = c.course_id
            ORDER BY es.date DESC
        ");
        include '../results/edit.php';
    }
    
    public function delete($result_id) {
        if ($this->model->delete($result_id)) {
            $_SESSION['success'] = "Result deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete result!";
        }
        header("Location: index.php");
        exit();
    }
    
    public function view($result_id) {
        $result = $this->model->getById($result_id);
        if (!$result) {
            $_SESSION['error'] = "Result not found!";
            header("Location: index.php");
            exit();
        }
        include '../results/view.php';
    }
    
    public function publish() {
        $conn = getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish'])) {
            $result_ids = $_POST['result_ids'] ?? [];
            
            if (!empty($result_ids)) {
                $ids = implode(',', array_map('intval', $result_ids));
                $sql = "UPDATE exam_results SET status = 'published' WHERE result_id IN ($ids)";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = count($result_ids) . " results published successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to publish results!";
                }
            } else {
                $_SESSION['error'] = "Please select at least one result to publish!";
            }
        }
        
        include '../results/publish.php';
    }
}

// Handle routing
$controller = new ExamResultController();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            $controller->create();
            break;
        case 'edit':
            if (isset($_GET['id'])) {
                $controller->edit($_GET['id']);
            } else {
                header("Location: index.php");
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                $controller->delete($_GET['id']);
            } else {
                header("Location: index.php");
            }
            break;
        case 'view':
            if (isset($_GET['id'])) {
                $controller->view($_GET['id']);
            } else {
                header("Location: index.php");
            }
            break;
        case 'publish':
            $controller->publish();
            break;
        default:
            $controller->index();
    }
} else {
    $controller->index();
}
?>