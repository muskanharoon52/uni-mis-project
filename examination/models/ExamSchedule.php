<?php
require_once '../../../../config/db_connect.php';

class ExamSchedule {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Get all exam schedules with course and program details
     */
    public function getAll() {
        $sql = "SELECT es.*, c.course_name, c.course_code, c.credit_hours, 
                       p.program_name, p.program_code
                FROM exam_schedules es
                JOIN courses c ON es.course_id = c.course_id
                JOIN programs p ON c.program_id = p.program_id
                ORDER BY es.date DESC, es.start_time DESC";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    /**
     * Get exam schedule by ID
     */
    public function getById($exam_id) {
        $sql = "SELECT es.*, c.course_name, c.course_code, c.credit_hours,
                       p.program_name, p.program_code
                FROM exam_schedules es
                JOIN courses c ON es.course_id = c.course_id
                JOIN programs p ON c.program_id = p.program_id
                WHERE es.exam_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Add new exam schedule
     */
    public function add($data) {
        // Validate data
        if (empty($data['course_id']) || empty($data['exam_type']) || 
            empty($data['date']) || empty($data['start_time']) || 
            empty($data['end_time']) || empty($data['room'])) {
            return false;
        }
        
        // Check for conflicts
        if ($this->hasConflict($data['course_id'], $data['date'], $data['start_time'], $data['end_time'])) {
            return false;
        }
        
        $sql = "INSERT INTO exam_schedules (course_id, exam_type, date, start_time, end_time, room) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("isssss", 
            $data['course_id'], 
            $data['exam_type'], 
            $data['date'], 
            $data['start_time'], 
            $data['end_time'], 
            $data['room']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Update exam schedule
     */
    public function update($exam_id, $data) {
        // Validate data
        if (empty($data['course_id']) || empty($data['exam_type']) || 
            empty($data['date']) || empty($data['start_time']) || 
            empty($data['end_time']) || empty($data['room'])) {
            return false;
        }
        
        // Check for conflicts (excluding current schedule)
        if ($this->hasConflict($data['course_id'], $data['date'], $data['start_time'], $data['end_time'], $exam_id)) {
            return false;
        }
        
        $sql = "UPDATE exam_schedules 
                SET course_id = ?, exam_type = ?, date = ?, start_time = ?, end_time = ?, room = ? 
                WHERE exam_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("isssssi", 
            $data['course_id'], 
            $data['exam_type'], 
            $data['date'], 
            $data['start_time'], 
            $data['end_time'], 
            $data['room'],
            $exam_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete exam schedule
     */
    public function delete($exam_id) {
        // Check if there are results linked to this exam
        $check = $this->conn->prepare("SELECT COUNT(*) as count FROM exam_results WHERE exam_id = ?");
        if (!$check) {
            return false;
        }
        
        $check->bind_param("i", $exam_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Cannot delete exam schedule with existing results!";
            return false;
        }
        
        $sql = "DELETE FROM exam_schedules WHERE exam_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $exam_id);
        return $stmt->execute();
    }
    
    /**
     * Check for scheduling conflicts
     */
    private function hasConflict($course_id, $date, $start_time, $end_time, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM exam_schedules 
                WHERE course_id = ? AND date = ? 
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
        
        if ($exclude_id) {
            $sql .= " AND exam_id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssssi", $course_id, $date, $start_time, $start_time, $end_time, $end_time, $exclude_id);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssss", $course_id, $date, $start_time, $start_time, $end_time, $end_time);
        }
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }
    
    /**
     * Get upcoming exams
     */
    public function getUpcoming() {
        $sql = "SELECT es.*, c.course_name, c.course_code, p.program_name 
                FROM exam_schedules es
                JOIN courses c ON es.course_id = c.course_id
                JOIN programs p ON c.program_id = p.program_id
                WHERE es.date >= CURDATE()
                ORDER BY es.date ASC, es.start_time ASC
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    /**
     * Get exams by date range
     */
    public function getByDateRange($start_date, $end_date) {
        $sql = "SELECT es.*, c.course_name, c.course_code, p.program_name 
                FROM exam_schedules es
                JOIN courses c ON es.course_id = c.course_id
                JOIN programs p ON c.program_id = p.program_id
                WHERE es.date BETWEEN ? AND ?
                ORDER BY es.date ASC, es.start_time ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    /**
     * Get exams by course
     */
    public function getByCourse($course_id) {
        $sql = "SELECT * FROM exam_schedules WHERE course_id = ? ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
}
?>