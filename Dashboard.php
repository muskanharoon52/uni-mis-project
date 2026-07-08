<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get staff name from session
$staff_name = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Staff Member';

// ============================================
// FETCH DATA FROM DATABASE
// ============================================

// 1. Total Students
$total_students = getCount('students');

// 2. Total Departments
$total_departments = getCount('departments');

// 3. Total Teachers
$total_teachers = getCount('teachers');

// 4. Total Courses/Programs
$total_courses = getCount('courses');

// 5. Pending Applications (from admission_applications)
$pending_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                    SUM(CASE WHEN application_status = 'Under Review' THEN 1 ELSE 0 END) as under_review
                 FROM admission_applications 
                 WHERE application_status IN ('Submitted', 'Under Review')";
$pending_result = executeQuery($pending_sql);
$pending_data = $pending_result->fetch_assoc();

// 6. Today's Attendance (using class_date)
$attendance_sql = "SELECT 
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) as `leave`,
                    COUNT(*) as total
                  FROM attendance 
                  WHERE DATE(class_date) = CURDATE()";
$attendance_result = executeQuery($attendance_sql);
$attendance_data = $attendance_result->fetch_assoc();

// Calculate percentages
$total_attendance = $attendance_data['total'] > 0 ? $attendance_data['total'] : 1;
$attendance_present_pct = round(($attendance_data['present'] / $total_attendance) * 100);
$attendance_absent_pct = round(($attendance_data['absent'] / $total_attendance) * 100);
$attendance_leave_pct = round(($attendance_data['leave'] / $total_attendance) * 100);

// 7. Recent Applications (with department name)
$recent_sql = "SELECT 
                    a.application_id as id,
                    a.full_name as student_name,
                    d.department_name as course_name,
                    a.application_status as status,
                    a.created_at
               FROM admission_applications a
               LEFT JOIN departments d ON a.program_id = d.department_id
               ORDER BY a.created_at DESC
               LIMIT 5";
$recent_applications = getAllRecords($recent_sql);

// Current date and time
date_default_timezone_set('Asia/Karachi');
$current_date = date('l, F j, Y');
$current_time = date('h:i A');

// Helper function for time elapsed
function time_elapsed_string($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'Just now';
}

