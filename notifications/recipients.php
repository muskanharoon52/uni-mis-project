<?php
$pageTitle = 'Notification Recipients';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}
require_once __DIR__ . '/../includes/activity.php';
global $conn;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$n = null;
if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT n.*, u.full_name AS sender_name FROM notifications n LEFT JOIN users u ON u.user_id = n.sent_by WHERE n.notification_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $n = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$n) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container-fluid"><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Notification not found.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$type = isset($_GET['type']) && in_array($_GET['type'], ['Faculty', 'Students'], true) ? $_GET['type'] : 'Students';
$rows = [];
if ($type === 'Faculty') {
    $sql = "SELECT r.recipient_id, t.teacher_name AS name, t.email, d.department_name AS dept, r.is_read, r.created_at
            FROM notification_recipients r
            LEFT JOIN teachers t ON t.teacher_id = r.recipient_id
            LEFT JOIN departments d ON d.department_id = t.department_id
            WHERE r.notification_id = ? AND r.recipient_type = 'Faculty'
            ORDER BY t.teacher_name";
} else {
    $sql = "SELECT r.recipient_id, s.full_name AS name, s.email, s.batch_year, p.program_name AS program, d.department_name AS dept, r.is_read, r.created_at
            FROM notification_recipients r
            LEFT JOIN students s ON s.student_id = r.recipient_id
            LEFT JOIN programs p ON p.program_id = s.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            WHERE r.notification_id = ? AND r.recipient_type = 'Students'
            ORDER BY s.full_name";
}
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
mysqli_stmt_close($stmt);

$fCount = $sCount = 0;
$r = mysqli_query($conn, "SELECT recipient_type, COUNT(*) c FROM notification_recipients WHERE notification_id = $id GROUP BY recipient_type");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { if ($row['recipient_type'] === 'Faculty') $fCount = (int)$row['c']; else $sCount = (int)$row['c']; } }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-bell"></i> Recipients</h2>
                <span class="text-muted small"><?= htmlspecialchars($n['title']); ?></span>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <div class="panel">
            <h5 class="mb-2"><?= htmlspecialchars($n['title']); ?></h5>
            <p class="text-muted mb-1"><?= nl2br(htmlspecialchars($n['message'])); ?></p>
            <small class="text-muted">
                Audience: <span class="badge bg-info text-dark"><?= htmlspecialchars($n['audience_type']); ?></span>
                &nbsp;Sent by: <?= htmlspecialchars($n['sender_name'] ?? 'System'); ?>
                &nbsp;on <?= date('d M Y, h:i A', strtotime($n['created_at'])); ?>
            </small>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <ul class="nav nav-pills nav-sm">
                    <li class="nav-item">
                        <a class="nav-link <?= $type === 'Students' ? 'active' : '' ?>" href="recipients.php?id=<?= (int)$id; ?>&type=Students">
                            Students (<?= $sCount; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $type === 'Faculty' ? 'active' : '' ?>" href="recipients.php?id=<?= (int)$id; ?>&type=Faculty">
                            Faculty (<?= $fCount; ?>)
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($rows)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th><?= $type === 'Faculty' ? 'Designation' : 'Program / Department'; ?></th>
                                    <th><?= $type === 'Faculty' ? 'Department' : 'Batch'; ?></th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1; ?></td>
                                        <td><?= htmlspecialchars($row['name'] ?? '—'); ?></td>
                                        <td><small><?= $type === 'Faculty' ? htmlspecialchars($row['dept'] ?? '—') : htmlspecialchars(($row['program'] ?? '—') . ' / ' . ($row['dept'] ?? '—')); ?></small></td>
                                        <td><small><?= $type === 'Faculty' ? '—' : 'Batch ' . htmlspecialchars((string)$row['batch_year']); ?></small></td>
                                        <td><small><?= htmlspecialchars($row['email'] ?? '—'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state p-4">
                        <i class="fas fa-users"></i>
                        <h5>No <?= htmlspecialchars($type); ?> Recipients</h5>
                        <p class="text-muted">This notification has no <?= strtolower(htmlspecialchars($type)); ?> recipients.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
