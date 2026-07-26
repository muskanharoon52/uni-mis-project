<?php
require_once '../../config/db_connect.php';
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
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Exam Result Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Student</th>
                            <td>
                                <strong><?php echo $result['student_name']; ?></strong><br>
                                <small><?php echo $result['student_id']; ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th>Course</th>
                            <td>
                                <strong><?php echo $result['course_code']; ?></strong><br>
                                <?php echo $result['course_name']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Exam Type</th>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst($result['exam_type']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Exam Date</th>
                            <td><?php echo date('F d, Y', strtotime($result['exam_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Marks</th>
                            <td>
                                <strong><?php echo $result['marks_obtained']; ?></strong> / 
                                <strong><?php echo $result['total_marks']; ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Percentage</th>
                            <td>
                                <?php 
                                $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                echo number_format($percentage, 2) . '%';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Grade</th>
                            <td>
                                <span class="badge <?php echo getGradeColor($result['grade']); ?>" style="font-size: 1.2rem;">
                                    <?php echo $result['grade']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                <a href="index.php" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="edit.php?id=<?php echo $result['result_id']; ?>" class="btn btn-warning me-2">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="delete.php?id=<?php echo $result['result_id']; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this result?')">
                    <i class="bi bi-trash"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
