<?php
require_once '../../config/database.php';

class ExamResult {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all results with student and course details
    public function getAll() {
        $sql = "SELECT er.*, 
                       s.student_id, u.full_name as student_name,
                       c.course_name, c.course_code,
                       es.exam_type, es.date as exam_date
                FROM exam_results er
                JOIN students s ON er.student_id = s.student_id
                JOIN users u ON s.user_id = u.user_id
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                JOIN courses c ON es.course_id = c.course_id
                ORDER BY er.result_id DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get result by ID
    public function getById($result_id) {
        $sql = "SELECT er.*, 
                       s.student_id, u.full_name as student_name,
                       c.course_name, c.course_code,
                       es.exam_type, es.date as exam_date
                FROM exam_results er
                JOIN students s ON er.student_id = s.student_id
                JOIN users u ON s.user_id = u.user_id
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                JOIN courses c ON es.course_id = c.course_id
                WHERE er.result_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $result_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Add new result
    public function add($data) {
        // Check if result already exists
        $check = $this->conn->prepare("SELECT result_id FROM exam_results WHERE student_id = ? AND exam_id = ?");
        $check->bind_param("si", $data['student_id'], $data['exam_id']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return false;
        }
        
        $sql = "INSERT INTO exam_results (student_id, exam_id, marks_obtained, total_marks, grade, status) 
                VALUES (?, ?, ?, ?, ?, 'draft')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sidds", 
            $data['student_id'], 
            $data['exam_id'], 
            $data['marks_obtained'], 
            $data['total_marks'], 
            $data['grade']
        );
        return $stmt->execute();
    }
    
    // Update result
    public function update($result_id, $data) {
        $sql = "UPDATE exam_results 
                SET marks_obtained = ?, total_marks = ?, grade = ? 
                WHERE result_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ddsi", 
            $data['marks_obtained'], 
            $data['total_marks'], 
            $data['grade'],
            $result_id
        );
        return $stmt->execute();
    }
    
    // Delete result
    public function delete($result_id) {
        $sql = "DELETE FROM exam_results WHERE result_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $result_id);
        return $stmt->execute();
    }
    
    // Get results by student
    public function getByStudent($student_id) {
        $sql = "SELECT er.*, 
                       c.course_name, c.course_code,
                       es.exam_type, es.date as exam_date
                FROM exam_results er
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                JOIN courses c ON es.course_id = c.course_id
                WHERE er.student_id = ?
                ORDER BY es.date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get results by exam
    public function getByExam($exam_id) {
        $sql = "SELECT er.*, 
                       s.student_id, u.full_name as student_name
                FROM exam_results er
                JOIN students s ON er.student_id = s.student_id
                JOIN users u ON s.user_id = u.user_id
                WHERE er.exam_id = ?
                ORDER BY u.full_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get results by grade
    public function getByGrade($grade) {
        $sql = "SELECT er.*, 
                       s.student_id, u.full_name as student_name,
                       c.course_name, c.course_code
                FROM exam_results er
                JOIN students s ON er.student_id = s.student_id
                JOIN users u ON s.user_id = u.user_id
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                JOIN courses c ON es.course_id = c.course_id
                WHERE er.grade = ?
                ORDER BY er.result_id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $grade);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Calculate grade based on marks
    public function calculateGrade($marks, $total) {
        $percentage = ($marks / $total) * 100;
        
        if ($percentage >= 90) return 'A';
        elseif ($percentage >= 80) return 'B';
        elseif ($percentage >= 70) return 'C';
        elseif ($percentage >= 60) return 'D';
        else return 'F';
    }
    
    // Get student GPA
    public function getGPA($student_id) {
        $sql = "SELECT 
                    AVG(CASE 
                        WHEN grade = 'A' THEN 4.0
                        WHEN grade = 'B' THEN 3.0
                        WHEN grade = 'C' THEN 2.0
                        WHEN grade = 'D' THEN 1.0
                        WHEN grade = 'F' THEN 0.0
                        ELSE 0.0
                    END) as gpa
                FROM exam_results er
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                WHERE er.student_id = ?
                AND es.exam_type = 'final'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['gpa'] ? round($result['gpa'], 2) : 0;
    }
}
?>
