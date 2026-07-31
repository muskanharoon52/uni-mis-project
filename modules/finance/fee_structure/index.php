<?php
$pageTitle = 'Fee Structures';
include __DIR__ . '/../includes/header.php';

// Fixed SQL to remove errors regarding missing columns
// We simply display the valid data we have: Department, Status, and Amount.
$sql = "SELECT fs.fee_structure_id, fs.amount, fs.status, d.department_name 
        FROM fee_structures fs
        JOIN departments d ON d.department_id = fs.department_id 
        WHERE fs.status = 'Active'
        ORDER BY fs.fee_structure_id DESC";
$result = mysqli_query($conn, $sql);

// If query fails, display error safely
if (!$result) {
    die("MySQL Error: " . mysqli_error($conn));
}
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>Fee Structures</h3>
            <span class="badge badge-outline">&#128274; Read-Only</span>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Program/Department</th>
                    <th style="text-align:right">Total Amount</th>
                    <th>Status</th>
                    <!-- ACTION COLUMN HAS BEEN REMOVED -->
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['department_name']) ?></td>
                            <td style="text-align:right;font-weight:700;">PKR <?= number_format($row['amount'] ?? 0, 2) ?></td>
                            <td><span class="badge <?= $row['status'] === 'Active' ? 'badge-active' : 'badge-outline' ?>"><?= $row['status'] ?></span></td>
                            <!-- VIEW BUTTON AND ACTION TD HAS BEEN REMOVED -->
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="muted text-center" style="padding:24px;">No fee structures found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border); font-size:.8rem; color:var(--muted);">
        Note: Fee structures are read-only. Please use the <a href="../fee_heads/index.php" style="color:var(--accent);font-weight:600;">Fee Heads</a> module for management.
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>