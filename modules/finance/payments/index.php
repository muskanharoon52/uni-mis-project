<?php
$pageTitle = 'Payment History';
include __DIR__ . '/../includes/header.php';

// ── Filters ─────────────────────────────────────────────
$fq     = trim($_GET['q'] ?? '');
$fdept  = (int)($_GET['dept'] ?? 0);
$fmethod= trim($_GET['method'] ?? '');
$fstatus= trim($_GET['status'] ?? '');
$ffrom  = trim($_GET['from'] ?? '');
$fto    = trim($_GET['to'] ?? '');
$fstudent = (int)($_GET['student'] ?? 0);

// Student-level filter (applies to both tables)
$ledgerWhere = [];
if ($fq !== '') {
    $sq = mysqli_real_escape_string($conn, $fq);
    $ledgerWhere[] = "(s.full_name LIKE '%$sq%' OR s.roll_no LIKE '%$sq%')";
}
if ($fdept > 0) {
    $ledgerWhere[] = 'pr.department_id = ' . $fdept;
}
if ($fstudent > 0) {
    $ledgerWhere[] = 's.student_id = ' . $fstudent;
}
$ledgerWhereSql = $ledgerWhere ? ' AND ' . implode(' AND ', $ledgerWhere) : '';

// Payment-level filter (applies to payment lines + KPIs)
$payWhere = $ledgerWhere;
if ($fmethod !== '') {
    $m = mysqli_real_escape_string($conn, $fmethod);
    $payWhere[] = "p.payment_method = '$m'";
}
if ($fstatus !== '') {
    $st = mysqli_real_escape_string($conn, $fstatus);
    $payWhere[] = "p.status = '$st'";
}
if ($ffrom !== '') {
    $f = mysqli_real_escape_string($conn, $ffrom);
    $payWhere[] = "DATE(p.payment_date) >= '$f'";
}
if ($fto !== '') {
    $t = mysqli_real_escape_string($conn, $fto);
    $payWhere[] = "DATE(p.payment_date) <= '$t'";
}
$payWhereSql = $payWhere ? ' AND ' . implode(' AND ', $payWhere) : '';

// ── KPIs ────────────────────────────────────────────────
$agg = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(p.amount_paid),0) AS collected,
           COUNT(p.payment_id) AS payments,
           SUM(CASE WHEN p.status='Success' THEN 1 ELSE 0 END) AS successful,
           COUNT(DISTINCT p.student_id) AS students
    FROM payments p
    JOIN students s ON s.student_id = p.student_id
    LEFT JOIN programs pr ON pr.program_id = s.program_id
    WHERE 1 $payWhereSql"));

$outstanding = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(sf.remaining_amount),0) AS total
    FROM student_fee sf
    JOIN students s ON s.student_id = sf.student_id
    LEFT JOIN programs pr ON pr.program_id = s.program_id
    WHERE 1 $ledgerWhereSql"));

// ── Student Fee Ledger ──────────────────────────────────
$ledger = mysqli_query($conn, "
    SELECT s.student_id, s.full_name, s.roll_no,
           pr.department_id, d.department_name,
           SUM(sf.total_amount)    AS total_amount,
           SUM(sf.paid_amount)     AS paid_amount,
           SUM(sf.remaining_amount)AS remaining_amount,
           (SELECT COUNT(*) FROM payments p2 WHERE p2.student_id = s.student_id) AS pay_count,
           (SELECT MAX(p2.payment_date) FROM payments p2 WHERE p2.student_id = s.student_id) AS last_pay_date
    FROM student_fee sf
    JOIN students s ON s.student_id = sf.student_id
    LEFT JOIN programs pr ON pr.program_id = s.program_id
    LEFT JOIN departments d ON d.department_id = pr.department_id
    WHERE 1 $ledgerWhereSql
    GROUP BY s.student_id, s.full_name, s.roll_no, pr.department_id, d.department_name
    ORDER BY total_amount DESC");

// ── Payment History lines ───────────────────────────────
$payments = mysqli_query($conn, "
    SELECT p.payment_id, p.amount_paid, p.payment_method, p.transaction_ref,
           p.payment_date, p.status,
           s.full_name, s.roll_no, d.department_name,
           sf.total_amount, sf.paid_amount, sf.remaining_amount,
           u.full_name AS received_by_name
    FROM payments p
    JOIN students s ON s.student_id = p.student_id
    LEFT JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
    LEFT JOIN programs pr ON pr.program_id = s.program_id
    LEFT JOIN departments d ON d.department_id = pr.department_id
    LEFT JOIN users u ON u.user_id = p.received_by
    WHERE 1 $payWhereSql
    ORDER BY p.payment_id DESC");

$feeStatusBadge = function ($status) {
    $map = ['Paid' => 'badge-active', 'Overdue' => 'badge-inactive', 'Unpaid' => 'badge-inactive'];
    $cls = $map[$status] ?? 'badge-outline';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status) . '</span>';
};
$preserve = [];
foreach (['q', 'dept', 'method', 'status', 'from', 'to', 'student'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $preserve[$k] = $_GET[$k];
}
$qs = http_build_query($preserve);
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<?php if ($fstudent > 0): ?>
    <div style="margin-bottom:16px;">
        <a href="index.php?<?= htmlspecialchars(http_build_query(array_diff_key($preserve, ['student' => 1]))) ?>" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to all students</a>
    </div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--success-bg);color:#065f46;">PKR</div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Total Collected</div>
            <div class="stat-card-v2-value"><?= number_format($agg['collected'], 0) ?></div>
            <div class="stat-card-v2-hint"><?= (int)$agg['payments'] ?> payment(s), <?= (int)$agg['successful'] ?> successful</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--info-bg);color:#1e40af;">&#128176;</div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Students Covered</div>
            <div class="stat-card-v2-value"><?= (int)$agg['students'] ?></div>
            <div class="stat-card-v2-hint">with at least one payment</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--accent-light);color:var(--accent);">&#128202;</div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Outstanding Fee</div>
            <div class="stat-card-v2-value"><?= number_format($outstanding['total'] ?? 0, 0) ?></div>
            <div class="stat-card-v2-hint">remaining across filtered students</div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--danger-bg);color:#991b1b;">&#128202;</div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Reversed</div>
            <div class="stat-card-v2-value"><?= (int)$agg['payments'] - (int)$agg['successful'] ?></div>
            <div class="stat-card-v2-hint">cancelled / reversed payments</div>
        </div>
    </div>
