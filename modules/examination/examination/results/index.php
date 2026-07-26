<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Correct paths - going up 4 levels to reach root
// From: modules/examination/examination/results/index.php
// Go up: results -> examination -> examination -> modules -> root
require_once '../../../../config/db_connect.php';
require_once '../models/ExamResult.php';

// Include header and navbar - going up 4 levels to root includes
include '../../../../includes/header.php';
$hideSidebarToggle = true;
$showDashboardBackButton = true;
include '../../../../includes/navbar.php';

$model = new ExamResult();
$results = $model->getAll();

// Ensure results is an array
if (!$results) {
    $results = [];
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Exam Results</h2>
        <a href="add.php" class="btn btn-primary">
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
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
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
                            <th>#</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Exam Type</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No results found</td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['student_name'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted">ID: <?php echo htmlspecialchars($result['student_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['course_code'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($result['course_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo isset($result['exam_type']) ? ucfirst($result['exam_type']) : 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['marks_obtained'] ?? '0'); ?></strong> / <?php echo htmlspecialchars($result['total_marks'] ?? '0'); ?>
                                    </td>
                                    <td>
                                        <?php if (isset($result['grade']) && $result['grade']): ?>
                                            <span class="badge <?php 
                                                echo $result['grade'] == 'A' ? 'bg-success' : 
                                                    ($result['grade'] == 'B' ? 'bg-primary' : 
                                                    ($result['grade'] == 'C' ? 'bg-warning' : 
                                                    ($result['grade'] == 'D' ? 'bg-info' : 'bg-danger')));
                                            ?>">
                                                <?php echo $result['grade']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo ($result['status'] ?? 'draft') == 'published' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($result['status'] ?? 'draft'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (($result['status'] ?? 'draft') != 'published'): ?>
                                                <a href="publish.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                                   class="btn btn-success" title="Publish"
                                                   onclick="return confirm('Are you sure you want to publish this result?')">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="delete.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-danger" title="Delete"
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

<?php include '../../../../includes/footer.php'; ?>