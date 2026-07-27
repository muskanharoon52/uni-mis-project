<?php
// includes/sidebar.php - Navigation Sidebar (FIXED - Fee Management & Reports REMOVED)

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/uni-mis-project/');
}
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">
        <i class="fas fa-university fa-2x mb-2"></i>
        <h4>University MIS</h4>
        <small>SSO Module</small>
    </div>

    <div class="text-center text-white-50 mb-3" style="padding: 10px;">
        <i class="fas fa-user-circle fa-3x"></i>
        <div class="fw-bold mt-1"><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
        <small><?php echo ucfirst($_SESSION['role_name'] ?? 'sso'); ?></small>
    </div>

    <nav class="nav flex-column">
        <!-- Dashboard -->
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>

        <!-- Student Management -->
        <a href="<?php echo BASE_URL; ?>students/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'students' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Students
        </a>

        <!-- Student Enrollment -->
        <a href="<?php echo BASE_URL; ?>student_enrollment/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'student_enrollment' ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> Enrollment
        </a>

        <!-- Courses -->
        <a href="<?php echo BASE_URL; ?>Courses/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'Courses' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Courses
        </a>

        <!-- FEE MANAGEMENT - REMOVED -->
        <!-- 
        <a href="<?php echo BASE_URL; ?>fee_management/index.php" class="nav-link">
            <i class="fas fa-money-bill-wave"></i> Fee Management
        </a>
        -->

        <!-- Semester Courses -->
        <a href="<?php echo BASE_URL; ?>semester_courses/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'semester_courses' ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i> Semester Courses
        </a>

        <!-- Teacher Assignment -->
        <a href="<?php echo BASE_URL; ?>teacher_assignment/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'teacher_assignment' ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>

        <!-- Timetable -->
        <a href="<?php echo BASE_URL; ?>Timetable/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'Timetable' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>

        <!-- Attendance -->
        <a href="<?php echo BASE_URL; ?>attendance/index.php" class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'attendance' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>

        <!-- Admission Applications -->
        <a href="<?php echo BASE_URL; ?>applications/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'applications.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'applications') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Admission Apps
        </a>

        <!-- LMS Applications -->
        <a href="<?php echo BASE_URL; ?>lms_applications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lms_applications.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i> LMS Applications
        </a>

        <!-- REPORTS - REMOVED -->
        <!-- 
        <a href="<?php echo BASE_URL; ?>reports/index.php" class="nav-link">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        -->

        <hr style="border-color: rgba(255,255,255,0.1);">

        <!-- Logout -->
        <a href="<?php echo BASE_URL; ?>modules/sso/logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    padding-bottom: 20px;
    z-index: 1000;
    transition: all 0.3s;
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

.sidebar .brand {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar .brand h4 {
    font-weight: 700;
    margin: 0;
    letter-spacing: 1px;
}

.sidebar .brand small {
    color: rgba(255,255,255,0.5);
    font-size: 12px;
    letter-spacing: 2px;
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.6);
    padding: 12px 22px;
    border-radius: 0;
    transition: all 0.3s;
    font-size: 14px;
    border-left: 3px solid transparent;
    text-decoration: none;
    display: block;
}

.sidebar .nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.sidebar .nav-link.active {
    color: white;
    background: rgba(102, 126, 234, 0.2);
    border-left-color: #667eea;
}

.sidebar .nav-link i {
    width: 22px;
    margin-right: 12px;
    text-align: center;
    font-size: 15px;
}

.sidebar .nav-link.text-danger:hover {
    background: rgba(220, 53, 69, 0.15);
}

.sidebar hr {
    border-color: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-bottom: 10px;
    }
}

/* For main content when sidebar is fixed */
.main-content {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background: #f0f2f5;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
}
</style>