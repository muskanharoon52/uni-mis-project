<?php
$pageTitle = 'Student Fee Records';
include __DIR__ . '/../includes/header.php';

$sql = "SELECT sf.student_fee_id, sf.total_amount, sf.paid_amount, sf.remaining_amount, sf.status, sf.generated_at,
               s.full_name, s.roll_no, sm.semester_name, ses.session_name
        FROM student_fee sf
        JOIN students s ON s.student_id = sf.student_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        ORDER BY sf.student_fee_id DESC";
$result = mysqli_query($conn, $sql);
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
            <h3>Student Fees</h3>
            <div class="actions">
                <a href="generate.php" class="btn btn-primary">+ Generate Fee</a>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Semester</th>
                    <th>Session</th>
                    <th style="text-align:right">Total</th>
                    <th style="text-align:right">Paid</th>
                    <th style="text-align:right">Remaining</th>
                    <th>Status</th>
                    <th>Generated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['semester_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['session_name']) ?></td>
                            <td style="text-align:right;"><?= number_format($row['total_amount'], 2) ?></td>
                            <td style="text-align:right;color:var(--success);"><?= number_format($row['paid_amount'], 2) ?></td>
                            <td style="text-align:right;font-weight:700;color:<?= $row['remaining_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= number_format($row['remaining_amount'], 2) ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'badge-outline';
                                if ($row['status'] === 'Paid') $badgeClass = 'badge-active';
                                elseif ($row['status'] === 'Partially Paid') $badgeClass = 'badge-pending';
                                elseif ($row['status'] === 'Overdue') $badgeClass = 'badge-inactive';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </td>
                            <td class="muted"><?= date('M j, Y', strtotime($row['generated_at'])) ?></td>
                            <td><a href="view.php?id=<?= $row['student_fee_id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="muted text-center" style="padding:24px;">No student fees found. <a href="generate.php" style="color:var(--accent);font-weight:600;">Generate one now.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
