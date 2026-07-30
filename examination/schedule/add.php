<?php
$page_title = 'Add Exam Schedule';

require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';

$conn = getConnection();
$model = new ExamSchedule();
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'course_id' => $_POST['course_id'],
        'exam_type' => $_POST['exam_type'],
        'date' => $_POST['date'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time'],
        'room' => $_POST['room']
    ];
    
    // DEBUG: We use a try/catch block to force the error to show
    try {
        if ($model->add($data)) {
            $new_id = $conn->insert_id;
            $_SESSION['success'] = "Exam schedule added successfully!";
            header("Location: index.php?highlight=" . $new_id);
            exit();
        } else {
            // If the model returns false, we capture the specific database error
            throw new Exception("The model's add() method returned false. Check your ExamSchedule.php file.");
        }
    } catch (Exception $e) {
        // Instead of silently failing, we print the error on the screen!
        $_SESSION['error'] = "DATABASE ERROR: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Add New Exam Schedule</h4>
        </div>
        <div class="page-header-actions">
            <a href="index.php" class="btn btn-ghost">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" id="scheduleForm">
            <div class="form-group">
                <label for="course_id">Course *</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="exam_type">Exam Type *</label>
                <select id="exam_type" name="exam_type" required>
                    <option value="">Select Exam Type</option>
                    <option value="mid">Mid Term</option>
                    <option value="final">Final</option>
                    <option value="quiz">Quiz</option>
                    <option value="lab">Lab Exam</option>
                </select>
            </div>

            <div class="form-group">
                <label for="date">Date *</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time *</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>

            <div class="form-group">
                <label for="room">Room / Venue *</label>
                <input type="text" id="room" name="room" placeholder="e.g., Hall A, Lab 1" required>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        alert('End time must be after start time!');
    }
});
</script>

<?php include '../includes/footer.php'; ?>