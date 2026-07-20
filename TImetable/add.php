<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Fetch dropdown data
$teachers = $conn->query("SELECT teacher_id, teacher_name FROM teachers WHERE status = 'Active' ORDER BY teacher_name");
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$sessions = $conn->query("SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room_no = trim($_POST['room_no'] ?? '');
    $section = trim($_POST['section'] ?? '');

    // Validation
    if ($course_id <= 0) $error = "Please select a course";
    elseif ($teacher_id <= 0) $error = "Please select a teacher";
    elseif ($semester_id <= 0) $error = "Please select a semester";
    elseif ($session_id <= 0) $error = "Please select a session";
    elseif (empty($day_of_week)) $error = "Please select a day";
    elseif (empty($start_time)) $error = "Please select start time";
    elseif (empty($end_time)) $error = "Please select end time";
    elseif (empty($room_no)) $error = "Please enter room number";
    elseif (empty($section)) $error = "Please enter section";

    if (empty($error)) {
        // Check for conflicts
        $check_sql = "SELECT id FROM timetable 
                      WHERE day_of_week = ? AND start_time < ? AND end_time > ? AND room_no = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssss", $day_of_week, $end_time, $start_time, $room_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Room is already booked at this time slot!";
        } else {
            // Insert timetable entry
            $insert_sql = "INSERT INTO timetable 
                          (course_id, teacher_id, semester_id, session_id, day_of_week, 
                           start_time, end_time, room_no, section) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiiisssss", $course_id, $teacher_id, $semester_id, $session_id, 
                                    $day_of_week, $start_time, $end_time, $room_no, $section);
            
            if ($insert_stmt->execute()) {
                header("Location: index.php?success=Class added successfully!");
                exit;
            } else {
                $error = "Error adding class: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Timetable Entry';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-container .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    @media (max-width: 768px) {
        .form-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="form-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-plus-circle"></i> Add New Class</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Course <span class="required-star">*</span></label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php while($row = $courses->fetch_assoc()): ?>
                                <option value="<?= $row['course_id'] ?>">
                                    <?= htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teacher <span class="required-star">*</span></label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">Select Teacher</option>
                            <?php while($row = $teachers->fetch_assoc()): ?>
                                <option value="<?= $row['teacher_id'] ?>">
                                    <?= htmlspecialchars($row['teacher_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Semester <span class="required-star">*</span></label>
                        <select name="semester_id" class="form-select" required>
                            <option value="">Select Semester</option>
                            <?php while($row = $semesters->fetch_assoc()): ?>
                                <option value="<?= $row['semester_id'] ?>">
                                    <?= htmlspecialchars($row['semester_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Session <span class="required-star">*</span></label>
                        <select name="session_id" class="form-select" required>
                            <option value="">Select Session</option>
                            <?php while($row = $sessions->fetch_assoc()): ?>
                                <option value="<?= $row['session_id'] ?>">
                                    <?= htmlspecialchars($row['session_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Day <span class="required-star">*</span></label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Start Time <span class="required-star">*</span></label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">End Time <span class="required-star">*</span></label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room No <span class="required-star">*</span></label>
                        <input type="text" name="room_no" class="form-control" placeholder="e.g., Room 101" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Section <span class="required-star">*</span></label>
                        <input type="text" name="section" class="form-control" placeholder="e.g., A, B, C" required>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Class
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>