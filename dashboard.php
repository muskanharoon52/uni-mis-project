<?php
// dashboard.php - ULTIMATE DASHBOARD WITH ERROR HANDLING

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/config/db_connect.php';

// Check if auth.php exists
$authFile = __DIR__ . '/modules/sso/includes/auth.php';
if (!file_exists($authFile)) {
    die("Authentication file not found: " . $authFile);
}
require_once $authFile;

// Check if logged in
if (!function_exists('isLoggedIn')) {
    die("isLoggedIn() function not found in auth.php");
}

if (!isLoggedIn()) {
    header('Location: modules/sso/login.php');
    exit;
}

// Get user
$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

// Get database connection
$conn = getConnection();

// Check if connection is valid
if (!$conn) {
    die("Database connection failed!");
}

// Function to safely get count from a table
if (!function_exists('getTableCount')) {
    function getTableCount($conn, $tableName) {
        try {
            $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
            if (!$check || mysqli_num_rows($check) == 0) {
                return 0;
            }
            
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $tableName");
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['count'];
            }
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Function to safely query with try-catch
if (!function_exists('safeQuery')) {
    function safeQuery($conn, $query, $default = 0) {
        try {
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['count'] ?? $row['total'] ?? $default;
            }
            return $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

// Helper function to check if table exists
if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

// Helper function to check if column exists
if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $query = "SHOW COLUMNS FROM $table LIKE '$column'";
            $result = mysqli_query($conn, $query);
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) {
            return false;
        }
    }
}

// Helper function to get table columns
if (!function_exists('getTableColumns')) {
    function getTableColumns($conn, $table) {
        try {
            $columns = [];
            $query = "SHOW COLUMNS FROM $table";
            $result = mysqli_query($conn, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row['Field'];
                }
            }
            return $columns;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Initialize stats array with default values
$stats = [
    'students' => 0,
    'courses' => 0,
    'teachers' => 0,
    'applications' => 0,
    'fee_records' => 0,
    'sections' => 0,
    'enrollments' => 0,
    'attendance_today' => 0,
    'new_students_today' => 0,
    'new_apps_today' => 0,
    'total_fee' => 0,
    'pending_apps' => 0
];

// Get counts safely
$stats['students'] = getTableCount($conn, 'students');
$stats['courses'] = getTableCount($conn, 'courses');
$stats['teachers'] = getTableCount($conn, 'teachers');
$stats['applications'] = getTableCount($conn, 'applications');
$stats['fee_records'] = getTableCount($conn, 'fee_records');
$stats['sections'] = getTableCount($conn, 'sections');
$stats['enrollments'] = getTableCount($conn, 'student_enrollments');

// Today's Stats
$today = date('Y-m-d');

// Check if attendance table exists
if (tableExists($conn, 'attendance')) {
    if (columnExists($conn, 'attendance', 'class_date')) {
        $stats['attendance_today'] = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today'");
    }
}

// Check if students table exists and handle enrollment date
if (tableExists($conn, 'students')) {
    $studentColumns = getTableColumns($conn, 'students');
    
    // Find the date column
    $dateColumn = 'enrollment_date';
    if (!in_array('enrollment_date', $studentColumns)) {
        if (in_array('created_at', $studentColumns)) {
            $dateColumn = 'created_at';
        } elseif (in_array('registration_date', $studentColumns)) {
            $dateColumn = 'registration_date';
        } elseif (in_array('join_date', $studentColumns)) {
            $dateColumn = 'join_date';
        } else {
            $dateColumn = null;
        }
    }
    
    if ($dateColumn) {
        $stats['new_students_today'] = safeQuery($conn, "SELECT COUNT(*) as count FROM students WHERE $dateColumn = '$today'");
    }
}

// Check if applications table exists
if (tableExists($conn, 'applications')) {
    $appColumns = getTableColumns($conn, 'applications');
    
    if (in_array('created_at', $appColumns)) {
        $stats['new_apps_today'] = safeQuery($conn, "SELECT COUNT(*) as count FROM applications WHERE DATE(created_at) = '$today'");
    }
    if (in_array('status', $appColumns)) {
        $stats['pending_apps'] = safeQuery($conn, "SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'");
    }
}

// Total Fee Collected
if (tableExists($conn, 'fee_records')) {
    $feeColumns = getTableColumns($conn, 'fee_records');
    if (in_array('paid_amount', $feeColumns)) {
        $stats['total_fee'] = safeQuery($conn, "SELECT SUM(paid_amount) as total FROM fee_records");
    }
}

// Recent Students (Last 5) - Handle missing columns properly
$recent_students = [];
if (tableExists($conn, 'students')) {
    $studentColumns = getTableColumns($conn, 'students');
    
    // Check if users table exists and has full_name
    $userTableExists = tableExists($conn, 'users');
    $userColumns = $userTableExists ? getTableColumns($conn, 'users') : [];
    
    // Build the SELECT query based on available columns
    $selectFields = [];
    $selectFields[] = 's.student_id';
    
    if (in_array('roll_no', $studentColumns)) {
        $selectFields[] = 's.roll_no';
    }
    
    if (in_array('full_name', $studentColumns)) {
        $selectFields[] = 's.full_name';
    } elseif ($userTableExists && in_array('full_name', $userColumns)) {
        $selectFields[] = 'u.full_name';
        $joinUser = true;
    } else {
        $selectFields[] = "'N/A' as full_name";
    }
    
    if (in_array('status', $studentColumns)) {
        $selectFields[] = 's.status';
    }
    
    // Find date column for ordering
    $orderColumn = 'student_id';
    if (in_array('enrollment_date', $studentColumns)) {
        $orderColumn = 'enrollment_date';
    } elseif (in_array('created_at', $studentColumns)) {
        $orderColumn = 'created_at';
    } elseif (in_array('registration_date', $studentColumns)) {
        $orderColumn = 'registration_date';
    }
    $selectFields[] = "s.$orderColumn as order_date";
    
    // Build the query
    $selectStr = implode(', ', $selectFields);
    $joinStr = isset($joinUser) && $userTableExists ? "LEFT JOIN users u ON s.user_id = u.user_id" : "";
    
    $recent_query = "SELECT $selectStr 
                     FROM students s 
                     $joinStr
                     ORDER BY s.$orderColumn DESC LIMIT 5";
    
    try {
        $recent_result = mysqli_query($conn, $recent_query);
        if ($recent_result) {
            while ($row = mysqli_fetch_assoc($recent_result)) {
                $recent_students[] = $row;
            }
        }
    } catch (Exception $e) {
        // If query fails, try a simpler query without joins
        try {
            $simple_query = "SELECT * FROM students ORDER BY student_id DESC LIMIT 5";
            $recent_result = mysqli_query($conn, $simple_query);
            if ($recent_result) {
                while ($row = mysqli_fetch_assoc($recent_result)) {
                    $recent_students[] = $row;
                }
            }
        } catch (Exception $e2) {
            $recent_students = [];
        }
    }
}

// Recent Applications (Last 5)
$recent_apps = [];
if (tableExists($conn, 'applications')) {
    $appColumns = getTableColumns($conn, 'applications');
    
    // Check if students table has full_name
    $studentColumns = tableExists($conn, 'students') ? getTableColumns($conn, 'students') : [];
    
    $app_query = "SELECT a.*";
    if (in_array('full_name', $studentColumns)) {
        $app_query .= ", s.full_name as student_name";
    } else {
        $app_query .= ", 'N/A' as student_name";
    }
    $app_query .= " FROM applications a";
    if (in_array('full_name', $studentColumns)) {
        $app_query .= " LEFT JOIN students s ON a.student_id = s.student_id";
    }
    
    if (in_array('created_at', $appColumns)) {
        $app_query .= " ORDER BY a.created_at DESC LIMIT 5";
    } else {
        $app_query .= " ORDER BY a.application_id DESC LIMIT 5";
    }
    
    try {
        $app_result = mysqli_query($conn, $app_query);
        if ($app_result) {
            while ($row = mysqli_fetch_assoc($app_result)) {
                $recent_apps[] = $row;
            }
        }
    } catch (Exception $e) {
        $recent_apps = [];
    }
}

// Attendance Chart Data (Last 7 Days)
$chart_labels = [];
$chart_present = [];
$chart_absent = [];
if (tableExists($conn, 'attendance')) {
    $attColumns = getTableColumns($conn, 'attendance');
    if (in_array('class_date', $attColumns) && in_array('status', $attColumns)) {
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('D', strtotime($date));
            
            $p_count = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$date' AND status = 'Present'");
            $chart_present[] = $p_count;
            
            $a_count = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$date' AND status = 'Absent'");
            $chart_absent[] = $a_count;
        }
    } else {
        // Default empty chart data
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('D', strtotime($date));
            $chart_present[] = 0;
            $chart_absent[] = 0;
        }
    }
} else {
    // Default empty chart data
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('D', strtotime($date));
        $chart_present[] = 0;
        $chart_absent[] = 0;
    }
}

