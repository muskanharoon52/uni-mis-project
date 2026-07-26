<?php
require_once '../../../../config/db_connect.php';

class StudentPromotion {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all students eligible for promotion
    public function getEligibleStudents($program_id, $current_semester) {
        $sql = "SELECT s.student_id, u.full_name, s.semester, p.program_name
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                JOIN programs p ON s.program_id = p.program_id
                WHERE s.program_id = ? 
                AND s.semester = ?
                AND s.status = 'active'
                AND s.student_id NOT IN (
                    SELECT DISTINCT er.student_id 
                    FROM exam_results er
                    JOIN exam_schedules es ON er.exam_id = es.exam_id
                    WHERE er.grade = 'F'
                    AND es.exam_type IN ('final', 'mid')
                )
                AND s.student_id NOT IN (
                    SELECT DISTINCT student_id 
                    FROM student_courses 
                    WHERE status = 'failed' OR grade = 'F'
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $program_id, $current_semester);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Promote students to next semester
    public function promote($student_ids, $next_semester) {
        $success = true;
        $this->conn->begin_transaction();
        
        try {
            foreach ($student_ids as $student_id) {
                // Update student semester
                $sql = "UPDATE students SET semester = ? WHERE student_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("is", $next_semester, $student_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to promote student: " . $student_id);
                }
                
                // Update student_courses status for completed courses
                $sql = "UPDATE student_courses 
                        SET status = 'completed' 
                        WHERE student_id = ? AND semester < ? AND status = 'enrolled'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $student_id, $next_semester);
                $stmt->execute();
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Promotion error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if student can be promoted
    public function canPromote($student_id) {
        // Check for failed grades in final exams
        $sql = "SELECT COUNT(*) as failed_count
                FROM exam_results er
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                WHERE er.student_id = ?
                AND er.grade = 'F'
                AND es.exam_type IN ('final', 'mid')
                AND YEAR(es.date) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['failed_count'] > 0) {
            return false;
        }
        
        // Check for failed courses
        $sql = "SELECT COUNT(*) as failed_count
                FROM student_courses
                WHERE student_id = ?
                AND (status = 'failed' OR grade = 'F')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['failed_count'] == 0;
    }
    
    // Get student's academic standing
    public function getAcademicStanding($student_id) {
        $sql = "SELECT 
                    COUNT(CASE WHEN grade IN ('A', 'B') THEN 1 END) as good_grades,
                    COUNT(CASE WHEN grade = 'C' THEN 1 END) as avg_grades,
                    COUNT(CASE WHEN grade = 'D' THEN 1 END) as poor_grades,
                    COUNT(CASE WHEN grade = 'F' THEN 1 END) as failed_grades,
                    AVG(marks_obtained / total_marks * 100) as avg_percentage
                FROM exam_results er
                JOIN exam_schedules es ON er.exam_id = es.exam_id
                WHERE er.student_id = ?
                AND es.exam_type = 'final'
                AND YEAR(es.date) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Get student promotion history
    public function getPromotionHistory($student_id) {
        $sql = "SELECT s.semester, p.program_name,
                (SELECT COUNT(*) FROM student_courses sc 
                 WHERE sc.student_id = s.student_id AND sc.status = 'completed') as courses_completed,
                (SELECT AVG(grade_point) FROM (
                    SELECT CASE 
                        WHEN grade = 'A' THEN 4.0
                        WHEN grade = 'B' THEN 3.0
                        WHEN grade = 'C' THEN 2.0
                        WHEN grade = 'D' THEN 1.0
                        WHEN grade = 'F' THEN 0.0
                    END as grade_point
                    FROM exam_results er
                    JOIN exam_schedules es ON er.exam_id = es.exam_id
                    WHERE er.student_id = s.student_id
                ) as grades) as gpa
                FROM students s
                JOIN programs p ON s.program_id = p.program_id
                WHERE s.student_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>