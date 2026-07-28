<?php
$page_title = 'Publish Results';
require_once '../../config/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$conn = getConnection();

// Get all unpublished results
$unpublished = $conn->query("
    SELECT er.*, s.student_id, u.full_name, c.course_name, es.exam_type
    FROM exam_results er
    JOIN students s ON er.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN exam_schedules es ON er.exam_id = es.exam_id
    JOIN courses c ON es.course_id = c.course_id
    WHERE er.status = 'draft'
    ORDER BY er.result_id DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish'])) {
    $result_ids = $_POST['result_ids'] ?? [];
    
    if (!empty($result_ids)) {
        $ids = implode(',', array_map('intval', $result_ids));
        $sql = "UPDATE exam_results SET status = 'published' WHERE result_id IN ($ids)";
        if ($conn->query($sql)) {
            $_SESSION['success'] = count($result_ids) . " results published successfully!";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to publish results!";
        }
    } else {
        $_SESSION['error'] = "Please select at least one result to publish!";
    }
}
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Publish Results</h4>
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
            <form method="POST">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleAll()" style="width:16px;height:16px;accent-color:var(--accent);"></th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Exam Type</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($unpublished->num_rows == 0): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">&#9729;</div>
                                            <p class="empty-state-text">No unpublished results found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while($row = $unpublished->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="result_ids[]" 
                                                   value="<?php echo $row['result_id']; ?>" 
                                                   class="result-checkbox"
                                                   style="width:16px;height:16px;accent-color:var(--accent);">
                                        </td>
                                        <td>
                                            <strong><?php echo $row['full_name']; ?></strong><br>
                                            <small style="color:var(--text-secondary);"><?php echo $row['student_id']; ?></small>
                                        </td>
                                        <td><?php echo $row['course_name']; ?></td>
                                        <td>
                                            <span class="status-badge" style="background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);"><?php echo ucfirst($row['exam_type']); ?></span>
                                        </td>
                                        <td><?php echo $row['marks_obtained']; ?>/<?php echo $row['total_marks']; ?></td>
                                        <td>
                                            <?php
                                            $gradeStyles = [
                                                'A' => 'background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);',
                                                'B' => 'background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);',
                                                'C' => 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);',
                                                'D' => 'background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-border);',
                                                'F' => 'background:var(--error-bg);color:var(--error);border:1px solid var(--error-border);',
                                            ];
                                            $style = $gradeStyles[$row['grade']] ?? 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);';
                                            ?>
                                            <span class="status-badge" style="<?php echo $style; ?>">
                                                <?php echo $row['grade']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($unpublished->num_rows > 0): ?>
                    <div class="form-actions">
                        <button type="submit" name="publish" class="btn btn-primary"
                                onclick="return confirm('Are you sure you want to publish selected results?')">
                            <i class="bi bi-cloud-upload"></i> Publish Selected Results
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.result-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}
</script>

<?php include '../includes/footer.php'; ?>
