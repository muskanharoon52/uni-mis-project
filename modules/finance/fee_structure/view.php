<?php
$pageTitle = 'Fee Structure Details';
include_once __DIR__ . '/../includes/header.php';

$sql = "SELECT fs.fee_structure_id, fs.total_amount, fs.status,
               d.department_name, s.session_name, sm.semester_name,
               fsd.fee_head_id, fsd.amount, fh.fee_head_name
        FROM fee_structures fs
        JOIN departments d ON d.department_id = fs.program_id
        JOIN sessions s ON s.session_id = fs.session_id
        JOIN semesters sm ON sm.semester_id = fs.semester_id
        JOIN fee_structure_details fsd ON fsd.fee_structure_id = fs.fee_structure_id
        JOIN fee_heads fh ON fh.fee_head_id = fsd.fee_head_id
        WHERE fs.status = 'Active'
        ORDER BY fs.fee_structure_id DESC, fsd.fee_head_id";
$result = mysqli_query($conn, $sql);

$structures = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['fee_structure_id'];
    if (!isset($structures[$id])) {
        $structures[$id] = [
            'program' => $row['department_name'],
            'session' => $row['session_name'],
            'semester' => $row['semester_name'],
            'total' => $row['total_amount'],
            'status' => $row['status'],
            'heads' => []
        ];
    }
    $structures[$id]['heads'][] = ['name' => $row['fee_head_name'], 'amount' => $row['amount']];
}
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Structures</a>
</div>

<?php if (empty($structures)): ?>
    <div class="alert alert-info">No fee structures found. SSO module will create them.</div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <?php foreach ($structures as $id => $data): ?>
            <div class="card">
                <div class="card-header">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <h3><?= htmlspecialchars($data['program']) ?></h3>
                        <span class="badge badge-active"><?= $data['status'] ?></span>
                    </div>
                </div>
                <div style="padding:22px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
                        <div><span class="muted" style="font-size:.82rem;display:block;">Session</span><?= htmlspecialchars($data['session']) ?></div>
                        <div><span class="muted" style="font-size:.82rem;display:block;">Semester</span><?= htmlspecialchars($data['semester']) ?></div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Fee Head</th><th style="text-align:right">Amount (PKR)</th></tr></thead>
                            <tbody>
                                <?php foreach ($data['heads'] as $head): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($head['name']) ?></td>
                                        <td style="text-align:right;"><?= number_format($head['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--navy);color:white;font-weight:700;">
                                    <td>Total</td>
                                    <td style="text-align:right;"><?= number_format($data['total'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div style="font-size:.78rem;color:var(--muted);margin-top:12px;">Read-only. To modify, contact SSO staff.</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