// Today's Attendance Status
$today_present = 0;
$today_absent = 0;
$today_late = 0;
if (tableExists($conn, 'attendance')) {
    $attColumns = getTableColumns($conn, 'attendance');
    if (in_array('class_date', $attColumns) && in_array('status', $attColumns)) {
        $today_present = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Present'");
        $today_absent = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Absent'");
        $today_late = safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today' AND status = 'Late'");
    }
}

// Fee Collection Stats
$fee_paid = $stats['total_fee'] ?? 0;
$fee_total = 0;
if (tableExists($conn, 'fee_records')) {
    $feeColumns = getTableColumns($conn, 'fee_records');
    if (in_array('total_fee', $feeColumns)) {
        $fee_total = safeQuery($conn, "SELECT SUM(total_fee) as total FROM fee_records");
    }
}
$fee_remaining = $fee_total - $fee_paid;

// Top Courses (by enrollment)
$top_courses = [];
if (tableExists($conn, 'student_courses') && tableExists($conn, 'courses')) {
    try {
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
    } catch (Exception $e) {
        $top_courses = [];
    }
}

// ============================================
// INCLUDE HEADER
// ============================================
$headerFile = __DIR__ . '/includes/header.php';
if (file_exists($headerFile)) {
    include $headerFile;
} else {
    echo '<!DOCTYPE html><html><head><title>Dashboard</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '</head><body>';
}

