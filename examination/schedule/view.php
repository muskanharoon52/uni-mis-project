<?php
$page_title = 'View Exam Schedule';
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$model = new ExamSchedule();

$exam_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (!$exam_id) {
    $_SESSION['error'] = "Invalid schedule ID!";
    header("Location: index.php");
    exit();
}

$schedule = $model->getById($exam_id);

if (!$schedule) {
    $_SESSION['error'] = "Schedule not found!";
    header("Location: index.php");
    exit();
}
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Schedule Details</h4>
        </div>
        <div class="page-header-actions">
            <a href="edit.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-outline">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="delete.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this schedule?')">
                <i class="bi bi-trash"></i> Delete
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Course</div>
                <div class="detail-value">
                    <strong><?php echo htmlspecialchars($schedule['course_code']); ?></strong><br>
                    <?php echo htmlspecialchars($schedule['course_name']); ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Program</div>
                <div class="detail-value"><?php echo htmlspecialchars($schedule['program_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Exam Type</div>
                <div class="detail-value">
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
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date</div>
                <div class="detail-value"><?php echo date('l, F d, Y', strtotime($schedule['date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Time</div>
                <div class="detail-value">
                    <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> &ndash;
                    <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Duration</div>
                <div class="detail-value">
                    <?php 
                    $start = new DateTime($schedule['start_time']);
                    $end = new DateTime($schedule['end_time']);
                    $interval = $start->diff($end);
                    echo $interval->format('%h hours %i minutes');
                    ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Room</div>
                <div class="detail-value"><?php echo htmlspecialchars($schedule['room']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Credit Hours</div>
                <div class="detail-value"><?php echo htmlspecialchars($schedule['credit_hours']); ?></div>
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
