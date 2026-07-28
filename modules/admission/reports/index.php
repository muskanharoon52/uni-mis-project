<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Reports';
include __DIR__ . '/../includes/header.php';

try {
    $total_apps = $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn();
    $total_students = $pdo->query("SELECT COUNT(*) FROM admission_students")->fetchColumn();
    
    $fee_table = $pdo->query("SHOW TABLES LIKE 'fee_payments'")->fetchAll();
    if (!empty($fee_table)) {
        $total_paid = $pdo->query("SELECT SUM(amount) FROM fee_payments WHERE status='paid'")->fetchColumn() ?: 0;
    } else {
        $total_paid = 0;
    }
    
    $dept_stats = $pdo->query("
        SELECT d.department_name, COUNT(a.application_id) as total 
        FROM departments d 
        LEFT JOIN admission_applications a ON d.department_id = a.program_id 
        GROUP BY d.department_id
    ")->fetchAll();
    
    $status_stats = $pdo->query("
        SELECT application_status, COUNT(*) as total 
        FROM admission_applications 
        GROUP BY application_status
    ")->fetchAll();
    
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

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-chart-bar"></i> Reports & Analytics</h4>
    </div>
</div>

<div class="stat-row">
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--accent-light);color:var(--accent);"><i class="fas fa-file-alt"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Total Applications</div>
            <div class="stat-card-v2-value"><?= $total_apps ?></div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Total Enrolled Students</div>
            <div class="stat-card-v2-value"><?= $total_students ?></div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="fas fa-coins"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Total Fees Collected</div>
            <div class="stat-card-v2-value"><?= formatCurrency($total_paid) ?></div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Applications by Department</h3>
                <p>Distribution across university academic departments</p>
            </div>
        </div>
        <div class="card-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Department</th><th>Application Count</th></tr></thead>
                    <tbody>
                        <?php if (empty($dept_stats)): ?>
                            <tr><td colspan="2"><div class="empty-state" style="padding:24px 0;"><i class="fas fa-building"></i><h5>No Data</h5><p>No department metrics available</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach($dept_stats as $ds): ?>
                            <tr>
                                <td><?= htmlspecialchars($ds['department_name'] ?? 'N/A') ?></td>
                                <td><span class="status-badge" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);"><?= $ds['total'] ?? 0 ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Applications by Status</h3>
                <p>Breakdown of processing decision states</p>
            </div>
        </div>
        <div class="card-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Status State</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php if (empty($status_stats)): ?>
                            <tr><td colspan="2"><div class="empty-state" style="padding:24px 0;"><i class="fas fa-chart-pie"></i><h5>No Data</h5><p>No status metrics available</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach($status_stats as $ss): ?>
                            <tr>
                                <td><span class="status-badge <?= htmlspecialchars($ss['application_status'] ?? 'Submitted') ?>"><?= htmlspecialchars($ss['application_status'] ?? 'N/A') ?></span></td>
                                <td><strong><?= $ss['total'] ?? 0 ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($monthly)): ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3>Monthly Application Submissions</h3>
            <p>6-month trend breakdown of incoming admissions</p>
        </div>
    </div>
    <div class="card-content">
        <div style="position:relative;height:240px;width:100%;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
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
            borderColor: '#2563EB',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#E8EDF4' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>