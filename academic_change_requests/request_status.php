<?php
$acrActive = 'request_status';
$pageTitle = 'Request Status';
require_once __DIR__ . '/_common.php';

// Optional: allow status update (mark request processed/approved)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = (int)($_POST['req_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    if ($req_id > 0 && in_array($new_status, ['Pending', 'Approved', 'Rejected', 'Applied'], true)) {
        $stmt = mysqli_prepare($conn, "UPDATE acr_requests SET status = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $new_status, $req_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = "Request #$req_id marked as $new_status.";
            log_activity('Academic Change Requests', 'Status -> ' . $new_status, 'acr_requests', $req_id, "Request #$req_id marked as $new_status");
        }
    } else {
        $error = "Invalid request or status.";
    }
}

// Filters
$f_type = $_GET['type'] ?? '';
$f_status = $_GET['status'] ?? '';
$f_search = trim($_GET['search'] ?? '');
$allowed_types = ['Section Change', 'Department Transfer', 'Program Change', 'Course Add/Drop', 'Course Withdrawal'];
$allowed_statuses = ['Pending', 'Approved', 'Rejected', 'Applied'];

$sql = "SELECT r.*, u.full_name AS requested_by_name
        FROM acr_requests r
        LEFT JOIN users u ON u.user_id = r.requested_by
        WHERE 1=1";
$params = [];
$types = '';
if ($f_type !== '' && in_array($f_type, $allowed_types, true)) {
    $sql .= " AND r.request_type = ?"; $params[] = $f_type; $types .= 's';
}
if ($f_status !== '' && in_array($f_status, $allowed_statuses, true)) {
    $sql .= " AND r.status = ?"; $params[] = $f_status; $types .= 's';
}
if ($f_search !== '') {
    $sql .= " AND (r.student_name LIKE ? OR r.student_ref LIKE ? OR r.old_value LIKE ? OR r.new_value LIKE ?)";
    $like = '%' . $f_search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}
$sql .= " ORDER BY r.requested_at DESC, r.id DESC";

$requests = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $requests[] = $row; }
    mysqli_stmt_close($stmt);
}

$status_badge = [
    'Pending'  => 'status-pending',
    'Approved' => 'status-active',
    'Rejected' => 'status-inactive',
    'Applied'  => 'status-active',
];

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-clipboard-list"></i> Request Status</h2>
                <span class="text-muted small">Review the history and status of every academic change request.</span>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Academic Change Requests</a>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Filters -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Request Types</option>
                        <?php foreach ($allowed_types as $t): ?>
                            <option value="<?= htmlspecialchars($t); ?>" <?= $f_type === $t ? 'selected' : ''; ?>><?= htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($allowed_statuses as $st): ?>
                            <option value="<?= htmlspecialchars($st); ?>" <?= $f_status === $st ? 'selected' : ''; ?>><?= htmlspecialchars($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search student name, ID, old/new value..."
                           value="<?= htmlspecialchars($f_search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                </div>
            </form>
        </div>

        <!-- Requests table -->
        <div class="card mt-3">
            <div class="card-header"><h5>Change Requests (<?= count($requests); ?>)</h5></div>
            <div class="card-body">
                <?php if (!empty($requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Request Type</th>
                                    <th>Student</th>
                                    <th>Change</th>
                                    <th>Status</th>
                                    <th>Requested By</th>
                                    <th>Requested At</th>
                                    <th style="min-width:170px;">Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <tr>
                                        <td><?= (int)$r['id']; ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($r['request_type']); ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600;"><?= htmlspecialchars($r['student_name']); ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($r['student_ref']); ?> <?= $r['application_id'] ? '(App ' . (int)$r['application_id'] . ')' : ''; ?></small>
                                        </td>
                                        <td class="small">
                                            <div class="text-muted"><strike><?= htmlspecialchars($r['old_value']); ?></strike></div>
                                            <div style="color:var(--accent);font-weight:600;"><?= htmlspecialchars($r['new_value']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $status_badge[$r['status']] ?? 'status-pending'; ?>"><?= htmlspecialchars($r['status']); ?></span>
                                        </td>
                                        <td class="muted"><?= htmlspecialchars($r['requested_by_name'] ?? 'N/A'); ?></td>
                                        <td class="muted"><?= htmlspecialchars($r['requested_at']); ?></td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="req_id" value="<?= (int)$r['id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm">
                                                    <?php foreach ($allowed_statuses as $st): ?>
                                                        <option value="<?= htmlspecialchars($st); ?>" <?= $r['status'] === $st ? 'selected' : ''; ?>><?= htmlspecialchars($st); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h5>No Requests Found</h5>
                        <p class="text-muted">No change requests match the current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
