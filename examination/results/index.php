<?php
require_once '../../config/database.php';
require_once '../models/ExamResult.php';
include '../../includes/header.php';
include '../../includes/navbar.php';


$model = new ExamResult();
$results = $model->getAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Exam Results</h2>
        <a href="?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Results
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Exam Type</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No results found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $result['student_name']; ?></strong><br>
                                        <small><?php echo $result['student_id']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $result['course_code']; ?></strong><br>
                                        <small><?php echo $result['course_name']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst($result['exam_type']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $result['grade'] == 'A' ? 'bg-success' : 
                                                ($result['grade'] == 'B' ? 'bg-primary' : 
                                                ($result['grade'] == 'C' ? 'bg-warning' : 
                                                ($result['grade'] == 'D' ? 'bg-info' : 'bg-danger')));
                                        ?>">
                                            <?php echo $result['grade']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=view&id=<?php echo $result['result_id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $result['result_id']; ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $result['result_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this result?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>