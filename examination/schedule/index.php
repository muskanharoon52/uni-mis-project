<?php
require_once '../../config/db_connect.php';
require_once '../models/ExamSchedule.php';
include '../../includes/header.php';
$showDashboardBackButton = true;
$hideSidebarToggle = true;
include '../../includes/navbar.php';

// Initialize the model
$model = new ExamSchedule();
$schedules = $model->getAll();
?>

<!-- Main Container with Slideable Sidebar -->
<div class="main-container">
    
    
    <!-- Main Content Area -->
    <div class="content-area" id="contentArea">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2> Exam Schedules</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Schedule
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
                    <table class="table table-striped table-hover datatable" id="scheduleTable">
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
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No exam schedules found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($schedule['course_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($schedule['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-exam-<?php echo $schedule['exam_type']; ?>">
                                                <?php echo strtoupper(htmlspecialchars($schedule['exam_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($schedule['date'])); ?></td>
                                        <td>
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view.php?id=<?php echo $schedule['exam_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $schedule['exam_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $schedule['exam_id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this exam schedule?')">
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
</div>

<?php include '../../includes/footer.php'; ?>
