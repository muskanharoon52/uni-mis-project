<?php
require_once '../../config/database.php';
$page_title = 'Reports';
include '../../includes/header.php';

try {
    // Get statistics using correct table names
    $total_apps = $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn();
    $total_students = $pdo->query("SELECT COUNT(*) FROM admission_students")->fetchColumn();
    
    // Check if fee_payments exists
    $fee_table = $pdo->query("SHOW TABLES LIKE 'fee_payments'")->fetchAll();
    if (!empty($fee_table)) {
        $total_paid = $pdo->query("SELECT SUM(amount) FROM fee_payments WHERE status='paid'")->fetchColumn() ?: 0;
    } else {
        $total_paid = 0;
    }
    
    // Department-wise applications
    $dept_stats = $pdo->query("
        SELECT d.department_name, COUNT(a.application_id) as total 
        FROM departments d 
        LEFT JOIN admission_applications a ON d.department_id = a.program_id 
        GROUP BY d.department_id
    ")->fetchAll();
    
    // Status-wise applications
    $status_stats = $pdo->query("
        SELECT application_status, COUNT(*) as total 
        FROM admission_applications 
        GROUP BY application_status
    ")->fetchAll();
    
    // Monthly applications (last 6 months)
    $monthly = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('M Y', strtotime("-$i months"));
        $count = $pdo->query("
            SELECT COUNT(*) FROM admission_applications 
            WHERE DATE_FORMAT(submitted_at, '%Y-%m') = '" . date('Y-m', strtotime("-$i months")) . "'
        ")->fetchColumn();
        $monthly[] = ['month' => $month, 'count' => $count];
    }
    
} catch (PDOException $e) {
    $total_apps = 0;
    $total_students = 0;
    $total_paid = 0;
    $dept_stats = [];
    $status_stats = [];
    $monthly = [];
}
?>
<!-- Find ₹ and replace with PKR -->
<div class="number"><?= formatCurrency($total_paid) ?></div>
<div class="page-header"><h5><i class="fas fa-chart-bar"></i> Reports Dashboard</h5></div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small>Total Applications</small><div class="number"><?= $total_apps ?></div></div>
                <div class="text-primary"><i class="fas fa-file-alt fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small>Total Students</small><div class="number"><?= $total_students ?></div></div>
                <div class="text-success"><i class="fas fa-user-graduate fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small>Total Fees Collected</small><div class="number">₹<?= number_format($total_paid, 0) ?></div></div>
                <div class="text-warning"><i class="fas fa-coins fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="page-header">
            <h6><i class="fas fa-building"></i> Applications by Department</h6>
            <table class="table table-hover">
                <thead><tr><th>Department</th><th>Applications</th></tr></thead>
                <tbody>
                    <?php if (empty($dept_stats)): ?>
                        <tr><td colspan="2" class="text-muted">No data</td></tr>
                    <?php else: ?>
                        <?php foreach($dept_stats as $ds): ?>
                        <tr><td><?= $ds['department_name'] ?? 'N/A' ?></td><td><span class="badge bg-primary"><?= $ds['total'] ?? 0 ?></span></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="page-header">
            <h6><i class="fas fa-chart-pie"></i> Applications by Status</h6>
            <table class="table table-hover">
                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                    <?php if (empty($status_stats)): ?>
                        <tr><td colspan="2" class="text-muted">No data</td></tr>
                    <?php else: ?>
                        <?php foreach($status_stats as $ss): ?>
                        <tr><td><span class="badge bg-<?= getStatusBadge($ss['application_status'] ?? 'pending') ?>"><?= $ss['application_status'] ?? 'N/A' ?></span></td>
                        <td><strong><?= $ss['total'] ?? 0 ?></strong></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($monthly)): ?>
<div class="page-header">
    <h6><i class="fas fa-chart-line"></i> Monthly Applications Trend</h6>
    <canvas id="trendChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly, 'month')) ?>,
        datasets: [{
            label: 'Applications',
            data: <?= json_encode(array_column($monthly, 'count')) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>