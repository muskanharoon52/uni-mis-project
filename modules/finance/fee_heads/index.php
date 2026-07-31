<?php
$pageTitle = 'Fee Heads';
include __DIR__ . '/../includes/header.php';

$sql = "SELECT * FROM fee_heads WHERE deleted_at IS NULL ORDER BY fee_head_id DESC";
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
            <h3>Fee Heads</h3>
            <a href="add.php" class="btn btn-primary">+ Add Fee Head</a>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Amount (Rs)</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['fee_head_name']) ?></td>
                            <td style="font-weight:700;color:#2563eb;">PKR <?= number_format($row['amount'] ?? 0, 2) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['description'] ?? 'N/A') ?></td>
                            <td><span class="badge <?= $row['status'] === 'Active' ? 'badge-active' : 'badge-outline' ?>"><?= $row['status'] ?></span></td>
                            <td class="muted"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= $row['fee_head_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <a href="delete.php?id=<?= $row['fee_head_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this fee head?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="muted text-center" style="padding:24px;">No fee heads found. <a href="add.php" style="color:var(--accent);font-weight:600;">Add one now.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>