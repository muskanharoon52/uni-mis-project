<?php
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

// ----- STATISTICS -----

// Total Students
$stu_sql = "SELECT COUNT(*) AS total FROM students WHERE status = 'Active'";
$stu_result = mysqli_query($conn, $stu_sql);
$total_students = mysqli_fetch_assoc($stu_result)['total'] ?? 0;

// Total Revenue (collected)
$rev_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE status = 'Success'";
$rev_result = mysqli_query($conn, $rev_sql);
$total_revenue = mysqli_fetch_assoc($rev_result)['total'] ?? 0;

// Total Pending Fee
$pen_sql = "SELECT SUM(remaining_amount) AS total FROM student_fee WHERE remaining_amount > 0";
$pen_result = mysqli_query($conn, $pen_sql);
$pending_fee = mysqli_fetch_assoc($pen_result)['total'] ?? 0;

// Total Payments Count
$pay_sql = "SELECT COUNT(*) AS total FROM payments WHERE status = 'Success'";
$pay_result = mysqli_query($conn, $pay_sql);
$total_payments = mysqli_fetch_assoc($pay_result)['total'] ?? 0;

// Fee Status Counts
$status_sql = "SELECT status, COUNT(*) AS count FROM student_fee GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}

// Today's Collection
$today = date('Y-m-d');
$today_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) = '$today' AND status = 'Success'";
$today_result = mysqli_query($conn, $today_sql);
$today_collection = mysqli_fetch_assoc($today_result)['total'] ?? 0;

// This Week Collection
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '$week_start' AND status = 'Success'";
$week_result = mysqli_query($conn, $week_sql);
$week_collection = mysqli_fetch_assoc($week_result)['total'] ?? 0;

// This Month Collection
$month_start = date('Y-m-01');
$month_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '$month_start' AND status = 'Success'";
$month_result = mysqli_query($conn, $month_sql);
$month_collection = mysqli_fetch_assoc($month_result)['total'] ?? 0;

// Recent Activity Logs
$log_sql = "SELECT 
            module,
            action,
            reference_table,
            reference_id,
            details,
            created_at
            FROM activity_logs 
            ORDER BY log_id DESC 
            LIMIT 10";
$log_result = mysqli_query($conn, $log_sql);

// Monthly Payment Chart (Last 6 Months)
$chart_sql = "SELECT 
              DATE_FORMAT(payment_date, '%b %Y') AS month,
              SUM(amount_paid) AS total
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: #fff;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: scale(1.03);
        }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .bg-revenue { background: linear-gradient(135deg, #28a745, #20c997); }
        .bg-pending { background: linear-gradient(135deg, #dc3545, #fd7e14); }
        .bg-students { background: linear-gradient(135deg, #007bff, #6610f2); }
        .bg-payments { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
        .bg-today { background: linear-gradient(135deg, #fd7e14, #ffc107); }
        .bg-week { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
        .bg-month { background: linear-gradient(135deg, #20c997, #28a745); }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-unpaid { background-color: #f8d7da; color: #721c24; }
        .status-overdue { background-color: #f8d7da; color: #721c24; font-weight: bold; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-pie text-primary"></i> Finance Dashboard</h2>
            <p class="text-muted">Complete financial overview of the university</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="fee_heads/index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Finance
            </a>
            <button onclick="window.location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-revenue">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">Total Revenue</div>
                        <div class="number">PKR <?php echo number_format($total_revenue, 0); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-coins"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-pending">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">Pending Fee</div>
                        <div class="number">PKR <?php echo number_format($pending_fee, 0); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-students">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">Total Students</div>
                        <div class="number"><?php echo number_format($total_students); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-payments">
                <div class="d-flex justify-content-between">
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
            <div class="stat-card bg-today">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">Today's Collection</div>
                        <div class="number">PKR <?php echo number_format($today_collection, 0); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card bg-week">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">This Week Collection</div>
                        <div class="number">PKR <?php echo number_format($week_collection, 0); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-week"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card bg-month">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="label">This Month Collection</div>
                        <div class="number">PKR <?php echo number_format($month_collection, 0); ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart & Status -->
    <div class="row">
        <!-- Chart -->
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
        <!-- Fee Status -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-pie-chart"></i> Fee Status
                </div>
                <div class="card-body">
                    <?php if (!empty($status_data)): ?>
                        <ul class="list-group">
                            <?php foreach ($status_data as $status => $count): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php 
                                    $badge_class = 'secondary';
                                    if ($status == 'Paid') $badge_class = 'success';
                                    elseif ($status == 'Partially Paid') $badge_class = 'warning';
                                    elseif ($status == 'Unpaid') $badge_class = 'danger';
                                    elseif ($status == 'Overdue') $badge_class = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                    <span class="badge bg-dark rounded-pill"><?php echo $count; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info">No fee records found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-history"></i> Recent Activities
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($log_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($log_result)): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($row['module']); ?></span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['action']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['details'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No recent activities found.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-3 text-center text-muted">
        <small>Finance Module - University MIS &copy; <?php echo date('Y'); ?></small>
    </div>
</div>

<script>
// Chart.js - Monthly Payment Chart
const ctx = document.getElementById('paymentChart').getContext('2d');
const chart = new Chart(ctx, {
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
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'PKR ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>