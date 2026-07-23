<?php
// includes/sidebar.php - Navigation Sidebar

// Make sure BASE_URL is defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/university_mis/');
}
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-university"></i> SSO Panel</h4>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-user-circle"></i>
        <span class="name"><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
        <span class="role"><?php echo ucfirst($_SESSION['role_name'] ?? 'sso'); ?></span>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'students' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>students/index.php">
                    <i class="fas fa-users"></i> Students
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'student_enrollment' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>student_enrollment/index.php">
                    <i class="fas fa-user-graduate"></i> Student Enrollment
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'Courses' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>Courses/index.php">
                    <i class="fas fa-book"></i> Courses
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'fee_per_course' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>fee_per_course/index.php">
                    <i class="fas fa-money-bill-wave"></i> Fee Per Course
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'semester_courses' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>semester_courses/index.php">
                    <i class="fas fa-layer-group"></i> Semester Courses
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'teacher_assignment' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>teacher_assignment/index.php">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher Assignment
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'Timetable' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>Timetable/index.php">
                    <i class="fas fa-calendar-alt"></i> Timetable
                </a>
            </li>
         
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'attendance' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>attendance/index.php">
                    <i class="fas fa-clipboard-check"></i> Attendance
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'applications' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>applications/index.php">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>reports/index.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
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
}

.sidebar-brand {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand h4 {
    margin: 0;
    font-weight: 700;
}

.sidebar-user {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-user i {
    font-size: 48px;
    display: block;
    margin-bottom: 5px;
    color: #a8a8b3;
}

.sidebar-user .name {
    display: block;
    font-weight: 600;
}

.sidebar-user .role {
    display: block;
    font-size: 12px;
    color: #a8a8b3;
    text-transform: uppercase;
}

.sidebar-nav {
    padding: 10px 0;
}

.sidebar-nav .nav-link {
    color: #a8a8b3;
    padding: 12px 20px;
    border-radius: 0;
    transition: all 0.3s;
}

.sidebar-nav .nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.sidebar-nav .nav-link.active {
    color: white;
    background: rgba(102, 126, 234, 0.3);
    border-left: 3px solid #667eea;
}

.sidebar-nav .nav-link i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

.sidebar-nav .nav-link.text-danger:hover {
    background: rgba(220, 53, 69, 0.2);
}

/* Main content adjustment */
.main-content {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background: #f0f2f5;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-bottom: 10px;
    }
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
}
</style>