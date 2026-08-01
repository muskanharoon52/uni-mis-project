<?php
$pageTitle = 'Activity Reports';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}
require_once __DIR__ . '/../includes/activity.php';
global $conn;

$error = '';
$success = '';

// ---- Filters ----
$f_module = $_GET['module'] ?? '';
$f_action = trim($_GET['action'] ?? '');
$f_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$f_date_from = $_GET['date_from'] ?? '';
$f_date_to = $_GET['date_to'] ?? '';

$where = "1=1";
$params = [];
$types = '';
if ($f_module !== '') { $where .= " AND l.module = ?"; $params[] = $f_module; $types .= 's'; }
if ($f_action !== '') { $where .= " AND l.action LIKE ?"; $params[] = "%$f_action%"; $types .= 's'; }
if ($f_user > 0) { $where .= " AND l.performed_by = ?"; $params[] = $f_user; $types .= 'i'; }
if ($f_date_from !== '') { $where .= " AND DATE(l.created_at) >= ?"; $params[] = $f_date_from; $types .= 's'; }
if ($f_date_to !== '') { $where .= " AND DATE(l.created_at) <= ?"; $params[] = $f_date_to; $types .= 's'; }

// ---- Load logs ----
$logs = [];
$sql = "SELECT l.*, u.full_name AS user_name, r.role_name
        FROM activity_logs l
        LEFT JOIN users u ON u.user_id = l.performed_by
        LEFT JOIN roles r ON r.role_id = u.role_id
        WHERE $where
        ORDER BY l.log_id DESC
        LIMIT 1000";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $logs[] = $row; }
    mysqli_stmt_close($stmt);
}

// ---- Distinct modules + users for filter dropdowns ----
$module_list = [];
$res = mysqli_query($conn, "SELECT DISTINCT module FROM activity_logs ORDER BY module");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $module_list[] = $r['module']; } }

$user_list = [];
$res = mysqli_query($conn, "SELECT DISTINCT l.performed_by, u.full_name FROM activity_logs l LEFT JOIN users u ON u.user_id = l.performed_by WHERE l.performed_by IS NOT NULL ORDER BY u.full_name");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $user_list[] = $r; } }

// ---- Summary stats ----
$stats = ['total' => 0, 'today' => 0, 'this_week' => 0, 'this_month' => 0, 'views' => 0, 'submits' => 0];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['total'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE DATE(created_at) = CURDATE()");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['today'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['this_week'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['this_month'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE action = 'Page View'");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['views'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE action = 'Form Submit'");
if ($res && ($r = mysqli_fetch_assoc($res))) $stats['submits'] = (int)$r['c'];

// ---- Export CSV ----
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_report_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Date', 'Module', 'Action', 'User', 'Reference Table', 'Reference ID', 'Details']);
    foreach ($logs as $row) {
        fputcsv($out, [
            $row['log_id'],
            $row['created_at'],
            $row['module'],
            $row['action'],
            $row['user_name'] ?? 'N/A',
            $row['reference_table'] ?? '',
            $row['reference_id'] ?? '',
            $row['details'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-chart-line"></i> Activity Reports</h2>
                <span class="text-muted small">Every click and action performed by SSO staff is recorded here.</span>
            </div>
            <a href="index.php?export=csv<?= $f_module !== '' ? '&module=' . urlencode($f_module) : ''; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Summary cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold" style="color:var(--accent);"><?= number_format($stats['total']); ?></div><small class="text-muted">Total Actions</small></div></div></div>
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-success"><?= number_format($stats['today']); ?></div><small class="text-muted">Today</small></div></div></div>
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-info"><?= number_format($stats['this_week']); ?></div><small class="text-muted">This Week</small></div></div></div>
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-warning"><?= number_format($stats['this_month']); ?></div><small class="text-muted">This Month</small></div></div></div>
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-secondary"><?= number_format($stats['views']); ?></div><small class="text-muted">Page Views</small></div></div></div>
            <div class="col-md-2 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-danger"><?= number_format($stats['submits']); ?></div><small class="text-muted">Form Submits</small></div></div></div>
        </div>

        <!-- Filters -->
        <div class="panel">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Module</label>
                    <select name="module" class="form-select">
                        <option value="">All Modules</option>
                        <?php foreach ($module_list as $m): ?>
                            <option value="<?= htmlspecialchars($m); ?>" <?= $f_module === $m ? 'selected' : ''; ?>><?= htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Action</label>
                    <input type="text" name="action" class="form-control" value="<?= htmlspecialchars($f_action); ?>" placeholder="e.g. Generate, Approved...">
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">User</label>
                    <select name="user" class="form-select">
                        <option value="0">All Users</option>
                        <?php foreach ($user_list as $u): ?>
                            <option value="<?= (int)$u['performed_by']; ?>" <?= $f_user === (int)$u['performed_by'] ? 'selected' : ''; ?>><?= htmlspecialchars($u['full_name'] ?? 'User #' . $u['performed_by']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($f_date_from); ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($f_date_to); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Log table -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Activity Log (<?= count($logs); ?> shown)</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
            <div class="card-body">
                <?php if (!empty($logs)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date &amp; Time</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Performed By</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= (int)$log['log_id']; ?></td>
                                        <td><small><?= date('d M Y, h:i A', strtotime($log['created_at'])); ?></small></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['module']); ?></span></td>
                                        <td>
                                            <?php
                                            $actBadge = 'bg-light text-dark border';
                                            $a = $log['action'];
                                            if ($a === 'Page View') $actBadge = 'bg-info text-dark';
                                            elseif ($a === 'Form Submit') $actBadge = 'bg-warning text-dark';
                                            elseif (stripos($a, 'Approved') !== false || $a === 'Published') $actBadge = 'bg-success';
                                            elseif (stripos($a, 'Reject') !== false) $actBadge = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $actBadge; ?>"><?= htmlspecialchars($a); ?></span>
                                        </td>
                                        <td><small><?= htmlspecialchars($log['user_name'] ?? 'System / N/A'); ?><?= $log['role_name'] ? ' <em class="text-muted">(' . htmlspecialchars($log['role_name']) . ')</em>' : ''; ?></small></td>
                                        <td style="max-width:340px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($log['details'] ?? '—'); ?>
                                                <?php if ($log['reference_table']): ?>
                                                    <br><em><?= htmlspecialchars($log['reference_table']); ?> #<?= htmlspecialchars((string)$log['reference_id']); ?></em>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <h5>No Activity Found</h5>
                        <p class="text-muted">No log entries match the current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
