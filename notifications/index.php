<?php
$pageTitle = 'Sent Notifications';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}
require_once __DIR__ . '/../includes/activity.php';
global $conn;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalRes = mysqli_query($conn, "SELECT COUNT(*) c FROM notifications");
$totalRows = $totalRes ? (int)mysqli_fetch_assoc($totalRes)['c'] : 0;
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$list = [];
$sql = "SELECT n.*, u.full_name AS sender_name
        FROM notifications n
        LEFT JOIN users u ON u.user_id = n.sent_by
        ORDER BY n.notification_id DESC
        LIMIT $perPage OFFSET $offset";
$res = mysqli_query($conn, $sql);
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $list[] = $r; } }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-bell"></i> Notifications</h2>
                <span class="text-muted small">Notifications shared with faculty and students.</span>
            </div>
            <a href="send.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Send Notification</a>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Sent Notifications (<?= $totalRows; ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($list)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Audience</th>
                                    <th>Recipients</th>
                                    <th>Sent By</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $n): ?>
                                    <tr>
                                        <td><?= (int)$n['notification_id']; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($n['title']); ?></strong>
                                            <?php if ($n['message']): ?>
                                                <div class="small text-muted text-truncate" style="max-width:360px;"><?= htmlspecialchars($n['message']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge = $n['audience_type'] === 'Both' ? 'bg-info text-dark' : ($n['audience_type'] === 'Faculty' ? 'bg-secondary' : 'bg-success');
                                            ?>
                                            <span class="badge <?= $badge; ?>"><?= htmlspecialchars($n['audience_type']); ?></span>
                                        </td>
                                        <td><span class="badge bg-primary"><?= (int)$n['recipient_count']; ?></span></td>
                                        <td><small><?= htmlspecialchars($n['sender_name'] ?? 'System'); ?></small></td>
                                        <td><small><?= date('d M Y, h:i A', strtotime($n['created_at'])); ?></small></td>
                                        <td><a href="recipients.php?id=<?= (int)$n['notification_id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="p-3">
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="index.php?page=<?= $p; ?>"><?= $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state p-4">
                        <i class="fas fa-bell"></i>
                        <h5>No Notifications Sent</h5>
                        <p class="text-muted">Use the Send Notification button to share an announcement.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
