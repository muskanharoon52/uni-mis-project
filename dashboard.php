<?php
// dashboard.php - ULTIMATE DASHBOARD

session_start();
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/modules/sso/includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: modules/sso/login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';
$conn = getConnection();

// ============================================
// GET ALL STATS
// ============================================

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

// Today's Stats
$today = date('Y-m-d');
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today'");
$stats['attendance_today'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE enrollment_date = '$today'");
$stats['new_students_today'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM applications WHERE DATE(created_at) = '$today'");
$stats['new_apps_today'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Total Fee Collected
$result = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_records");
$stats['total_fee'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Pending Applications
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'");
$stats['pending_apps'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

// Recent Students (Last 5)
$recent_query = "SELECT s.student_id, s.roll_no, u.full_name, s.enrollment_date, s.status 
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

// Recent Applications (Last 5)
$app_query = "SELECT a.*, s.full_name as student_name 
              FROM applications a 
              LEFT JOIN students s ON a.student_id = s.student_id 
              ORDER BY a.created_at DESC LIMIT 5";
$app_result = mysqli_query($conn, $app_query);
$recent_apps = [];
if ($app_result) {
    while ($row = mysqli_fetch_assoc($app_result)) {
        $recent_apps[] = $row;
    }
}

// Attendance Chart Data (Last 7 Days)
$chart_labels = [];
$chart_present = [];
$chart_absent = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($date));
    
    $p_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$date' AND status = 'Present'");
    $p_count = ($p_result && mysqli_num_rows($p_result) > 0) ? mysqli_fetch_assoc($p_result)['count'] : 0;
    $chart_present[] = $p_count;
    
    $a_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$date' AND status = 'Absent'");
    $a_count = ($a_result && mysqli_num_rows($a_result) > 0) ? mysqli_fetch_assoc($a_result)['count'] : 0;
    $chart_absent[] = $a_count;
}

// Today's Attendance Status
$today_present = 0;
$today_absent = 0;
$today_late = 0;
$p_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Present'");
if ($p_result) $today_present = mysqli_fetch_assoc($p_result)['count'] ?? 0;
$a_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Absent'");
if ($a_result) $today_absent = mysqli_fetch_assoc($a_result)['count'] ?? 0;
$l_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Late'");
if ($l_result) $today_late = mysqli_fetch_assoc($l_result)['count'] ?? 0;

// Fee Collection Stats
$fee_paid = $stats['total_fee'] ?? 0;
$fee_total = mysqli_query($conn, "SELECT SUM(total_fee) as total FROM fee_records");
$fee_total = ($fee_total && mysqli_num_rows($fee_total) > 0) ? mysqli_fetch_assoc($fee_total)['total'] : 0;
$fee_remaining = $fee_total - $fee_paid;

// Top Courses (by enrollment)
$top_courses = [];
$top_query = "SELECT c.course_code, c.course_name, COUNT(sc.student_id) as enrolled 
              FROM student_courses sc
              JOIN courses c ON sc.course_id = c.course_id
              GROUP BY c.course_id
              ORDER BY enrolled DESC LIMIT 5";
$top_result = mysqli_query($conn, $top_query);
if ($top_result) {
    while ($row = mysqli_fetch_assoc($top_result)) {
        $top_courses[] = $row;
    }
}

// ============================================
// INCLUDE HEADER
// ============================================
include __DIR__ . '/includes/header.php';
$page_title = 'Dashboard';
include __DIR__ . '/includes/sidebar.php';
?>

<style>
    /* ============================================
       ROOT VARIABLES
       ============================================ */
    :root {
        --bg-color: #f0f2f5;
        --card-bg: #ffffff;
        --text-color: #2c3e50;
        --text-secondary: #7f8c8d;
        --border-color: #e9ecef;
        --shadow: 0 2px 15px rgba(0,0,0,0.05);
        --shadow-hover: 0 8px 30px rgba(0,0,0,0.12);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Dark Mode */
    [data-theme="dark"] {
        --bg-color: #1a1a2e;
        --card-bg: #16213e;
        --text-color: #ecf0f1;
        --text-secondary: #a8a8b3;
        --border-color: #2c3e50;
        --shadow: 0 2px 15px rgba(0,0,0,0.3);
        --shadow-hover: 0 8px 30px rgba(0,0,0,0.5);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        background: var(--bg-color); 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: var(--transition);
        color: var(--text-color);
    }
    
    .main-content { 
        margin-left: 250px; 
        padding: 20px; 
        transition: var(--transition);
        min-height: 100vh;
    }
    
    /* ============================================
       TOPBAR
       ============================================ */
    .topbar { 
        background: var(--card-bg); padding: 15px 25px; 
        border-radius: 16px; margin-bottom: 25px; 
        box-shadow: var(--shadow); 
        display: flex; justify-content: space-between; align-items: center;
        transition: var(--transition);
        border: 1px solid var(--border-color);
    }
    .topbar .avatar { 
        width: 42px; height: 42px; border-radius: 50%; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; display: flex; align-items: center; 
        justify-content: center; font-weight: 700; font-size: 16px;
        cursor: pointer;
    }
    .welcome-text { font-size: 22px; font-weight: 700; color: var(--text-color); }
    .welcome-text small { font-weight: 400; color: var(--text-secondary); font-size: 14px; }
    
    /* Dark Mode Toggle */
    .theme-toggle {
        width: 42px; height: 42px; border-radius: 50%;
        background: var(--bg-color); border: 1px solid var(--border-color);
        color: var(--text-color); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .theme-toggle:hover { transform: rotate(30deg); }
    
    /* ============================================
       STATS CARDS
       ============================================ */
    .stat-card {
        background: var(--card-bg); border-radius: 16px; padding: 22px 25px;
        box-shadow: var(--shadow); border: 1px solid var(--border-color);
        transition: var(--transition); position: relative; overflow: hidden;
        color: var(--text-color);
    }
    .stat-card:hover { 
        transform: translateY(-6px); 
        box-shadow: var(--shadow-hover);
    }
    .stat-card .number { font-size: 32px; font-weight: 800; letter-spacing: -1px; }
    .stat-card .label { font-size: 14px; color: var(--text-secondary); margin-top: 2px; }
    .stat-card .icon-wrap {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
    }
    .stat-card .trend {
        font-size: 12px; padding: 2px 12px; border-radius: 20px;
        display: inline-block; margin-top: 6px; font-weight: 600;
    }
    .trend-up { background: #d4edda; color: #155724; }
    .trend-down { background: #f8d7da; color: #721c24; }
    
    /* Gradient Variations */
    .stat-card .icon-wrap.blue { background: rgba(79, 172, 254, 0.15); color: #4facfe; }
    .stat-card .icon-wrap.green { background: rgba(67, 233, 123, 0.15); color: #43e97b; }
    .stat-card .icon-wrap.orange { background: rgba(245, 87, 108, 0.15); color: #f5576c; }
    .stat-card .icon-wrap.purple { background: rgba(161, 140, 209, 0.15); color: #a18cd1; }
    .stat-card .icon-wrap.teal { background: rgba(8, 174, 234, 0.15); color: #08aeea; }
    .stat-card .icon-wrap.pink { background: rgba(240, 147, 251, 0.15); color: #f093fb; }
    .stat-card .icon-wrap.gold { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    
    /* ============================================
       CARDS
       ============================================ */
    .card-custom {
        background: var(--card-bg); border-radius: 16px; padding: 20px 25px;
        box-shadow: var(--shadow); border: 1px solid var(--border-color);
        transition: var(--transition);
        color: var(--text-color);
    }
    .card-custom:hover { box-shadow: var(--shadow-hover); }
    .card-custom .card-title { 
        font-weight: 700; font-size: 16px; 
        color: var(--text-color); margin-bottom: 18px;
    }
    
    .activity-item { 
        padding: 12px 0; border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 15px;
        transition: var(--transition);
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-item:hover { background: var(--bg-color); margin: 0 -10px; padding: 12px 10px; border-radius: 8px; }
    .activity-item .activity-icon {
        width: 42px; height: 42px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }
    .activity-item .activity-text { flex: 1; }
    .activity-item .activity-text .title { font-weight: 600; color: var(--text-color); }
    .activity-item .activity-text .time { font-size: 12px; color: var(--text-secondary); }
    
    .status-badge { padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-active { background: #d4edda; color: #155724; }
    
    /* Quick Actions */
    .quick-action {
        display: inline-flex; flex-direction: column; align-items: center;
        padding: 15px 18px; background: var(--bg-color); 
        border-radius: 12px; text-decoration: none; color: var(--text-color);
        transition: var(--transition); border: 1px solid var(--border-color);
        min-width: 90px; font-size: 12px; font-weight: 500;
    }
    .quick-action:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; transform: translateY(-4px); 
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
        border-color: transparent;
    }
    .quick-action i { font-size: 22px; margin-bottom: 6px; }
    
    /* Chart container */
    .chart-container { height: 200px; position: relative; }
    
    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 768px) {
        .main-content { margin-left: 0; padding: 15px; }
        .stat-card .number { font-size: 24px; }
        .welcome-text { font-size: 18px; }
        .quick-action { min-width: 70px; padding: 12px 12px; font-size: 11px; }
        .quick-action i { font-size: 18px; }
    }
    @media (max-width: 576px) {
        .topbar { flex-wrap: wrap; gap: 10px; }
        .welcome-text { font-size: 16px; }
        .stat-card { padding: 15px; }
        .stat-card .number { font-size: 20px; }
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeInUp 0.5s ease forwards;
    }
    .animate-in:nth-child(2) { animation-delay: 0.1s; }
    .animate-in:nth-child(3) { animation-delay: 0.2s; }
    .animate-in:nth-child(4) { animation-delay: 0.3s; }
    .animate-in:nth-child(5) { animation-delay: 0.4s; }
    .animate-in:nth-child(6) { animation-delay: 0.5s; }
</style>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <span class="welcome-text">
                👋 Welcome back, <span style="color:#667eea;"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
                <small>· <?php echo date('l, d F Y'); ?></small>
            </span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark" style="padding:6px 14px;">
                <i class="fas fa-clock"></i> <?php echo date('h:i A'); ?>
            </span>
            <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            <span class="badge bg-danger" style="padding:6px 14px; cursor:pointer;" title="Pending Applications">
                <i class="fas fa-bell"></i> <?php echo $stats['pending_apps']; ?>
            </span>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- STATS CARDS - Row 1 -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-6 animate-in">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number" id="countStudents">0</div>
                        <div class="label">Total Students</div>
                        <span class="trend trend-up"><i class="fas fa-arrow-up"></i> 12%</span>
                    </div>
                    <div class="icon-wrap blue"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6 animate-in">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number" id="countCourses">0</div>
                        <div class="label">Total Courses</div>
                        <span class="trend trend-up"><i class="fas fa-arrow-up"></i> 5%</span>
                    </div>
                    <div class="icon-wrap green"><i class="fas fa-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6 animate-in">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number" id="countTeachers">0</div>
                        <div class="label">Total Teachers</div>
                        <span class="trend trend-up"><i class="fas fa-arrow-up"></i> 8%</span>
                    </div>
                    <div class="icon-wrap orange"><i class="fas fa-chalkboard-teacher"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6 animate-in">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number" id="countApps">0</div>
                        <div class="label">Applications</div>
                        <span class="trend trend-up"><i class="fas fa-arrow-up"></i> 15%</span>
                    </div>
                    <div class="icon-wrap purple"><i class="fas fa-file-alt"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- STATS CARDS - Row 2 -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number"><?php echo $stats['fee_records'] ?? 0; ?></div>
                        <div class="label">Fee Records</div>
                    </div>
                    <div class="icon-wrap teal"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number"><?php echo $stats['sections'] ?? 0; ?></div>
                        <div class="label">Sections</div>
                    </div>
                    <div class="icon-wrap gold"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number"><?php echo $stats['enrollments'] ?? 0; ?></div>
                        <div class="label">Enrollments</div>
                    </div>
                    <div class="icon-wrap pink"><i class="fas fa-user-plus"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="number"><?php echo $stats['attendance_today']; ?></div>
                        <div class="label">Attendance Today</div>
                    </div>
                    <div class="icon-wrap blue"><i class="fas fa-clipboard-check"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CHARTS + QUICK ACTIONS -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <div class="col-xl-7 col-lg-12">
            <div class="card-custom">
                <div class="card-title"><i class="fas fa-chart-line text-primary"></i> Attendance Trend (Last 7 Days)</div>
                <div class="chart-container">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-12">
            <div class="card-custom" style="height:100%;">
                <div class="card-title"><i class="fas fa-bolt text-warning"></i> Quick Actions</div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo BASE_URL; ?>students/add.php" class="quick-action">
                        <i class="fas fa-user-plus"></i> Add Student
                    </a>
                    <a href="<?php echo BASE_URL; ?>attendance/take.php" class="quick-action">
                        <i class="fas fa-clipboard-check"></i> Attendance
                    </a>
                    <a href="<?php echo BASE_URL; ?>fee_management/payment.php" class="quick-action">
                        <i class="fas fa-money-bill-wave"></i> Payment
                    </a>
                    <a href="<?php echo BASE_URL; ?>applications/index.php" class="quick-action">
                        <i class="fas fa-file-alt"></i> Applications
                    </a>
                    <a href="<?php echo BASE_URL; ?>reports/index.php" class="quick-action">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="<?php echo BASE_URL; ?>teacher_assignment/add_teacher.php" class="quick-action">
                        <i class="fas fa-user-tie"></i> Add Teacher
                    </a>
                    <a href="<?php echo BASE_URL; ?>fee_management/structure_add.php" class="quick-action">
                        <i class="fas fa-money-bill-wave"></i> Fee Structure
                    </a>
                    <a href="<?php echo BASE_URL; ?>Courses/add.php" class="quick-action">
                        <i class="fas fa-book"></i> Add Course
                    </a>
                </div>
                <div class="mt-3 pt-3 border-top" style="border-color:var(--border-color);">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Today's Stats</span>
                        <div class="d-flex gap-3">
                            <span><i class="fas fa-user-plus text-success"></i> <?php echo $stats['new_students_today']; ?> New</span>
                            <span><i class="fas fa-file-alt text-primary"></i> <?php echo $stats['new_apps_today']; ?> Apps</span>
                            <span><i class="fas fa-clock text-warning"></i> <?php echo $stats['pending_apps']; ?> Pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- RECENT ACTIVITY + TOP COURSES -->
    <!-- ========================================== -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="card-title d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-graduate text-primary"></i> Recent Students</span>
                    <a href="<?php echo BASE_URL; ?>students/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php if (!empty($recent_students)): ?>
                    <?php foreach ($recent_students as $student): ?>
                    <div class="activity-item">
                        <div class="activity-icon" style="background:#e3f2fd; color:#1976d2;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="activity-text">
                            <div class="title"><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></div>
                            <div class="time">
                                <i class="far fa-id-card"></i> <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?> 
                                | <i class="far fa-calendar"></i> <?php echo isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <span class="status-badge status-active"><?php echo ucfirst($student['status'] ?? 'Active'); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No recent students</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="card-title d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-alt text-success"></i> Recent Applications</span>
                    <a href="<?php echo BASE_URL; ?>applications/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php if (!empty($recent_apps)): ?>
                    <?php foreach ($recent_apps as $app): 
                        $status_class = '';
                        $status_text = $app['status'] ?? 'Pending';
                        if ($status_text == 'Approved') $status_class = 'status-approved';
                        elseif ($status_text == 'Rejected') $status_class = 'status-rejected';
                        else $status_class = 'status-pending';
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon" style="background:#fce4ec; color:#c62828;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="activity-text">
                            <div class="title"><?php echo htmlspecialchars($app['student_name'] ?? 'N/A'); ?></div>
                            <div class="time">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($app['application_type'] ?? 'N/A'); ?>
                                | <i class="far fa-calendar"></i> <?php echo isset($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No recent applications</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FOOTER -->
    <!-- ========================================== -->
    <div class="text-center text-muted small mt-4" style="border-top:1px solid var(--border-color); padding-top:20px;">
        <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> University MIS - SSO Module | 
        <i class="fas fa-code"></i> Built with <i class="fas fa-heart text-danger"></i> | v2.0
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS -->
<!-- ============================================ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Counter Animation -->
<script>
$(document).ready(function() {
    function animateCounter(id, target) {
        if (target == 0) { $('#' + id).text('0'); return; }
        let current = 0;
        let increment = Math.ceil(target / 50);
        let interval = setInterval(function() {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(interval);
            }
            $('#' + id).text(current);
        }, 30);
    }

    animateCounter('countStudents', <?php echo $stats['students']; ?>);
    animateCounter('countCourses', <?php echo $stats['courses']; ?>);
    animateCounter('countTeachers', <?php echo $stats['teachers']; ?>);
    animateCounter('countApps', <?php echo $stats['applications']; ?>);
});

// ============================================
// DARK MODE TOGGLE
// ============================================
const themeToggle = document.getElementById('themeToggle');
const currentTheme = localStorage.getItem('theme') || 'light';
if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
}

themeToggle.addEventListener('click', function() {
    const theme = document.documentElement.getAttribute('data-theme');
    if (theme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        this.innerHTML = '<i class="fas fa-moon"></i>';
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        this.innerHTML = '<i class="fas fa-sun"></i>';
    }
});
</script>

<!-- ============================================ -->
<!-- CHART.JS - Attendance Trend -->
<!-- ============================================ -->
<script>
const ctx2 = document.getElementById('attendanceTrendChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [
            {
                label: 'Present',
                data: <?php echo json_encode($chart_present); ?>,
                borderColor: '#43e97b',
                backgroundColor: 'rgba(67, 233, 123, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#43e97b',
                pointRadius: 4,
                borderWidth: 2
            },
            {
                label: 'Absent',
                data: <?php echo json_encode($chart_absent); ?>,
                borderColor: '#f5576c',
                backgroundColor: 'rgba(245, 87, 108, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f5576c',
                pointRadius: 4,
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 12,
                    padding: 15,
                    font: { size: 12, weight: '600' },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { size: 11 } },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
</script>

</body>
</html>
