<?php
$pageTitle = 'Activity Logs';
include __DIR__ . '/../includes/header.php';

$module_filter = isset($_GET['module']) ? mysqli_real_escape_string($conn, $_GET['module']) : '';
$action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

$where = "1=1";
if (!empty($module_filter)) $where .= " AND module = '$module_filter'";
if (!empty($action_filter)) $where .= " AND action LIKE '%$action_filter%'";
if (!empty($date_filter)) $where .= " AND DATE(created_at) = '$date_filter'";

$result = mysqli_query($conn, "SELECT log_id, module, action, reference_table, reference_id, details, created_at FROM activity_logs WHERE $where ORDER BY log_id DESC LIMIT 100");
$error_msg = null;
if (!$result) $error_msg = mysqli_error($conn);

$module_result = mysqli_query($conn, "SELECT DISTINCT module FROM activity_logs ORDER BY module");
?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Filter Logs</h3>
    </div>
    <form method="get" style="padding:18px 22px;">
        <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr auto;">
            <div class="field" style="margin-bottom:0;">
                <label>Module</label>
                <select name="module">
                    <option value="">All Modules</option>
                    <?php if ($module_result && mysqli_num_rows($module_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($module_result)): ?>
                            <option value="<?= htmlspecialchars($row['module']) ?>" <?= $module_filter === $row['module'] ? 'selected' : '' ?>><?= htmlspecialchars($row['module']) ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Action</label>
                <input type="text" name="action" placeholder="Search action..." value="<?= htmlspecialchars($action_filter) ?>">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div style="display:flex;align-items:end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>Activity Logs (Last 100)</h3>
    </div>
    <?php if ($error_msg): ?>
        <div class="alert alert-error" style="margin:16px 22px;"><?= htmlspecialchars($error_msg) ?></div>
    <?php elseif ($result && mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>#</th><th>Module</th><th>Action</th><th>Details</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php $count = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><span class="badge badge-active"><?= htmlspecialchars($row['module']) ?></span></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['action']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['details'] ?? 'N/A') ?></td>
                            <td class="muted"><?= date('M j, g:i A', strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted text-center" style="padding:24px;">No activity logs found.</p>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
