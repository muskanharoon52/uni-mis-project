<?php
$page_title = 'Exam Results';
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/db_connect.php';
require_once '../models/ExamResult.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$model = new ExamResult();
$results = $model->getAll();

// Ensure results is an array
if (!$results) {
    $results = [];
}
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Results</h4>
        </div>
        <div class="page-header-actions">
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Results
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-content">
            <div class="table-responsive">
                <table class="data-table">
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
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#128202;</div>
                                        <p class="empty-state-text">No results found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['student_name'] ?? 'N/A'); ?></strong><br>
                                        <small style="color:var(--text-secondary);">ID: <?php echo htmlspecialchars($result['student_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['course_code'] ?? 'N/A'); ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?php echo htmlspecialchars($result['course_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);"><?php echo isset($result['exam_type']) ? ucfirst($result['exam_type']) : 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['marks_obtained'] ?? '0'); ?></strong> / <?php echo htmlspecialchars($result['total_marks'] ?? '0'); ?>
                                    </td>
                                    <td>
                                        <?php if (isset($result['grade']) && $result['grade']): ?>
                                            <?php
                                            $gradeStyles = [
                                                'A' => 'background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);',
                                                'B' => 'background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);',
                                                'C' => 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);',
                                                'D' => 'background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-border);',
                                                'F' => 'background:var(--error-bg);color:var(--error);border:1px solid var(--error-border);',
                                            ];
                                            $style = $gradeStyles[$result['grade']] ?? 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);';
                                            ?>
                                            <span class="status-badge" style="<?php echo $style; ?>"><?php echo $result['grade']; ?></span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusPublished = ($result['status'] ?? 'draft') === 'published';
                                        $statusStyle = $statusPublished
                                            ? 'background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);'
                                            : 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);';
                                        ?>
                                        <span class="status-badge" style="<?php echo $statusStyle; ?>">
                                            <?php echo ucfirst($result['status'] ?? 'draft'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:4px;">
                                            <a href="view.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-outline" style="padding:4px 8px;font-size:12px;" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-outline" style="padding:4px 8px;font-size:12px;" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (($result['status'] ?? 'draft') != 'published'): ?>
                                                <a href="publish.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                                   class="btn btn-outline" style="padding:4px 8px;font-size:12px;" title="Publish"
                                                   onclick="return confirm('Are you sure you want to publish this result?')">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="delete.php?id=<?php echo $result['result_id'] ?? 0; ?>" 
                                               class="btn btn-danger" style="padding:4px 8px;font-size:12px;" title="Delete"
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

<?php include '../includes/footer.php'; ?>
