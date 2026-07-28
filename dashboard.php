<?php
// dashboard.php - SSO Dashboard

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: modules/sso/login.php');
    exit;
}

$user = getCurrentUser();
$conn = getConnection();

if (!$conn) {
    die("Database connection failed!");
}

if (!function_exists('getTableCount')) {
    function getTableCount($conn, $tableName) {
        try {
            $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
            if (!$check || mysqli_num_rows($check) == 0) return 0;
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $tableName");
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['count'];
            }
            return 0;
        } catch (Exception $e) { return 0; }
    }
}

if (!function_exists('safeQuery')) {
    function safeQuery($conn, $query, $default = 0) {
        try {
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['count'] ?? $row['total'] ?? $default;
            }
            return $default;
        } catch (Exception $e) { return $default; }
    }
}

if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) { return false; }
    }
}

if (!function_exists('getTableColumns')) {
    function getTableColumns($conn, $table) {
        try {
            $columns = [];
            $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
            if ($result) { while ($row = mysqli_fetch_assoc($result)) { $columns[] = $row['Field']; } }
            return $columns;
        } catch (Exception $e) { return []; }
    }
}

$stats = [
    'students' => getTableCount($conn, 'students'),
    'courses' => getTableCount($conn, 'courses'),
    'teachers' => getTableCount($conn, 'teachers'),
    'applications' => getTableCount($conn, 'applications'),
    'fee_records' => getTableCount($conn, 'fee_records'),
    'sections' => getTableCount($conn, 'sections'),
    'enrollments' => getTableCount($conn, 'student_enrollments'),
];

$today = date('Y-m-d');
$stats['attendance_today'] = tableExists($conn, 'attendance') ? safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE class_date = '$today'") : 0;

if (tableExists($conn, 'applications')) {
    $appColumns = getTableColumns($conn, 'applications');
    $stats['pending_apps'] = in_array('status', $appColumns) ? safeQuery($conn, "SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'") : 0;
} else {
    $stats['pending_apps'] = 0;
}

$chart_labels = [];
$chart_present = [];
$chart_absent = [];
if (tableExists($conn, 'attendance')) {
    $attColumns = getTableColumns($conn, 'attendance');
    $dateCol = in_array('class_date', $attColumns) ? 'class_date' : (in_array('attendance_date', $attColumns) ? 'attendance_date' : 'date');
    $hasStatus = in_array('status', $attColumns);
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('D', strtotime($date));
        $chart_present[] = $hasStatus ? safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE $dateCol = '$date' AND status = 'Present'") : 0;
        $chart_absent[] = $hasStatus ? safeQuery($conn, "SELECT COUNT(*) as count FROM attendance WHERE $dateCol = '$date' AND status = 'Absent'") : 0;
    }
} else {
    for ($i = 6; $i >= 0; $i--) {
        $chart_labels[] = date('D', strtotime("-" . $i . " days"));
        $chart_present[] = 0;
        $chart_absent[] = 0;
    }
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$displayName = $user['full_name'] ?? 'SSO Admin';
?>

<div style="background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 50%,#2563EB 100%);border-radius:var(--radius-lg);padding:28px 32px;margin-bottom:24px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
    <div>
        <div style="font-size:1.4rem;font-weight:700;margin-bottom:4px;"><?= $greeting ?>, <?= htmlspecialchars($displayName) ?> &#128075;</div>
        <div style="font-size:.88rem;color:rgba(255,255,255,0.7);">Welcome back to the SSO Admin Dashboard. Here's what's happening today.</div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:.78rem;color:#fff;opacity:.85;"><?= date('l') ?></div>
        <div style="font-size:1.1rem;font-weight:600;color:#fff;"><?= date('d M Y') ?></div>
    </div>
</div>

<div class="stat-row">
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Total Students</div>
                <div class="stat-number"><?= $stats['students'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(37,99,235,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#127891;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Total Courses</div>
                <div class="stat-number"><?= $stats['courses'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128218;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Total Teachers</div>
                <div class="stat-number"><?= $stats['teachers'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128205;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Applications</div>
                <div class="stat-number"><?= $stats['applications'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(139,92,246,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128196;</div>
        </div>
    </div>
</div>

<div class="stat-row">
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Fee Records</div>
                <div class="stat-number"><?= $stats['fee_records'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128176;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Enrollments</div>
                <div class="stat-number"><?= $stats['enrollments'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(236,72,153,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128203;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Attendance Today</div>
                <div class="stat-number"><?= $stats['attendance_today'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(37,99,235,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#9989;</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="stat-label">Pending Apps</div>
                <div class="stat-number"><?= $stats['pending_apps'] ?></div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;font-size:20px;">&#9203;</div>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;font-size:15px;font-weight:600;color:var(--navy);">Attendance Trend (Last 7 Days)</h3>
    <div style="position:relative;width:100%;height:280px;">
        <canvas id="attendanceChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                { label: 'Present', data: <?= json_encode($chart_present) ?>, backgroundColor: 'rgba(16,185,129,0.75)', borderColor: '#10b981', borderWidth: 2, borderRadius: 6 },
                { label: 'Absent', data: <?= json_encode($chart_absent) ?>, backgroundColor: 'rgba(239,68,68,0.75)', borderColor: '#ef4444', borderWidth: 2, borderRadius: 6 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, padding: 16, font: { size: 12, family: "'Inter', sans-serif" } } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11, family: "'Inter', sans-serif" } },
                    grid: { color: '#E8EDF4' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, family: "'Inter', sans-serif" } },
                    border: { display: false }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
