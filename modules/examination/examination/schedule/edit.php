<?php
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../../includes/header.php';
include '../../includes/navbar.php';

$conn = getConnection();
$model = new ExamSchedule();

// Get schedule ID
$exam_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (!$exam_id) {
    $_SESSION['error'] = "Invalid schedule ID!";
    header("Location: index.php");
    exit();
}

// Fetch schedule data
$schedule = $model->getById($exam_id);

if (!$schedule) {
    $_SESSION['error'] = "Schedule not found!";
    header("Location: index.php");
    exit();
}

// Fetch courses for dropdown
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_name");

// Handle form submission
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

<!-- Main Container -->
<div class="main-container">
    
    
    <div class="content-area" id="contentArea">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-pencil"></i> Edit Exam Schedule</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="scheduleForm">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course *</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php while($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                <?php echo ($course['course_id'] == $schedule['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="exam_type" class="form-label">Exam Type *</label>
                                <select class="form-select" id="exam_type" name="exam_type" required>
                                    <option value="">Select Exam Type</option>
                                    <option value="mid" <?php echo ($schedule['exam_type'] == 'mid') ? 'selected' : ''; ?>>Mid Term</option>
                                    <option value="final" <?php echo ($schedule['exam_type'] == 'final') ? 'selected' : ''; ?>>Final</option>
                                    <option value="quiz" <?php echo ($schedule['exam_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                    <option value="lab" <?php echo ($schedule['exam_type'] == 'lab') ? 'selected' : ''; ?>>Lab Exam</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo $schedule['date']; ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?php echo $schedule['start_time']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time *</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?php echo $schedule['end_time']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="room" class="form-label">Room / Venue *</label>
                                <input type="text" class="form-control" id="room" name="room" 
                                       value="<?php echo htmlspecialchars($schedule['room']); ?>" required>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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

<?php include '../../includes/footer.php'; ?>