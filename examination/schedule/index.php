<?php
$page_title = 'Exam Schedule';
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$model = new ExamSchedule();
$schedules = $model->getAll();

// Get the ID to highlight
$highlight_id = isset($_GET['highlight']) ? intval($_GET['highlight']) : 0;
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Schedules</h4>
        </div>
        <div class="page-header-actions">
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Schedule
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-content">
            <?php if (empty($schedules)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">&#128197;</div>
                    <p class="empty-state-text">No exam schedules found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table datatable" id="scheduleTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Exam Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr id="row-<?php echo $schedule['exam_id']; ?>" 
                                    style="<?php echo ($highlight_id == $schedule['exam_id']) ? 'background-color: #fff3cd; border-left: 4px solid #ffc107;' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($schedule['course_code']); ?></strong><br>
                                        <span class="muted"><?php echo htmlspecialchars($schedule['course_name']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $typeColors = [
                                            'mid' => 'background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);',
                                            'final' => 'background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger-border);',
                                            'quiz' => 'background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);',
                                            'lab' => 'background:#F3E8FF;color:#7c3aed;border:1px solid #DDD6FE;',
                                        ];
                                        $typeStyle = $typeColors[$schedule['exam_type']] ?? 'background:var(--border);color:var(--text);';
                                        ?>
                                        <span class="status-badge" style="<?php echo $typeStyle; ?>">
                                            <?php echo strtoupper(htmlspecialchars($schedule['exam_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($schedule['date'])); ?></td>
                                    <td>
                                        <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> &ndash;
                                        <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                    <td>
                                        <div style="display:flex;gap:4px;">
                                            <a href="view.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px;" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px;" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-danger" style="padding:4px 10px;font-size:12px;" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this exam schedule?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-scroll to the highlighted row when the page loads
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($highlight_id > 0): ?>
        var row = document.getElementById("row-<?php echo $highlight_id; ?>");
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>