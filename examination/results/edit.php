<?php
require_once '../../config/database.php';
require_once '../models/ExamResult.php';
include '../../includes/header.php';
include '../../includes/navbar.php';

$model = new ExamResult();
$result = $model->getById($_GET['id']);

if (!$result) {
    $_SESSION['error'] = "Result not found!";
    header("Location: index.php");
    exit();
}

$conn = getConnection();
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
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-pencil"></i> Edit Exam Result</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php while($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>" 
                                            <?php echo ($student['student_id'] == $result['student_id']) ? 'selected' : ''; ?>>
                                        <?php echo $student['full_name'] . ' (' . $student['student_id'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_id" class="form-label">Exam</label>
                            <select class="form-select" id="exam_id" name="exam_id" required>
                                <option value="">Select Exam</option>
                                <?php while($exam = $exams->fetch_assoc()): ?>
                                    <option value="<?php echo $exam['exam_id']; ?>" 
                                            <?php echo ($exam['exam_id'] == $result['exam_id']) ? 'selected' : ''; ?>>
                                        <?php echo $exam['course_code'] . ' - ' . $exam['exam_type'] . ' (' . date('M d', strtotime($exam['date'])) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="marks_obtained" class="form-label">Marks Obtained</label>
                                <input type="number" step="0.01" class="form-control" id="marks_obtained" 
                                       name="marks_obtained" value="<?php echo $result['marks_obtained']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="total_marks" class="form-label">Total Marks</label>
                                <input type="number" class="form-control" id="total_marks" 
                                       name="total_marks" value="<?php echo $result['total_marks']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select" id="grade" name="grade" required>
                                <option value="">Select Grade</option>
                                <option value="A" <?php echo ($result['grade'] == 'A') ? 'selected' : ''; ?>>A (Excellent)</option>
                                <option value="B" <?php echo ($result['grade'] == 'B') ? 'selected' : ''; ?>>B (Good)</option>
                                <option value="C" <?php echo ($result['grade'] == 'C') ? 'selected' : ''; ?>>C (Average)</option>
                                <option value="D" <?php echo ($result['grade'] == 'D') ? 'selected' : ''; ?>>D (Poor)</option>
                                <option value="F" <?php echo ($result['grade'] == 'F') ? 'selected' : ''; ?>>F (Fail)</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Result</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('marks_obtained').addEventListener('change', updateGrade);
document.getElementById('total_marks').addEventListener('change', updateGrade);

function updateGrade() {
    const marks = parseFloat(document.getElementById('marks_obtained').value);
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
}
</script>

<?php include '../../includes/footer.php'; ?>