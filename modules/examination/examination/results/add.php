<?php
require_once '../../config/database.php';
require_once '../models/ExamResult.php';
require_once '../models/ExamSchedule.php';

$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marksObtained = (float) ($_POST['marks_obtained'] ?? 0);
    $totalMarks = (float) ($_POST['total_marks'] ?? 0);

    if (empty($_POST['student_id']) || empty($_POST['exam_id']) || empty($_POST['grade'])) {
        $formError = 'Please complete all fields.';
    } elseif ($totalMarks <= 0 || $marksObtained < 0 || $marksObtained > $totalMarks) {
        $formError = 'Marks obtained must be between 0 and the total marks.';
    } else {
        $resultModel = new ExamResult();
        $resultAdded = $resultModel->add([
            'student_id' => $_POST['student_id'],
            'exam_id' => $_POST['exam_id'],
            'marks_obtained' => $marksObtained,
            'total_marks' => $totalMarks,
            'grade' => $_POST['grade']
        ]);

        if ($resultAdded) {
            $_SESSION['success'] = 'Result added successfully!';
            header('Location: index.php');
            exit;
        }

        $formError = 'This result already exists, or it could not be added.';
    }
}

include '../../includes/header.php';
$hideSidebarToggle = true;
$showDashboardBackButton = true;
include '../../includes/navbar.php';

$conn = getConnection();
$scheduleModel = new ExamSchedule();
$schedules = $scheduleModel->getUpcoming();

// Get students
$students = $conn->query("
    SELECT s.student_id, u.full_name, p.program_name 
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN programs p ON s.program_id = p.program_id
    WHERE s.status = 'active'
    ORDER BY u.full_name
");
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Add Exam Results</h5>
                </div>
                <div class="card-body">
                    <?php if ($formError): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($formError); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php while($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo $student['full_name'] . ' (' . $student['student_id'] . ') - ' . $student['program_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_id" class="form-label">Exam Schedule</label>
                            <select class="form-select" id="exam_id" name="exam_id" required>
                                <option value="">Select Exam</option>
                                <?php foreach($schedules as $schedule): ?>
                                    <option value="<?php echo $schedule['exam_id']; ?>">
                                        <?php echo $schedule['course_code'] . ' - ' . $schedule['exam_type'] . ' (' . date('M d', strtotime($schedule['date'])) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="marks_obtained" class="form-label">Marks Obtained</label>
                                <input type="number" step="0.01" class="form-control" id="marks_obtained" name="marks_obtained" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="total_marks" class="form-label">Total Marks</label>
                                <input type="number" class="form-control" id="total_marks" name="total_marks" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select" id="grade" name="grade" required>
                                <option value="">Select Grade</option>
                                <option value="A">A (Excellent)</option>
                                <option value="B">B (Good)</option>
                                <option value="C">C (Average)</option>
                                <option value="D">D (Poor)</option>
                                <option value="F">F (Fail)</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Result</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate grade based on marks
document.getElementById('marks_obtained').addEventListener('change', function() {
    const marks = parseFloat(this.value);
    const total = parseFloat(document.getElementById('total_marks').value);
    if (marks && total) {
        const percentage = (marks / total) * 100;
        let grade = '';
        if (percentage >= 90) grade = 'A';
        else if (percentage >= 80) grade = 'B';
        else if (percentage >= 70) grade = 'C';
        else if (percentage >= 60) grade = 'D';
        else grade = 'F';
        document.getElementById('grade').value = grade;
    }
});

document.getElementById('total_marks').addEventListener('change', function() {
    const marks = parseFloat(document.getElementById('marks_obtained').value);
    const total = parseFloat(this.value);
    if (marks && total) {
        const percentage = (marks / total) * 100;
        let grade = '';
        if (percentage >= 90) grade = 'A';
        else if (percentage >= 80) grade = 'B';
        else if (percentage >= 70) grade = 'C';
        else if (percentage >= 60) grade = 'D';
        else grade = 'F';
        document.getElementById('grade').value = grade;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
