<?php
$pageTitle = 'Student Fee Details';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<script>window.location.href="index.php?error=Invalid fee record ID";</script>';
    exit();
}

$student_fee_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT sf.student_fee_id, sf.total_amount, sf.paid_amount, sf.remaining_amount, sf.status, sf.generated_at, sf.due_date,
               s.full_name, s.roll_no, s.father_name, s.email, s.contact_no,
               d.department_name, sm.semester_name, ses.session_name
        FROM student_fee sf
        JOIN students s ON s.student_id = sf.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        WHERE sf.student_fee_id = '$student_fee_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo '<script>window.location.href="index.php?error=Fee record not found";</script>';
    exit();
}

$fee = mysqli_fetch_assoc($result);

$detail_sql = "SELECT fh.fee_head_name, sfd.amount, sfd.discount_amount, sfd.net_amount
               FROM student_fee_details sfd
               JOIN fee_heads fh ON fh.fee_head_id = sfd.fee_head_id
               WHERE sfd.student_fee_id = '$student_fee_id'";
$detail_result = mysqli_query($conn, $detail_sql);

$badgeClass = 'badge-outline';
if ($fee['status'] === 'Paid') $badgeClass = 'badge-active';
elseif ($fee['status'] === 'Partially Paid') $badgeClass = 'badge-pending';
elseif ($fee['status'] === 'Overdue') $badgeClass = 'badge-inactive';
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Student Fees</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-header"><h3>Student Information</h3></div>
        <div style="padding:22px;">
            <div style="display:grid;gap:10px;">
                <div><span class="muted" style="font-size:.82rem;display:block;">Name</span><strong><?= htmlspecialchars($fee['full_name']) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Roll No</span><?= htmlspecialchars($fee['roll_no'] ?? 'N/A') ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Father Name</span><?= htmlspecialchars($fee['father_name'] ?? 'N/A') ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Program</span><?= htmlspecialchars($fee['department_name']) ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Semester</span><?= htmlspecialchars($fee['semester_name']) ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Session</span><?= htmlspecialchars($fee['session_name']) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Fee Summary</h3></div>
        <div style="padding:22px;">
            <div style="display:grid;gap:10px;">
                <div><span class="muted" style="font-size:.82rem;display:block;">Generated</span><?= date('M j, Y', strtotime($fee['generated_at'])) ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Due Date</span><?= $fee['due_date'] ? date('M j, Y', strtotime($fee['due_date'])) : 'N/A' ?></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Total Amount</span><strong style="font-size:1.05rem;">PKR <?= number_format($fee['total_amount'], 2) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Paid Amount</span><strong style="color:var(--success);">PKR <?= number_format($fee['paid_amount'], 2) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Remaining</span><strong style="color:<?= $fee['remaining_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">PKR <?= number_format($fee['remaining_amount'], 2) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;display:block;">Status</span><span class="badge <?= $badgeClass ?>"><?= $fee['status'] ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Fee Breakdown</h3></div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee Head</th>
                    <th style="text-align:right">Amount (PKR)</th>
                    <th style="text-align:right">Discount (PKR)</th>
                    <th style="text-align:right">Net (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($detail_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($detail_result)): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['fee_head_name']) ?></td>
                        <td style="text-align:right;"><?= number_format($row['amount'], 2) ?></td>
                        <td style="text-align:right;"><?= number_format($row['discount_amount'], 2) ?></td>
                        <td style="text-align:right;font-weight:700;"><?= number_format($row['net_amount'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="muted text-center" style="padding:20px;">No fee details found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
