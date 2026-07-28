<?php
require_once 'db.php';

/**
 * Generate a unique roll number for a student
 */
function generateRollNumber($student_id) {
    $conn = getConnection();
    
    // 1. Get student details
    $sql = "SELECT 
                s.student_id,
                s.full_name,
                d.department_code,
                ses.session_name,
                s.batch_year
            FROM students s
            JOIN departments d ON s.program_id = d.department_id
            JOIN sessions ses ON s.admission_session_id = ses.session_id
            WHERE s.student_id = ? AND s.roll_no IS NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if(!$student) {
        return ['success' => false, 'message' => 'Student not found or already has roll number'];
    }
    
    // 2. Extract year from session
    preg_match('/\d{4}/', $student['session_name'], $matches);
    $year = $matches[0] ?? date('Y');
    
    // 3. Get sequence number
    $sequence = getNextSequence($student['department_code'], $year);
    
    if(!$sequence['success']) {
        return $sequence;
    }
    
    // 4. Generate roll number: DEPT-YEAR-SEQ
    $roll_number = sprintf(
        "%s-%s-%03d",
        $student['department_code'],
        $year,
        $sequence['sequence']
    );
    
    // 5. Update student
    $updateSql = "UPDATE students SET roll_no = ? WHERE student_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $roll_number, $student_id);
    
    if($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'roll_number' => $roll_number,
            'message' => 'Roll number generated successfully'
        ];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update student'];
    }
}

/**
 * Get next sequence number for a department and session
 */
function getNextSequence($department_code, $session_year) {
    $conn = getConnection();
    
    // Get department ID
    $deptSql = "SELECT department_id FROM departments WHERE department_code = ?";
    $stmt = $conn->prepare($deptSql);
    $stmt->bind_param("s", $department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $dept = $result->fetch_assoc();
    $stmt->close();
    
    if(!$dept) {
        return ['success' => false, 'message' => 'Department not found'];
    }
    
    $department_id = $dept['department_id'];
    
    // Check if counter exists
    $counterSql = "SELECT last_sequence FROM roll_number_counter 
                   WHERE department_id = ? AND session_year = ?";
    $stmt = $conn->prepare($counterSql);
    $stmt->bind_param("is", $department_id, $session_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $counter = $result->fetch_assoc();
    $stmt->close();
    
    if($counter) {
        $sequence = $counter['last_sequence'] + 1;
        $updateSql = "UPDATE roll_number_counter 
                      SET last_sequence = ? 
                      WHERE department_id = ? AND session_year = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("iis", $sequence, $department_id, $session_year);
        $stmt->execute();
        $stmt->close();
    } else {
        $sequence = 1;
        $insertSql = "INSERT INTO roll_number_counter (department_id, session_year, last_sequence) 
                      VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("isi", $department_id, $session_year, $sequence);
        $stmt->execute();
        $stmt->close();
    }
    
    return ['success' => true, 'sequence' => $sequence];
}

/**
 * Get student statistics
 */
function getStudentStatistics() {
    $conn = getConnection();
    $stats = [];
    
    // Total students
    $result = $conn->query("SELECT COUNT(*) as total FROM students");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // With roll numbers
    $result = $conn->query("SELECT COUNT(*) as total FROM students WHERE roll_no IS NOT NULL");
    $stats['with_roll_no'] = $result->fetch_assoc()['total'];
    
    // Without roll numbers
    $stats['without_roll_no'] = $stats['total'] - $stats['with_roll_no'];
    
    // Active students
    $result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
    $stats['active'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

/**
 * Get pending students (without roll numbers)
 */
function getPendingStudents() {
    $conn = getConnection();
    $sql = "SELECT 
                s.student_id,
                s.full_name,
                d.department_code,
                ses.session_name,
                s.batch_year,
                s.application_id
            FROM students s
            JOIN departments d ON s.program_id = d.department_id
            JOIN sessions ses ON s.admission_session_id = ses.session_id
            WHERE s.roll_no IS NULL
            ORDER BY s.student_id";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get students with roll numbers
 */
function getStudentsWithRollNumbers() {
    $conn = getConnection();
    $sql = "SELECT 
                s.student_id,
                s.full_name,
                s.roll_no,
                s.batch_year,
                s.admission_date,
                s.status,
                d.department_code,
                d.department_name,
                ses.session_name
            FROM students s
            JOIN departments d ON s.program_id = d.department_id
            JOIN sessions ses ON s.admission_session_id = ses.session_id
            WHERE s.roll_no IS NOT NULL
            ORDER BY d.department_code, s.roll_no";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get recent students
 */
function getRecentStudents($limit = 20) {
    $conn = getConnection();
    $sql = "SELECT 
                s.student_id,
                s.full_name,
                s.roll_no,
                d.department_code,
                ses.session_name,
                s.batch_year,
                s.status
            FROM students s
            LEFT JOIN departments d ON s.program_id = d.department_id
            LEFT JOIN sessions ses ON s.admission_session_id = ses.session_id
            ORDER BY s.student_id DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    $output = '';
    if(isset($_SESSION['success'])) {
        $output .= '<div class="alert alert-success alert-dismissible fade show">';
        $output .= '<i class="fas fa-check-circle"></i> ' . $_SESSION['success'];
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $output .= '</div>';
        unset($_SESSION['success']);
    }
    if(isset($_SESSION['error'])) {
        $output .= '<div class="alert alert-danger alert-dismissible fade show">';
        $output .= '<i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'];
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $output .= '</div>';
        unset($_SESSION['error']);
    }
    return $output;
}
?>