// Function to get status badge class
function getStatusClass($status) {
    $status = strtolower($status);
    if ($status == 'submitted' || $status == 'pending') {
        return 'pending';
    } elseif ($status == 'under review' || $status == 'reviewing') {
        return 'reviewing';
    } elseif ($status == 'approved' || $status == 'admitted') {
        return 'approved';
    } elseif ($status == 'rejected' || $status == 'cancelled') {
        return 'rejected';
    }
    return 'pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Dashboard - University Management</title>
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================
           GLOBAL STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
        }

        /* ============================================
           CONTENT WRAPPER (pushes content right of sidebar)
           ============================================ */
        .content-wrapper {
            margin-left: 260px;
            padding: 20px 30px;
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* ============================================
           DASHBOARD CONTAINER
           ============================================ */
        .dashboard {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* ============================================
           HEADER
           ============================================ */
        .header {
            background: linear-gradient(135deg, #1a2a6c, #2d4373);
            color: white;
            padding: 24px 32px;
            border-radius: 16px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }
        .header h1 span {
            font-weight: 300;
            opacity: 0.8;
        }
        .header-info {
            text-align: right;
            font-size: 14px;
            opacity: 0.9;
        }
        .header-info .name {
            font-weight: 600;
            font-size: 16px;
        }
        .refresh-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        .refresh-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* ============================================
           STATS GRID
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #1a2a6c;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
        }
        .stat-card .icon {
            font-size: 28px;
            margin-bottom: 6px;
        }
        .stat-card .label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #1a2a6c;
            margin-top: 2px;
        }
        .stat-card:nth-child(2) { border-left-color: #e67e22; }
        .stat-card:nth-child(3) { border-left-color: #27ae60; }
        .stat-card:nth-child(4) { border-left-color: #8e44ad; }

        /* ============================================
           TWO-COLUMN ROW
           ============================================ */
        .row-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        /* ============================================
           CARDS
           ============================================ */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title .badge {
            background: #ef4444;
            color: white;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 20px;
            margin-left: auto;
        }

        /* Pending Stats */
        .pending-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .pending-item {
            text-align: center;
            flex: 1;
            min-width: 80px;
        }
        .pending-item .number {
            font-size: 32px;
            font-weight: 700;
        }
        .pending-item .number.urgent { color: #dc2626; }
        .pending-item .number.new { color: #2563eb; }
        .pending-item .number.total { color: #1f2937; }
        .pending-item .number.review { color: #8b5cf6; }
        .pending-item .desc {
            font-size: 14px;
            color: #6b7280;
        }

        /* Attendance */
        .attendance-chart {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .attendance-ring {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }
        .attendance-ring .inner {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .attendance-ring .inner .pct {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
        }
        .attendance-ring .inner .label-sm {
            font-size: 11px;
            color: #6b7280;
        }
        .attendance-legend {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .legend-item .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .legend-item .dot.present { background: #22c55e; }
        .legend-item .dot.absent { background: #ef4444; }
        .legend-item .dot.leave { background: #f59e0b; }

        /* ============================================
           TABLE
           ============================================ */
        .table-wrap {
            overflow-x: auto;
        }
        .app-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .app-table thead th {
            text-align: left;
            padding: 12px 12px 12px 0;
            border-bottom: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .app-table tbody td {
            padding: 14px 12px 14px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .app-table tbody tr:hover {
            background: #f9fafb;
        }
        .app-table .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status.pending { background: #fef3c7; color: #92400e; }
        .status.approved { background: #d1fae5; color: #065f46; }
        .status.reviewing { background: #dbeafe; color: #1e40af; }
        .status.rejected { background: #fee2e2; color: #991b1b; }

        .view-all {
            display: inline-block;
            margin-top: 16px;
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }
        .view-all:hover {
            text-decoration: underline;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 992px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .row-two-col {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .header-info {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- ============================================
     INCLUDE SIDEBAR
     ============================================ -->
<?php include_once 'includes/sidebar.php'; ?>

<!-- ============================================
     MAIN CONTENT WRAPPER
     ============================================ -->
<div class="content-wrapper">

    <div class="dashboard">

        <!-- ===== HEADER ===== -->
        <header class="header">
            <h1>🎓 SSO <span>Dashboard</span></h1>
            <div class="header-info">
                <div class="name">👋 Welcome, <?php echo htmlspecialchars($staff_name); ?></div>
                <div>
                    📅 <?php echo $current_date; ?> • <?php echo $current_time; ?>
                    <button onclick="location.reload()" class="refresh-btn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </header>

        <!-- ===== STATS CARDS ===== -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="icon">👨‍🎓</div>
                <div class="label">Total Students</div>
                <div class="value"><?php echo number_format($total_students); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">🏛️</div>
                <div class="label">Total Departments</div>
                <div class="value"><?php echo number_format($total_departments); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">👨‍🏫</div>
                <div class="label">Total Teachers</div>
                <div class="value"><?php echo number_format($total_teachers); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📚</div>
                <div class="label">Total Courses</div>
                <div class="value"><?php echo number_format($total_courses); ?></div>
            </div>
        </section>

        <!-- ===== PENDING + ATTENDANCE ===== -->
        <section class="row-two-col">

            <!-- Pending Applications -->
            <div class="card">
                <div class="card-title">
                    ⏳ Pending Applications
                    <span class="badge">Under Review: <?php echo $pending_data['under_review'] ?? 0; ?></span>
                </div>
                <div class="pending-stats">
                    <div class="pending-item">
                        <div class="number total"><?php echo $pending_data['total'] ?? 0; ?></div>
                        <div class="desc">Total Pending</div>
                    </div>
                    <div class="pending-item">
                        <div class="number review"><?php echo $pending_data['under_review'] ?? 0; ?></div>
                        <div class="desc">🔄 Under Review</div>
                    </div>
                    <div class="pending-item">
                        <div class="number new"><?php echo $pending_data['new_today'] ?? 0; ?></div>
                        <div class="desc">📅 New Today</div>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance -->
            <div class="card">
                <div class="card-title">📊 Today's Attendance Summary</div>
                <div class="attendance-chart">
                    <div class="attendance-ring" style="background: conic-gradient(
                        #22c55e 0% <?php echo $attendance_present_pct; ?>%,
                        #ef4444 <?php echo $attendance_present_pct; ?>% <?php echo $attendance_present_pct + $attendance_absent_pct; ?>%,
                        #f59e0b <?php echo $attendance_present_pct + $attendance_absent_pct; ?>% 100%
                    );">
                        <div class="inner">
                            <span class="pct"><?php echo $attendance_present_pct; ?>%</span>
                            <span class="label-sm">Present</span>
                        </div>
                    </div>
                    <div class="attendance-legend">
                        <div class="legend-item">
                            <span class="dot present"></span>
                            Present — <?php echo $attendance_present_pct; ?>%
                        </div>
                        <div class="legend-item">
                            <span class="dot absent"></span>
                            Absent — <?php echo $attendance_absent_pct; ?>%
                        </div>
                        <div class="legend-item">
                            <span class="dot leave"></span>
                            Leave — <?php echo $attendance_leave_pct; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== RECENT APPLICATIONS ===== -->
        <section class="card">
            <div class="card-title">📋 Recent Student Applications</div>
            <div class="table-wrap">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_applications) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($app['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($app['course_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status <?php echo getStatusClass($app['status']); ?>">
                                            <?php echo htmlspecialchars($app['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo time_elapsed_string($app['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #6b7280;">
                                    <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                    No recent applications found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="admissions.php" class="view-all">
                View All Applications <i class="fas fa-arrow-right"></i>
            </a>
        </section>

    </div>

</div>

</body>
</html>

<?php
// Close database connection
closeConnection();
?>