</div>

<form method="get" action="index.php" class="filter-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($fq) ?>" placeholder="Search student name or roll no...">
    <select name="dept" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;background:var(--panel);color:var(--text);">
        <option value="">All Departments</option>
        <?php
        $depts = mysqli_query($conn, "SELECT department_id, department_name FROM departments ORDER BY department_name");
        while ($d = mysqli_fetch_assoc($depts)) {
            $sel = ($fdept === (int)$d['department_id']) ? ' selected' : '';
            echo '<option value="' . (int)$d['department_id'] . '"' . $sel . '>' . htmlspecialchars($d['department_name']) . '</option>';
        }
        ?>
    </select>
    <select name="method" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;background:var(--panel);color:var(--text);">
        <option value="">All Methods</option>
        <?php foreach (['Cash', 'Bank', 'Card', 'Online'] as $mth): ?>
            <option value="<?= $mth ?>"<?= $fmethod === $mth ? ' selected' : '' ?>><?= $mth ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;background:var(--panel);color:var(--text);">
        <option value="">All Statuses</option>
        <?php foreach (['Success', 'Reversed'] as $st2): ?>
            <option value="<?= $st2 ?>"<?= $fstatus === $st2 ? ' selected' : '' ?>><?= $st2 ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="from" value="<?= htmlspecialchars($ffrom) ?>" title="From date" style="flex:0 0 auto;min-width:0;">
    <input type="date" name="to" value="<?= htmlspecialchars($fto) ?>" title="To date" style="flex:0 0 auto;min-width:0;">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="index.php" class="btn btn-outline">Reset</a>
    <a href="add.php" class="btn btn-primary" style="margin-left:auto;">+ Receive Payment</a>
</form>

<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3>Student Fee Ledger</h3>
            <span class="muted" style="font-size:.78rem;">Fee totals per student (filtered)</span>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Department</th>
                    <th style="text-align:right">Total Fee</th>
                    <th style="text-align:right">Paid</th>
                    <th style="text-align:right">Remaining</th>
                    <th>Fee Status</th>
                    <th style="text-align:center">Payments</th>
                    <th>Last Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($ledger) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($ledger)): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td class="muted"><?= htmlspecialchars($row['department_name'] ?? 'N/A') ?></td>
                            <td style="text-align:right;"><?= number_format($row['total_amount'], 2) ?></td>
                            <td style="text-align:right;color:var(--success);font-weight:600;"><?= number_format($row['paid_amount'], 2) ?></td>
                            <td style="text-align:right;color:<?= $row['remaining_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:600;"><?= number_format($row['remaining_amount'], 2) ?></td>
                            <td><?= $feeStatusBadge($row['remaining_amount'] > 0 ? 'Unpaid' : 'Paid') ?></td>
                            <td style="text-align:center;">
                                <?php if ($row['pay_count'] > 0): ?>
                                    <a href="index.php?<?= htmlspecialchars(http_build_query(array_merge($preserve, ['student' => (int)$row['student_id']]))) ?>" class="badge badge-active"><?= (int)$row['pay_count'] ?> payment(s)</a>
                                <?php else: ?>
                                    <span class="muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= $row['last_pay_date'] ? date('M j, Y', strtotime($row['last_pay_date'])) : '—' ?></td>
                            <td><a href="index.php?<?= htmlspecialchars(http_build_query(array_merge($preserve, ['student' => (int)$row['student_id']]))) ?>" class="btn btn-sm btn-outline">Payments</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="muted text-center" style="padding:24px;">No student fee records match the filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3>Payment History</h3>
            <?php if ($fstudent > 0): ?>
                <span class="muted" style="font-size:.78rem;">Showing payment lines for filtered student</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Department</th>
                    <th style="text-align:right">Amount (PKR)</th>
                    <th>Method</th>
                    <th>Ref</th>
                    <th>Date</th>
                    <th>Received By</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($payments) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td class="muted"><?= htmlspecialchars($row['department_name'] ?? 'N/A') ?></td>
                            <td style="text-align:right;font-weight:700;"><?= number_format($row['amount_paid'], 2) ?></td>
                            <td><span class="badge badge-outline"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                            <td class="muted"><?= htmlspecialchars($row['transaction_ref'] ?? '—') ?></td>
                            <td class="muted"><?= date('M j, Y g:i A', strtotime($row['payment_date'])) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['received_by_name'] ?? 'System') ?></td>
                            <td><span class="badge <?= $row['status'] === 'Success' ? 'badge-active' : 'badge-inactive' ?>"><?= $row['status'] ?></span></td>
                            <td><a href="view.php?id=<?= (int)$row['payment_id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="muted text-center" style="padding:24px;">No payments found. <a href="add.php" style="color:var(--accent);font-weight:600;">Receive a payment now.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
