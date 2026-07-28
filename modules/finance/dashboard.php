<?php
$pageTitle = 'Finance Dashboard';
include __DIR__ . '/includes/header.php';

$stu_sql = "SELECT COUNT(*) AS total FROM students WHERE status = 'Active'";
$total_students = (int) mysqli_fetch_assoc(mysqli_query($conn, $stu_sql))['total'];

$rev_sql = "SELECT SUM(amount_paid) AS total FROM payments WHERE status = 'Success'";
$total_revenue = (float) mysqli_fetch_assoc(mysqli_query($conn, $rev_sql))['total'];

$pen_sql = "SELECT SUM(remaining_amount) AS total FROM student_fee WHERE remaining_amount > 0";
$pending_fee = (float) mysqli_fetch_assoc(mysqli_query($conn, $pen_sql))['total'];

$pay_sql = "SELECT COUNT(*) AS total FROM payments WHERE status = 'Success'";
$total_payments = (int) mysqli_fetch_assoc(mysqli_query($conn, $pay_sql))['total'];

$today = date('Y-m-d');
$today_collection = (float) mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) = '{$today}' AND status = 'Success'"))['total'];

$week_start = date('Y-m-d', strtotime('monday this week'));
$week_collection = (float) mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '{$week_start}' AND status = 'Success'"))['total'];

$month_start = date('Y-m-01');
$month_collection = (float) mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_paid) AS total FROM payments WHERE DATE(payment_date) >= '{$month_start}' AND status = 'Success'"))['total'];

$log_result = mysqli_query($conn, "SELECT module, action, details, created_at FROM activity_logs ORDER BY log_id DESC LIMIT 8");

$chart_result = mysqli_query($conn, "SELECT DATE_FORMAT(payment_date, '%b %Y') AS month, SUM(amount_paid) AS total FROM payments WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status = 'Success' GROUP BY YEAR(payment_date), MONTH(payment_date) ORDER BY payment_date ASC");
$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_labels[] = $row['month'];
    $chart_data[] = (float) $row['total'];
}

$pie_result = mysqli_query($conn, "SELECT payment_method, SUM(amount_paid) as total FROM payments WHERE status = 'Success' GROUP BY payment_method");
$pie_labels = [];
$pie_data = [];
while ($row = mysqli_fetch_assoc($pie_result)) {
    $pie_labels[] = $row['payment_method'];
    $pie_data[] = (float) $row['total'];
}

$recent_payments = mysqli_query($conn, "SELECT p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.status, s.full_name, s.roll_no FROM payments p JOIN students s ON s.student_id = p.student_id ORDER BY p.payment_id DESC LIMIT 5");

$top_pending = mysqli_query($conn, "SELECT sf.total_amount, sf.paid_amount, sf.remaining_amount, s.full_name, s.roll_no FROM student_fee sf JOIN students s ON s.student_id = sf.student_id WHERE sf.remaining_amount > 0 ORDER BY sf.remaining_amount DESC LIMIT 5");
?>

<style>
.finance-stat {
    border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--panel); padding: 20px 22px;
    display: flex; align-items: center; gap: 16px;
    transition: all .2s var(--ease);
}
.finance-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.finance-stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: grid; place-items: center; font-size: 1.1rem; flex-shrink: 0;
}
.finance-stat-body { flex: 1; min-width: 0; }
.finance-stat-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 2px; }
.finance-stat-value { font-size: 1.3rem; font-weight: 800; color: var(--text-strong); line-height: 1.1; }
.finance-stat-hint { font-size: .72rem; color: var(--text-secondary); margin-top: 2px; }
.finance-chart-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
.finance-chart-header { padding: 16px 20px; border-bottom: 1px solid var(--border); background: var(--bg); }
.finance-chart-header h3 { font-size: .92rem; font-weight: 700; color: var(--text-strong); margin: 0; }
.finance-chart-body { padding: 20px; }
</style>

<div class="greeting-card" style="background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 50%,#2563EB 100%);">
    <div style="flex:1;">
        <h1>Finance Dashboard</h1>
        <p style="margin:4px 0 0; font-size:.85rem; color:rgba(255,255,255,0.7);">
            Overview of collections, fees, and payment activity across the university.
        </p>
        <p class="dashboard-date" style="margin-top:8px;"><?= date('l, F j, Y') ?></p>
    </div>
</div>

