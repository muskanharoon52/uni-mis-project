<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Fee Management';
include __DIR__ . '/../includes/header.php';

$message = '';
$error = '';

try {
    // ============================================
    // 1. CREATE TABLES IF NOT EXIST
    //    (Other modules depend on fee_structures,
    //     so we never drop it here.)
    // ============================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fee_structures (
            fee_structure_id INT PRIMARY KEY AUTO_INCREMENT,
            department_id INT,
            fee_type VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            academic_year VARCHAR(20),
            status VARCHAR(20) DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fee_payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT,
            fee_type VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(20) DEFAULT 'cash',
            status VARCHAR(20) DEFAULT 'pending'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // ============================================
    // 2. INSERT DEMO DATA (only if empty)
    // ============================================
    $fsCount = $pdo->query("SELECT COUNT(*) FROM fee_structures")->fetchColumn();
    if ($fsCount == 0) {
        $pdo->exec("
            INSERT INTO fee_structures (department_id, fee_type, amount, academic_year, status) VALUES
            (1, 'Tuition Fee', 45000.00, '2026', 'active'),
            (1, 'Admission Fee', 15000.00, '2026', 'active'),
            (1, 'Library Fee', 5000.00, '2026', 'active'),
            (2, 'Tuition Fee', 55000.00, '2026', 'active'),
            (2, 'Admission Fee', 20000.00, '2026', 'active'),
            (2, 'Lab Fee', 8000.00, '2026', 'active'),
            (3, 'Tuition Fee', 40000.00, '2026', 'active'),
            (3, 'Sports Fee', 3000.00, '2026', 'inactive')
        ");
    }

    $fpCount = $pdo->query("SELECT COUNT(*) FROM fee_payments")->fetchColumn();
    if ($fpCount == 0) {
        $pdo->exec("
            INSERT INTO fee_payments (student_id, fee_type, amount, payment_date, payment_method, status) VALUES
            (25, 'Tuition Fee', 45000.00, '2026-01-15', 'bank_transfer', 'completed'),
            (26, 'Tuition Fee', 55000.00, '2026-01-20', 'cash', 'completed'),
            (27, 'Admission Fee', 15000.00, '2026-02-01', 'cheque', 'pending'),
            (25, 'Library Fee', 5000.00, '2026-02-10', 'cash', 'completed'),
            (28, 'Tuition Fee', 40000.00, '2026-02-15', 'bank_transfer', 'completed'),
            (26, 'Lab Fee', 8000.00, '2026-03-01', 'online', 'completed'),
            (27, 'Tuition Fee', 45000.00, '2026-03-10', 'bank_transfer', 'pending'),
            (29, 'Admission Fee', 20000.00, '2026-03-15', 'cash', 'completed')
        ");
    }

    $structures = $pdo->query("
        SELECT fs.*, d.department_name
        FROM fee_structures fs
        LEFT JOIN departments d ON fs.department_id = d.department_id
        ORDER BY fs.fee_structure_id DESC
    ")->fetchAll();

    $payments = $pdo->query("
        SELECT fp.*, s.full_name AS student_name, s.student_id
        FROM fee_payments fp
        LEFT JOIN students s ON fp.student_id = s.student_id
        ORDER BY fp.payment_date DESC LIMIT 10
    ")->fetchAll();

    $message = "Fee structures loaded successfully!";

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
    $structures = [];
    $payments = [];
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-money-bill"></i> Fee Management</h4>
    </div>
    <div class="page-header-actions">
        <a href="?add_demo=1" class="btn btn-primary" onclick="return confirm('Reset database and add demo data?')">
            <i class="fas fa-database"></i> Reset & Add Demo Data
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

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
                <p>No program fee structures configured yet. Click <strong>"Reset & Add Demo Data"</strong> above.</p>
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