<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';

// ----- STATISTICS -----
$stu_sql = "SELECT COUNT(*) AS total FROM students WHERE status = 'Active'";
$stu_result = mysqli_query($conn, $stu_sql);
$total_students = mysqli_fetch_assoc($stu_result)['total'] ?? 0;

$rev_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE status = 'Success'";
$rev_result = mysqli_query($conn, $rev_sql);
$total_revenue = mysqli_fetch_assoc($rev_result)['total'] ?? 0;

$pen_sql = "SELECT SUM(remaining_amount) AS total FROM student_fee WHERE remaining_amount > 0";
$pen_result = mysqli_query($conn, $pen_sql);
$pending_fee = mysqli_fetch_assoc($pen_result)['total'] ?? 0;

$pay_sql = "SELECT COUNT(*) AS total FROM payments WHERE status = 'Success'";
$pay_result = mysqli_query($conn, $pay_sql);
$total_payments = mysqli_fetch_assoc($pay_result)['total'] ?? 0;

$today = date('Y-m-d');
$today_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) = '$today' AND status = 'Success'";
$today_result = mysqli_query($conn, $today_sql);
$today_collection = mysqli_fetch_assoc($today_result)['total'] ?? 0;

$week_start = date('Y-m-d', strtotime('monday this week'));
$week_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '$week_start' AND status = 'Success'";
$week_result = mysqli_query($conn, $week_sql);
$week_collection = mysqli_fetch_assoc($week_result)['total'] ?? 0;

$month_start = date('Y-m-01');
$month_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '$month_start' AND status = 'Success'";
$month_result = mysqli_query($conn, $month_sql);
$month_collection = mysqli_fetch_assoc($month_result)['total'] ?? 0;

// Recent Activity Logs
$log_sql = "SELECT module, action, details, created_at FROM activity_logs ORDER BY log_id DESC LIMIT 10";
$log_result = mysqli_query($conn, $log_sql);

// Monthly Payment Chart
$chart_sql = "SELECT DATE_FORMAT(payment_date, '%b %Y') AS month, SUM(amount_paid) AS total
              FROM payments
              WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              AND status = 'Success'
              GROUP BY YEAR(payment_date), MONTH(payment_date)
              ORDER BY payment_date ASC";
$chart_result = mysqli_query($conn, $chart_sql);
$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_labels[] = $row['month'];
    $chart_data[] = $row['total'];
}

// Pie Chart: Payment Methods Distribution
$pie_sql = "SELECT payment_method, COUNT(*) as count, SUM(amount_paid) as total 
            FROM payments 
            WHERE status = 'Success' 
            GROUP BY payment_method";
$pie_result = mysqli_query($conn, $pie_sql);
$pie_labels = [];
$pie_data = [];
while ($row = mysqli_fetch_assoc($pie_result)) {
    $pie_labels[] = $row['payment_method'];
    $pie_data[] = $row['total'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-chart-pie text-primary"></i> Finance Dashboard</h2>
    <div>
        <span class="user-badge">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Finance Officer'); ?>
        </span>
        <button onclick="window.location.reload()" class="btn btn-secondary btn-sm">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">Total Revenue</div>
                    <div class="number">PKR <?php echo number_format($total_revenue, 0); ?></div>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">Pending Fee</div>
                    <div class="number">PKR <?php echo number_format($pending_fee, 0); ?></div>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #007bff, #6610f2);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">Total Students</div>
                    <div class="number"><?php echo number_format($total_students); ?></div>
                </div>
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">Total Payments</div>
                    <div class="number"><?php echo number_format($total_payments); ?></div>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Collection Cards -->
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">Today's Collection</div>
                    <div class="number">PKR <?php echo number_format($today_collection, 0); ?></div>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">This Week</div>
                    <div class="number">PKR <?php echo number_format($week_collection, 0); ?></div>
                </div>
                <div class="icon"><i class="fas fa-calendar-week"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card p-3 text-white rounded" style="background: linear-gradient(135deg, #20c997, #28a745);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="label">This Month</div>
                    <div class="number">PKR <?php echo number_format($month_collection, 0); ?></div>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart and Pie Chart Row -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-bar"></i> Monthly Collection (Last 6 Months)
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <i class="fas fa-pie-chart"></i> Payment Methods
            </div>
            <div class="card-body">
                <canvas id="pieChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Logs -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white">
        <i class="fas fa-history"></i> Recent Activities
    </div>
    <div class="card-body">
        <?php if (isset($log_result) && mysqli_num_rows($log_result) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr><th>Module</th><th>Action</th><th>Details</th><th>Date/Time</th></tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($log_result)): ?>
                        <tr>
                            <td><span class="badge bg-success"><?php echo htmlspecialchars($row['module']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['action']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['details'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No recent activities found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
// Bar Chart - Monthly Collection
const ctx = document.getElementById('paymentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Collection (PKR)',
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 2,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'PKR ' + value.toLocaleString(); } } } }
    }
});

// Pie Chart - Payment Methods
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($pie_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($pie_data); ?>,
            backgroundColor: ['#28a745', '#007bff', '#fd7e14', '#6f42c1', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>