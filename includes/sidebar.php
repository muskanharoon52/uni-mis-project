<?php
// ============================================
// SIDEBAR - Without Admin User Display
// ============================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$user = getCurrentUser();

// Determine active menu
function isActiveMenu($dirs, $pages = []) {
    global $current_dir, $current_page;
    
    if (!is_array($dirs)) {
        $dirs = [$dirs];
    }
    if (!is_array($pages)) {
        $pages = [$pages];
    }
    
    foreach ($dirs as $dir) {
        if ($current_dir == $dir) {
            return true;
        }
    }
    
    foreach ($pages as $page) {
        if ($current_page == $page) {
            return true;
        }
    }
    return false;
}
?>

<style>
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        background: #1a1a2e;
        color: #ecf0f1;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1000;
        transition: all 0.3s ease;
        box-shadow: 2px 0 10px rgba(0,0,0,0.3);
    }
    
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    
    .sidebar::-webkit-scrollbar-track {
        background: #16213e;
    }
    
    .sidebar::-webkit-scrollbar-thumb {
        background: #0f3460;
        border-radius: 10px;
    }
    
    .sidebar-brand {
        padding: 20px 15px 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        text-align: center;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    }
    
    .sidebar-brand .logo-icon {
        font-size: 36px;
        color: #e94560;
        display: block;
        margin-bottom: 5px;
    }
    
    .sidebar-brand h4 {
        margin: 0;
        font-weight: 700;
        color: #ecf0f1;
        font-size: 18px;
        letter-spacing: 0.5px;
    }
    
    .sidebar-brand h4 span {
        color: #e94560;
    }
    
    .sidebar-brand small {
        display: block;
        margin-top: 3px;
        color: #95a5a6;
        font-size: 11px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    
    /* User info removed - only SSO Office display */
    
    .sidebar-menu {
        padding: 10px 0 20px;
    }
    
    .menu-section {
        padding: 12px 20px 6px;
        font-size: 10px;
        text-transform: uppercase;
        color: #6c7a89;
        letter-spacing: 1.5px;
        font-weight: 700;
        opacity: 0.8;
    }
    
    .menu-item {
        display: flex;
        align-items: center;
        padding: 11px 20px;
        color: #bdc3c7;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        font-size: 14px;
        font-weight: 400;
        cursor: pointer;
        position: relative;
        margin: 2px 0;
    }
    
    .menu-item:hover {
        background: rgba(255,255,255,0.05);
        color: #ecf0f1;
        text-decoration: none;
        border-left-color: #e94560;
    }
    
    .menu-item.active {
        background: rgba(233, 69, 96, 0.1);
        color: #ecf0f1;
        border-left-color: #e94560;
    }
    
    .menu-item.active i {
        color: #e94560;
    }
    
    .menu-item i {
        width: 22px;
        margin-right: 14px;
        font-size: 16px;
        text-align: center;
        color: #6c7a89;
        transition: all 0.3s ease;
    }
    
    .menu-item:hover i,
    .menu-item.active i {
        color: #e94560;
    }
    
    .menu-item .badge {
        margin-left: auto;
        background: #e94560;
        color: #fff;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        min-width: 20px;
        text-align: center;
    }
    
    .menu-item.logout-item {
        color: #e94560;
        border-top: 1px solid rgba(255,255,255,0.05);
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .menu-item.logout-item i {
        color: #e94560;
    }
    
    .menu-item.logout-item:hover {
        background: rgba(233, 69, 96, 0.15);
        color: #e94560;
    }
    
    .menu-item.logout-item:hover i {
        color: #e94560;
    }
    
    /* Mobile Toggle */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: #1a1a2e;
        color: #ecf0f1;
        border: none;
        padding: 10px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
        background: #16213e;
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        z-index: 999;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .sidebar-toggle {
            display: block;
        }
        
        .content-wrapper {
            margin-left: 0 !important;
        }
    }
    
    @media (max-width: 576px) {
        .sidebar {
            width: 280px;
        }
    }
</style>

<!-- Sidebar Toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="logo-icon">
            <i class="fas fa-university"></i>
        </span>
        <h4>Student <span>Services</span></h4>
        <small>SSO - Office</small>
        <!-- Admin User info removed from here -->
    </div>
    
    <div class="sidebar-menu">
        <!-- Dashboard -->
        <div class="menu-section">Main</div>
        <a href="/MIS/Dashboard.php" 
           class="menu-item <?php echo isActiveMenu('Dashboard', 'Dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <!-- Students -->
        <div class="menu-section">Students</div>
        <a href="/MIS/Students/index.php" 
           class="menu-item <?php echo isActiveMenu('Students') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> Student List
        </a>
        
        <!-- Academics -->
        <div class="menu-section">Academics</div>
        <a href="/MIS/Courses/index.php" 
           class="menu-item <?php echo isActiveMenu('Courses') ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Courses
        </a>
        <a href="/MIS/Semester_Courses/index.php" 
           class="menu-item <?php echo isActiveMenu('SemesterCourses') ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i> Semester Courses
        </a>
        <a href="/MIS/Teacher_Assignment/index.php" 
           class="menu-item <?php echo isActiveMenu('TeacherAssignment') ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i> Teacher Assignment
        </a>
        <a href="/MIS/Timetable/index.php" 
           class="menu-item <?php echo isActiveMenu('Timetable') ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Timetable
        </a>
        <a href="/MIS/Roll_Numbers/index.php" 
           class="menu-item <?php echo isActiveMenu('RollNumber') ? 'active' : ''; ?>">
            <i class="fas fa-hashtag"></i> Roll Number
        </a>
        
        <!-- Tracking -->
        <div class="menu-section">Tracking</div>
        <a href="/MIS/Attendance/index.php" 
           class="menu-item <?php echo isActiveMenu('Attendance') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>
        
        <!-- Account -->
        <div class="menu-section">Account</div>
        <a href="/MIS/Authentication/logout.php" 
           class="menu-item logout-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
// Mobile toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
        if (overlay) {
            overlay.classList.toggle('active');
        }
    }
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
});
</script>