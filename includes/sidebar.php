<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-university"></i> SSO Panel</h4>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-user-circle"></i>
        <span class="name"><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
        <span class="role"><?php echo ucfirst($_SESSION['role'] ?? 'sso'); ?></span>
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

            <!-- Student Enrollment -->
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

            <!-- Fee Per Course -->
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