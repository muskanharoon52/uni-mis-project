<?php
// 9. Attendance/report.php
// View attendance reports

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

$page_title = 'Attendance Report';
$conn = getConnection();
requireAnyRole(['SuperAdmin', 'Admin', 'SSOStaff', 'Teacher']);

$report_data = [];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Get courses
$courses = getAllRecords("SELECT course_id, course_code, course_title FROM courses WHERE status = 'Active'");

// Get students
if ($course_id > 0) {
    $students = getAllRecords("
        SELECT DISTINCT s.student_id, s.full_name, s.roll_no 
        FROM students s
        JOIN student_fee sf ON s.student_id = sf.student_id
        WHERE s.status = 'Active'
        ORDER BY s.full_name
    ");
}

// Get report data
if ($course_id > 0 && ($student_id > 0 || isset($_GET['show_all']))) {
    $params = [$course_id, $start_date, $end_date];
    $types = "iss";
    $where = "a.course_id = ? AND a.class_date BETWEEN ? AND ?";
    
    if ($student_id > 0) {
        $where .= " AND a.student_id = ?";
        $params[] = $student_id;
        $types .= "i";
    }
    
    $report_data = getAllRecords("
        SELECT 
            a.student_id,
            s.full_name,
            s.roll_no,
            COUNT(*) as total_classes,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'Leave' THEN 1 ELSE 0 END) as leave_count,
            ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as percentage
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        WHERE $where
        GROUP BY a.student_id
        ORDER BY percentage DESC
    ", $params, $types);
}
?>
<?php include '../../15. Includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar"></i> Attendance Report</h5>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>"
                                <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>" required>
                </div>
                <div class="col-md-2">
                    <select name="student_id" class="form-control">
                        <option value="0">All Students</option>
                        <?php if (isset($students)): ?>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>"
                                    <?php echo $student_id == $student['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <?php if (!empty($report_data)): ?>
                        <button type="button" class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Report Results -->
            <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Total Classes</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Leave</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; foreach($report_data as $data): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($data['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($data['roll_no'] ?: 'N/A'); ?></td>
                                <td><?php echo $data['total_classes']; ?></td>
                                <td class="text-success"><?php echo $data['present_count']; ?></td>
                                <td class="text-danger"><?php echo $data['absent_count']; ?></td>
                                <td class="text-warning"><?php echo $data['leave_count']; ?></td>
                                <td>
                                    <strong><?php echo $data['percentage']; ?>%</strong>
                                </td>
                                <td>
                                    <?php 
                                    $percent = $data['percentage'];
                                    if ($percent >= 80) {
                                        echo '<span class="badge bg-success">Excellent</span>';
                                    } elseif ($percent >= 70) {
                                        echo '<span class="badge bg-info">Good</span>';
                                    } elseif ($percent >= 60) {
                                        echo '<span class="badge bg-warning">Satisfactory</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Needs Improvement</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h6>Total Students</h6>
                            <h3><?php echo count($report_data); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h6>Average Attendance</h6>
                            <h3>
                                <?php 
                                $avg = array_sum(array_column($report_data, 'percentage')) / count($report_data);
                                echo round($avg, 2) . '%';
                                ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h6>Highest Attendance</h6>
                            <h3><?php echo max(array_column($report_data, 'percentage')) . '%'; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h6>Lowest Attendance</h6>
                            <h3><?php echo min(array_column($report_data, 'percentage')) . '%'; ?></h3>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['course_id']) && $_GET['course_id'] > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No attendance records found for the selected criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../15. Includes/footer.php'; ?>