$page_title = 'Dashboard';
$sidebarFile = __DIR__ . '/includes/sidebar.php';
if (file_exists($sidebarFile)) {
    include $sidebarFile;
} else {
    echo '<div class="main-content" style="margin-left:0;padding:20px;">';
}
?>

<!-- ============================================ -->
<!-- STYLES -->
<!-- ============================================ -->
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
    
    /* Chart container */
    .chart-container { height: 200px; position: relative; }
    
    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 768px) {
        .main-content { margin-left: 0; padding: 15px; }
        .stat-card .number { font-size: 24px; }
        .welcome-text { font-size: 18px; }
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
    <!-- CHARTS ONLY (Quick Actions Removed) -->
    <!-- ========================================== -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-title"><i class="fas fa-chart-line text-primary"></i> Attendance Trend (Last 7 Days)</div>
                <div class="chart-container">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- JAVASCRIPT -->
    <!-- ========================================== -->
    <script>
        // Animated Counter
        function animateCounter(elementId, target, duration = 1000) {
            const element = document.getElementById(elementId);
            if (!element) return;
            let start = 0;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(progress * target);
                element.textContent = current;
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    element.textContent = target;
                }
            }
            requestAnimationFrame(update);
        }

        // Start animations
        document.addEventListener('DOMContentLoaded', function() {
            animateCounter('countStudents', <?php echo $stats['students']; ?>, 1200);
            animateCounter('countCourses', <?php echo $stats['courses']; ?>, 1200);
            animateCounter('countTeachers', <?php echo $stats['teachers']; ?>, 1200);
            animateCounter('countApps', <?php echo $stats['applications']; ?>, 1200);
        });

        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            themeToggle.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    this.innerHTML = '<i class="fas fa-moon"></i>';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    this.innerHTML = '<i class="fas fa-sun"></i>';
                }
            });
        }

        // Attendance Chart
        (function() {
            const ctx = document.getElementById('attendanceTrendChart');
            if (!ctx) return;
            
            const labels = <?php echo json_encode($chart_labels); ?>;
            const presentData = <?php echo json_encode($chart_present); ?>;
            const absentData = <?php echo json_encode($chart_absent); ?>;
            
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Present',
                            data: presentData,
                            backgroundColor: 'rgba(67, 233, 123, 0.7)',
                            borderColor: '#43e97b',
                            borderWidth: 2,
                            borderRadius: 6,
                        },
                        {
                            label: 'Absent',
                            data: absentData,
                            backgroundColor: 'rgba(245, 87, 108, 0.7)',
                            borderColor: '#f5576c',
                            borderWidth: 2,
                            borderRadius: 6,
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
                                font: { size: 12 }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 11 } },
                            grid: { color: 'var(--border-color)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        })();
    </script>

</div>

</body>
</html>