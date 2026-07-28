<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Fee Management';
include __DIR__ . '/../includes/header.php';

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'fee_structures'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS fee_structures (
                id INT PRIMARY KEY AUTO_INCREMENT,
                department_id INT,
                fee_type VARCHAR(50) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                academic_year VARCHAR(20),
                status VARCHAR(20) DEFAULT 'active',
                FOREIGN KEY (department_id) REFERENCES departments(department_id)
            )
        ");
    }
    
    $tables = $pdo->query("SHOW TABLES LIKE 'fee_payments'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS fee_payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT,
                fee_type VARCHAR(50) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method VARCHAR(20) DEFAULT 'cash',
                status VARCHAR(20) DEFAULT 'pending',
                FOREIGN KEY (student_id) REFERENCES admission_students(id)
            )
        ");
    }
    
    $structures = $pdo->query("
        SELECT fs.*, d.department_name 
        FROM fee_structures fs 
        LEFT JOIN departments d ON fs.department_id = d.department_id 
        ORDER BY fs.id DESC
    ")->fetchAll();
    
    $payments = $pdo->query("
        SELECT fp.*, s.student_name, s.student_id 
        FROM fee_payments fp 
        LEFT JOIN admission_students s ON fp.student_id = s.id 
        ORDER BY fp.payment_date DESC LIMIT 10
    ")->fetchAll();
    
} catch (PDOException $e) {
    $structures = [];
    $payments = [];
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-money-bill"></i> Fee Management</h4>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div>
            <h3>Fee Structures</h3>
            <p>Configured admission and program fee heads</p>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($structures)): ?>
            <div class="empty-state">
                <i class="fas fa-coins"></i>
                <h5>No Fee Structures Found</h5>
                <p>No program fee structures configured yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Department</th><th>Fee Type</th><th>Amount</th><th>Academic Year</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($structures as $fs): ?>
                        <tr>
                            <td><?= htmlspecialchars($fs['department_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($fs['fee_type'] ?? 'N/A') ?></td>
                            <td><strong><?= formatCurrency($fs['amount'] ?? 0) ?></strong></td>
                            <td><?= htmlspecialchars($fs['academic_year'] ?? 'N/A') ?></td>
                            <td>
                                <?php $status = strtolower($fs['status'] ?? 'active'); ?>
                                <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3>Recent Fee Payments</h3>
            <p>Latest payment transactions recorded</p>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fas fa-credit-card"></i>
                <h5>No Payments Recorded</h5>
                <p>No student fee payment transactions logged.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Student Name</th><th>Fee Type</th><th>Amount</th><th>Payment Date</th><th>Method</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['student_name'] ?? 'N/A') ?> <span class="muted">(<?= htmlspecialchars($p['student_id'] ?? 'N/A') ?>)</span></td>
                            <td><?= htmlspecialchars($p['fee_type'] ?? 'N/A') ?></td>
                            <td><strong><?= formatCurrency($p['amount'] ?? 0) ?></strong></td>
                            <td><?= isset($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A' ?></td>
                            <td><span class="status-badge" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);"><?= ucfirst(htmlspecialchars($p['payment_method'] ?? 'cash')) ?></span></td>
                            <td>
                                <?php $status = strtolower($p['status'] ?? 'pending'); ?>
                                <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>