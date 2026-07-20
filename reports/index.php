<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireSSO();

// Get statistics for reports
$total_students = getRow("SELECT COUNT(*) as count FROM students")['count'] ?? 0;
$total_courses = getRow("SELECT COUNT(*) as count FROM courses")['count'] ?? 0;
$total_faculty = getRow("SELECT COUNT(*) as count FROM faculty")['count'] ?? 0;
$total_applications = getRow("SELECT COUNT(*) as count FROM applications")['count'] ?? 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h2><i class="fas fa-chart-bar"></i> Reports</h2>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h5>Students Report</h5>
                        <p class="text-muted">Total: <?php echo $total_students; ?></p>
                        <a href="../students/index.php" class="btn btn-sm btn-primary">View Report</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-book fa-3x text-success mb-3"></i>
                        <h5>Courses Report</h5>
                        <p class="text-muted">Total: <?php echo $total_courses; ?></p>
                        <a href="../Courses/index.php" class="btn btn-sm btn-success">View Report</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x text-info mb-3"></i>
                        <h5>Faculty Report</h5>
                        <p class="text-muted">Total: <?php echo $total_faculty; ?></p>
                        <a href="../teacher_assignment/index.php" class="btn btn-sm btn-info">View Report</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                        <h5>Applications Report</h5>
                        <p class="text-muted">Total: <?php echo $total_applications; ?></p>
                        <a href="../applications/index.php" class="btn btn-sm btn-warning">View Report</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Attendance Report</h5>
                    </div>
                    <div class="card-body">
                        <p>Generate attendance report by course and date range.</p>
                        <a href="../attendance/report.php" class="btn btn-primary">Generate Attendance Report</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Export Data</h5>
                    </div>
                    <div class="card-body">
                        <p>Export student data to Excel/CSV format.</p>
                        <a href="../students/export.php" class="btn btn-success">Export Students</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>