<?php
require_once '../../config/database.php';
$page_title = 'Fee Management';
include '../../includes/header.php';

// Check if fee tables exist, if not create them
try {
    // Check fee_structures table
    $tables = $pdo->query("SHOW TABLES LIKE 'fee_structures'")->fetchAll();
    if (empty($tables)) {
        // Create fee_structures table
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
    
    // Check fee_payments table
    $tables = $pdo->query("SHOW TABLES LIKE 'fee_payments'")->fetchAll();
    if (empty($tables)) {
        // Create fee_payments table
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
    
    // Get fee structures with department names
    $structures = $pdo->query("
        SELECT fs.*, d.department_name 
        FROM fee_structures fs 
        LEFT JOIN departments d ON fs.department_id = d.department_id 
        ORDER BY fs.id DESC
    ")->fetchAll();
    
    // Get recent payments
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
<!-- Find all ₹ and replace with PKR -->

<!-- In fee structures table -->
<td><strong><?= formatCurrency($fs['amount'] ?? 0) ?></strong></td>

<!-- In payments table -->
<td><?= formatCurrency($p['amount'] ?? 0) ?></td>
<div class="page-header"><h5><i class="fas fa-money-bill"></i> Fee Management</h5></div>

<!-- Fee Structures -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-list"></i> Fee Structures</h6>
    </div>
    <div class="card-body">
        <?php if (empty($structures)): ?>
            <div class="alert alert-info">No fee structures found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Department</th><th>Fee Type</th><th>Amount</th><th>Academic Year</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($structures as $fs): ?>
                        <tr>
                            <td><?= $fs['department_name'] ?? 'N/A' ?></td>
                            <td><?= $fs['fee_type'] ?? 'N/A' ?></td>
                            <td><strong>₹<?= number_format($fs['amount'] ?? 0, 2) ?></strong></td>
                            <td><?= $fs['academic_year'] ?? 'N/A' ?></td>
                            <td><span class="badge bg-<?= ($fs['status'] ?? 'active') == 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($fs['status'] ?? 'active') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h6 class="mb-0"><i class="fas fa-history"></i> Recent Payments</h6>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="alert alert-info">No payments recorded.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Date</th><th>Method</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?= $p['student_name'] ?? 'N/A' ?> (<?= $p['student_id'] ?? 'N/A' ?>)</td>
                            <td><?= $p['fee_type'] ?? 'N/A' ?></td>
                            <td>₹<?= number_format($p['amount'] ?? 0, 2) ?></td>
                            <td><?= isset($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A' ?></td>
                            <td><span class="badge bg-info"><?= ucfirst($p['payment_method'] ?? 'cash') ?></span></td>
                            <td><span class="badge bg-<?= ($p['status'] ?? 'pending') == 'paid' ? 'success' : 'warning' ?>"><?= ucfirst($p['status'] ?? 'pending') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>