<div class="stat-row" style="grid-template-columns:repeat(4,1fr);">
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:var(--success-bg); color:var(--success);">&#128176;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">Total Revenue</div>
            <div class="finance-stat-value">PKR <?= number_format($total_revenue) ?></div>
        </div>
    </div>
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:var(--danger-bg); color:var(--danger);">&#9888;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">Pending Fee</div>
            <div class="finance-stat-value">PKR <?= number_format($pending_fee) ?></div>
        </div>
    </div>
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:var(--accent-light); color:var(--accent);">&#127891;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">Active Students</div>
            <div class="finance-stat-value"><?= number_format($total_students) ?></div>
        </div>
    </div>
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:#F3E8FF; color:#7c3aed;">&#128196;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">Successful Payments</div>
            <div class="finance-stat-value"><?= number_format($total_payments) ?></div>
        </div>
    </div>
</div>

<div class="stat-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:var(--warning-bg); color:var(--warning);">&#128197;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">Today's Collection</div>
            <div class="finance-stat-value">PKR <?= number_format($today_collection) ?></div>
        </div>
    </div>
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:#F3E8FF; color:#7c3aed;">&#128197;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">This Week</div>
            <div class="finance-stat-value">PKR <?= number_format($week_collection) ?></div>
        </div>
    </div>
    <div class="finance-stat">
        <div class="finance-stat-icon" style="background:var(--success-bg); color:var(--success);">&#128197;</div>
        <div class="finance-stat-body">
            <div class="finance-stat-label">This Month</div>
            <div class="finance-stat-value">PKR <?= number_format($month_collection) ?></div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="finance-chart-card">
        <div class="finance-chart-header">
            <h3>Monthly Collection (Last 6 Months)</h3>
        </div>
        <div class="finance-chart-body">
            <canvas id="paymentChart" height="220"></canvas>
        </div>
    </div>
    <div class="finance-chart-card">
        <div class="finance-chart-header">
            <h3>Payment Methods</h3>
        </div>
        <div class="finance-chart-body">
            <canvas id="pieChart" height="220"></canvas>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h3>Recent Payments</h3>
                <a href="/uni-mis-project/modules/finance/payments/index.php" class="btn btn-sm btn-outline">View All</a>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Student</th><th style="text-align:right">Amount</th><th>Method</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if ($recent_payments && mysqli_num_rows($recent_payments) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($recent_payments)): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td style="text-align:right;font-weight:700;color:var(--success);">PKR <?= number_format($p['amount_paid']) ?></td>
                                <td><span class="badge badge-outline"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                                <td class="muted"><?= date('M j, g:i A', strtotime($p['payment_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="muted text-center" style="padding:24px;">No payments recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h3>Top Pending Dues</h3>
                <a href="/uni-mis-project/modules/finance/student_fee/index.php" class="btn btn-sm btn-outline">View All</a>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Student</th><th style="text-align:right">Total</th><th style="text-align:right">Paid</th><th style="text-align:right">Remaining</th></tr>
                </thead>
                <tbody>
                    <?php if ($top_pending && mysqli_num_rows($top_pending) > 0): ?>
                        <?php while ($pf = mysqli_fetch_assoc($top_pending)): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($pf['full_name']) ?></td>
                                <td style="text-align:right;">PKR <?= number_format($pf['total_amount']) ?></td>
                                <td style="text-align:right;color:var(--success);">PKR <?= number_format($pf['paid_amount']) ?></td>
                                <td style="text-align:right;font-weight:700;color:var(--danger);">PKR <?= number_format($pf['remaining_amount']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="muted text-center" style="padding:24px;">No pending dues.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Activity</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Module</th><th>Action</th><th>Details</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php if ($log_result && mysqli_num_rows($log_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($log_result)): ?>
                        <tr>
                            <td><span class="badge badge-active"><?= htmlspecialchars($row['module']) ?></span></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['action']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['details'] ?? 'N/A') ?></td>
                            <td class="muted"><?= date('M j, g:i A', strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="muted text-center" style="padding:24px;">No recent activity.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('paymentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Collection (PKR)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(37, 99, 235, 0.7)',
            borderColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { callback: v => 'PKR ' + v.toLocaleString(), font: { size: 11 } }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($pie_labels) ?>,
        datasets: [{
            data: <?= json_encode($pie_data) ?>,
            backgroundColor: ['#2563EB', '#059669', '#D97706', '#7C3AED', '#DC2626'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 12 } } } }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
