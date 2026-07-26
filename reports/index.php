<?php
// reports/index.php - Reports Dashboard

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';
$conn = getConnection();

// ============================================
// GET STATS FOR REPORTS - WITH ERROR HANDLING
// ============================================

// Total Students
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
$total_students = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Courses
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses");
$total_courses = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Teachers
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers");
$total_teachers = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Applications
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM applications");
$total_applications = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Fee Records
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_records");
$total_fee_records = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Attendance
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance");
$total_attendance = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Recent Students
$recent_query = "SELECT s.student_id, s.roll_no, u.full_name, s.enrollment_date 
                 FROM students s 
                 LEFT JOIN users u ON s.user_id = u.user_id 
                 ORDER BY s.enrollment_date DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);
$recent_students = [];
if ($recent_result) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recent_students[] = $row;
    }
}

// Recent Applications
$app_query = "SELECT a.*, s.full_name as student_name 
              FROM applications a 
              LEFT JOIN students s ON a.student_id = s.student_id 
              ORDER BY a.created_at DESC LIMIT 5";
$app_result = mysqli_query($conn, $app_query);
$recent_applications = [];
if ($app_result) {
    while ($row = mysqli_fetch_assoc($app_result)) {
        $recent_applications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-content { margin-left: 250px; padding: 20px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card .number { font-size: 28px; font-weight: 700; }
        .stat-card .label { color: #666; font-size: 14px; }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .report-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .report-card .report-icon { font-size: 36px; margin-bottom: 10px; }
        .btn-report { border-radius: 20px; padding: 8px 25px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- SIDEBAR INCLUDED FROM includes/sidebar.php   -->
<!-- ============================================ -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- ============================================ -->
<!-- MAIN CONTENT                                 -->
<!-- ============================================ -->
<div class="main-content">
    <div class="topbar">
        <div>
            <span class="fw-bold">Reports Dashboard</span>
            <span class="badge bg-primary ms-2"><?php echo ucfirst($role); ?></span>
        </div>
        <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><p class="number"><?php echo $total_students; ?></p><p class="label">Students</p></div>
                    <div class="icon" style="background: #e3f2fd; color: #1976d2;"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><p class="number"><?php echo $total_courses; ?></p><p class="label">Courses</p></div>
                    <div class="icon" style="background: #e8f5e9; color: #388e3c;"><i class="fas fa-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><p class="number"><?php echo $total_teachers; ?></p><p class="label">Teachers</p></div>
                    <div class="icon" style="background: #fff3e0; color: #f57c00;"><i class="fas fa-chalkboard-teacher"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><p class="number"><?php echo $total_applications; ?></p><p class="label">Applications</p></div>
                    <div class="icon" style="background: #fce4ec; color: #c62828;"><i class="fas fa-file-alt"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Cards -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-primary"><i class="fas fa-users"></i></div>
                <h5>Student Report</h5>
                <p class="text-muted small">View and export student data</p>
                <a href="<?php echo BASE_URL; ?>students/export.php" class="btn btn-primary btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-success"><i class="fas fa-money-bill-wave"></i></div>
                <h5>Fee Report</h5>
                <p class="text-muted small">View fee collection status</p>
                <a href="<?php echo BASE_URL; ?>fee_per_course/report.php" class="btn btn-success btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-warning"><i class="fas fa-clipboard-check"></i></div>
                <h5>Attendance Report</h5>
                <p class="text-muted small">View attendance summary</p>
                <a href="<?php echo BASE_URL; ?>attendance/report.php" class="btn btn-warning btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-info"><i class="fas fa-file-alt"></i></div>
                <h5>Applications Report</h5>
                <p class="text-muted small">View application status</p>
                <a href="<?php echo BASE_URL; ?>applications/index.php" class="btn btn-info btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-danger"><i class="fas fa-calendar-alt"></i></div>
                <h5>Timetable Report</h5>
                <p class="text-muted small">View class schedules</p>
                <a href="<?php echo BASE_URL; ?>Timetable/index.php" class="btn btn-danger btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card text-center">
                <div class="report-icon text-secondary"><i class="fas fa-user-graduate"></i></div>
                <h5>Enrollment Report</h5>
                <p class="text-muted small">View student enrollments</p>
                <a href="<?php echo BASE_URL; ?>student_enrollment/index.php" class="btn btn-secondary btn-report">
                    <i class="fas fa-eye"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="report-card">
                <h5><i class="fas fa-user-graduate text-primary"></i> Recent Students</h5>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Student ID</th><th>Name</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if (!empty($recent_students)): ?>
                        <?php foreach ($recent_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No students found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="report-card">
                <h5><i class="fas fa-file-alt text-success"></i> Recent Applications</h5>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Student</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (!empty($recent_applications)): ?>
                        <?php foreach ($recent_applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['student_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($app['application_type'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo ($app['status'] ?? 'Pending') == 'Approved' ? 'bg-success' : (($app['status'] ?? 'Pending') == 'Rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                    <?php echo $app['status'] ?? 'Pending'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No applications found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>