<?php
$page_title = 'View Exam Schedule';
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$model = new ExamSchedule();

// Get schedule ID
$exam_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (!$exam_id) {
    $_SESSION['error'] = "Invalid schedule ID!";
    header("Location: index.php");
    exit();
}

// Fetch schedule data
$schedule = $model->getById($exam_id);

if (!$schedule) {
    $_SESSION['error'] = "Schedule not found!";
    header("Location: index.php");
    exit();
}
?>

<div class="content-area" id="contentArea">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar-event"></i> Exam Schedule Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Course</th>
                                <td>
                                    <strong><?php echo htmlspecialchars($schedule['course_code']); ?></strong><br>
                                    <?php echo htmlspecialchars($schedule['course_name']); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Program</th>
                                <td><?php echo htmlspecialchars($schedule['program_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Exam Type</th>
                                <td>
                                    <span class="badge badge-exam-<?php echo $schedule['exam_type']; ?>">
                                        <?php echo strtoupper(htmlspecialchars($schedule['exam_type'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?php echo date('l, F d, Y', strtotime($schedule['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Time</th>
                                <td>
                                    <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>
                                    <?php 
                                    $start = new DateTime($schedule['start_time']);
                                    $end = new DateTime($schedule['end_time']);
                                    $interval = $start->diff($end);
                                    echo $interval->format('%h hours %i minutes');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Room</th>
                                <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                            </tr>
                            <tr>
                                <th>Credit Hours</th>
                                <td><?php echo htmlspecialchars($schedule['credit_hours']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="edit.php?id=<?php echo $schedule['exam_id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="delete.php?id=<?php echo $schedule['exam_id']; ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this schedule?')">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>