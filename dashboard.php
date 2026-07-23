<?php
// dashboard.php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

// ============================================
// GET STATS - Simple Queries
// ============================================

$conn = getConnection();

// Total Students
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
$stats['students'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Courses
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses");
$stats['courses'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Teachers
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers");
$stats['teachers'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Applications
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM applications");
$stats['applications'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Fee Records
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_records");
$stats['fee_records'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Sections
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM sections");
$stats['sections'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Enrollments
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM student_enrollments");
$stats['enrollments'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-content { margin-left: 250px; padding: 20px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-card .number { font-size: 28px; font-weight: 700; margin: 0; }
        .stat-card .label { color: #666; font-size: 14px; }
        .topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        
        /* Sidebar Styles */
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; overflow-y: auto; padding-bottom: 20px; z-index: 1000; }
        .sidebar .brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .brand h4 { font-weight: 700; margin: 0; }
        .sidebar .brand small { color: #a8a8b3; }
        .sidebar .nav-link { color: #a8a8b3; padding: 12px 20px; border-radius: 0; transition: all 0.3s; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active { color: white; background: rgba(102, 126, 234, 0.3); border-left: 3px solid #667eea; }
        .sidebar .nav-link i { width: 20px; margin-right: 10px; }
        .sidebar .nav-link.text-danger:hover { background: rgba(220, 53, 69, 0.2); }
        @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- SIDEBAR - Included Directly -->
<!-- ============================================ -->
<div class="sidebar">
    <div class="brand">
        <i class="fas fa-university fa-2x mb-2"></i>
        <h4>University MIS</h4>
        <small>SSO Module</small>
    </div>
    
    <div class="text-center text-white-50 mb-3" style="padding: 10px;">
        <i class="fas fa-user-circle fa-3x"></i>
        <div class="fw-bold mt-1"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></div>
        <small><?php echo ucfirst($role); ?></small>
    </div>
    
    <nav class="nav flex-column">
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="nav-link active">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>students/index.php" class="nav-link">
            <i class="fas fa-users"></i> Students
        </a>
        <a href="<?php echo BASE_URL; ?>student_enrollment/index.php" class="nav-link">
            <i class="fas fa-user-graduate"></i> Enrollment
        </a>
        <a href="<?php echo BASE_URL; ?>Courses/index.php" class="nav-link">
            <i class="fas fa-book"></i> Courses
        </a>
        <a href="<?php echo BASE_URL; ?>fee_per_course/index.php" class="nav-link">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>
        <a href="<?php echo BASE_URL; ?>semester_courses/index.php" class="nav-link">
            <i class="fas fa-layer-group"></i> Semester Courses
        </a>
        <a href="<?php echo BASE_URL; ?>teacher_assignment/index.php" class="nav-link">
            <i class="fas fa-chalkboard-teacher"></i> Teacher Assignment
        </a>
        <a href="<?php echo BASE_URL; ?>Timetable/index.php" class="nav-link">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a href="<?php echo BASE_URL; ?>attendance/index.php" class="nav-link">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>
        <a href="<?php echo BASE_URL; ?>applications/index.php" class="nav-link">
            <i class="fas fa-file-alt"></i> Applications
        </a>
        <a href="<?php echo BASE_URL; ?>reports/index.php" class="nav-link">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <hr style="border-color: rgba(255,255,255,0.1);">
        <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <span class="fw-bold">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
            <span class="badge bg-primary ms-2"><?php echo ucfirst($role); ?></span>
        </div>
        <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['students']; ?></p>
                        <p class="label">Total Students</p>
                    </div>
                    <div class="icon" style="background: #e3f2fd; color: #1976d2;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['courses']; ?></p>
                        <p class="label">Total Courses</p>
                    </div>
                    <div class="icon" style="background: #e8f5e9; color: #388e3c;">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['teachers']; ?></p>
                        <p class="label">Total Teachers</p>
                    </div>
                    <div class="icon" style="background: #fff3e0; color: #f57c00;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['applications']; ?></p>
                        <p class="label">Applications</p>
                    </div>
                    <div class="icon" style="background: #fce4ec; color: #c62828;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['fee_records'] ?? 0; ?></p>
                        <p class="label">Fee Records</p>
                    </div>
                    <div class="icon" style="background: #e8eaf6; color: #3949ab;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['sections'] ?? 0; ?></p>
                        <p class="label">Sections</p>
                    </div>
                    <div class="icon" style="background: #f3e5f5; color: #7b1fa2;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="number"><?php echo $stats['enrollments'] ?? 0; ?></p>
                        <p class="label">Enrollments</p>
                    </div>
                    <div class="icon" style="background: #e0f7fa; color: #00838f;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Welcome to the SSO Module Dashboard. Use the sidebar to navigate.
    </div>
</div>

</body>
</html>