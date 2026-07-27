<?php
$page_title = 'Edit Exam Schedule';
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$conn = getConnection();
$model = new ExamSchedule();

$exam_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (!$exam_id) {
    $_SESSION['error'] = "Invalid schedule ID!";
    header("Location: index.php");
    exit();
}

$schedule = $model->getById($exam_id);

if (!$schedule) {
    $_SESSION['error'] = "Schedule not found!";
    header("Location: index.php");
    exit();
}

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
    
    if ($model->update($exam_id, $data)) {
        $_SESSION['success'] = "Exam schedule updated successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update exam schedule. Please check for conflicts.";
    }
}

$conn->close();
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Edit Exam Schedule</h4>
        </div>
        <div class="page-header-actions">
            <a href="index.php" class="btn btn-ghost">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" id="scheduleForm">
            <div class="form-group">
                <label for="course_id">Course *</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($course['course_id'] == $schedule['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="exam_type">Exam Type *</label>
                <select id="exam_type" name="exam_type" required>
                    <option value="">Select Exam Type</option>
                    <option value="mid" <?php echo ($schedule['exam_type'] == 'mid') ? 'selected' : ''; ?>>Mid Term</option>
                    <option value="final" <?php echo ($schedule['exam_type'] == 'final') ? 'selected' : ''; ?>>Final</option>
                    <option value="quiz" <?php echo ($schedule['exam_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                    <option value="lab" <?php echo ($schedule['exam_type'] == 'lab') ? 'selected' : ''; ?>>Lab Exam</option>
                </select>
            </div>

            <div class="form-group">
                <label for="date">Date *</label>
                <input type="date" id="date" name="date" value="<?php echo $schedule['date']; ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo $schedule['start_time']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time *</label>
                    <input type="time" id="end_time" name="end_time" value="<?php echo $schedule['end_time']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="room">Room / Venue *</label>
                <input type="text" id="room" name="room" value="<?php echo htmlspecialchars($schedule['room']); ?>" required>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Schedule
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
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
