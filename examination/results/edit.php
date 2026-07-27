<?php
$page_title = 'Edit Exam Result';
require_once '../../config/db_connect.php';
require_once '../models/ExamResult.php';

$model = new ExamResult();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $marksObtained = (float) ($_POST['marks_obtained'] ?? 0);
    $totalMarks = (float) ($_POST['total_marks'] ?? 0);

    if ($totalMarks <= 0 || $marksObtained < 0 || $marksObtained > $totalMarks) {
        $_SESSION['error'] = 'Marks obtained must be between 0 and the total marks.';
    } elseif ($model->update($_GET['id'], [
        'marks_obtained' => $marksObtained,
        'total_marks' => $totalMarks,
        'grade' => $_POST['grade']
    ])) {
        $_SESSION['success'] = 'Result updated successfully!';
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to update result.';
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';

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

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4><i class="bi bi-pencil"></i> Edit Exam Result</h4>
        </div>
    </div>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label for="student_id">Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['student_id']; ?>" 
                                <?php echo ($student['student_id'] == $result['student_id']) ? 'selected' : ''; ?>>
                            <?php echo $student['full_name'] . ' (' . $student['student_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="exam_id">Exam</label>
                <select id="exam_id" name="exam_id" required>
                    <option value="">Select Exam</option>
                    <?php while($exam = $exams->fetch_assoc()): ?>
                        <option value="<?php echo $exam['exam_id']; ?>" 
                                <?php echo ($exam['exam_id'] == $result['exam_id']) ? 'selected' : ''; ?>>
                            <?php echo $exam['course_code'] . ' - ' . $exam['exam_type'] . ' (' . date('M d', strtotime($exam['date'])) . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="marks_obtained">Marks Obtained</label>
                    <input type="number" step="0.01" id="marks_obtained" 
                           name="marks_obtained" value="<?php echo $result['marks_obtained']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="total_marks">Total Marks</label>
                    <input type="number" id="total_marks" 
                           name="total_marks" value="<?php echo $result['total_marks']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="grade">Grade</label>
                <select id="grade" name="grade" required>
                    <option value="">Select Grade</option>
                    <option value="A" <?php echo ($result['grade'] == 'A') ? 'selected' : ''; ?>>A (Excellent)</option>
                    <option value="B" <?php echo ($result['grade'] == 'B') ? 'selected' : ''; ?>>B (Good)</option>
                    <option value="C" <?php echo ($result['grade'] == 'C') ? 'selected' : ''; ?>>C (Average)</option>
                    <option value="D" <?php echo ($result['grade'] == 'D') ? 'selected' : ''; ?>>D (Poor)</option>
                    <option value="F" <?php echo ($result['grade'] == 'F') ? 'selected' : ''; ?>>F (Fail)</option>
                </select>
            </div>
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Result</button>
            </div>
        </form>
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

<?php include '../includes/footer.php'; ?>
