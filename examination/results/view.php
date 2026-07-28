<?php
$page_title = 'View Exam Result';
require_once '../../config/db_connect.php';
require_once '../models/ExamResult.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$model = new ExamResult();
$result = $model->getById($_GET['id']);

if (!$result) {
    $_SESSION['error'] = "Result not found!";
    header("Location: index.php");
    exit();
}
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Result Details</h4>
        </div>
        <div class="page-header-actions">
            <a href="edit.php?id=<?php echo $result['result_id']; ?>" class="btn btn-outline">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="delete.php?id=<?php echo $result['result_id']; ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this result?')">
                <i class="bi bi-trash"></i> Delete
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Student</div>
                <div class="detail-value">
                    <strong><?php echo $result['student_name']; ?></strong><br>
                    <small style="color:var(--text-secondary);"><?php echo $result['student_id']; ?></small>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Course</div>
                <div class="detail-value">
                    <strong><?php echo $result['course_code']; ?></strong><br>
                    <?php echo $result['course_name']; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Exam Type</div>
                <div class="detail-value">
                    <span class="status-badge" style="background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);"><?php echo ucfirst($result['exam_type']); ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Exam Date</div>
                <div class="detail-value"><?php echo date('F d, Y', strtotime($result['exam_date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Marks</div>
                <div class="detail-value">
                    <strong><?php echo $result['marks_obtained']; ?></strong> / 
                    <strong><?php echo $result['total_marks']; ?></strong>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Percentage</div>
                <div class="detail-value">
                    <?php 
                    $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                    echo number_format($percentage, 2) . '%';
                    ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Grade</div>
                <div class="detail-value">
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
                    <span class="status-badge" style="<?php echo $style; ?>;font-size:1.2rem;">
                        <?php echo $result['grade']; ?>
                    </span>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
