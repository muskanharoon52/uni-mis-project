<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';

$module_filter = isset($_GET['module']) ? mysqli_real_escape_string($conn, $_GET['module']) : '';
$action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

$where = "1=1";
if (!empty($module_filter)) {
    $where .= " AND module = '$module_filter'";
}
if (!empty($action_filter)) {
    $where .= " AND action LIKE '%$action_filter%'";
}
if (!empty($date_filter)) {
    $where .= " AND DATE(created_at) = '$date_filter'";
}

$sql = "SELECT 
        log_id,
        module,
        action,
        reference_table,
        reference_id,
        details,
        created_at,
        NULL AS performed_by_name
        FROM activity_logs
        WHERE $where
        ORDER BY log_id DESC
        LIMIT 100";

$result = mysqli_query($conn, $sql);
if (!$result) {
    $error_msg = mysqli_error($conn);
    $result = false;
}

$module_sql = "SELECT DISTINCT module FROM activity_logs ORDER BY module";
$module_result = mysqli_query($conn, $module_sql);
if (!$module_result) {
    $module_result = false;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-history text-primary"></i> Activity Logs</h2>
    <a href="../fee_heads/index.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Finance
    </a>
</div>

<!-- Filter Section -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <i class="fas fa-filter"></i> Filter Logs
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Module</label>
                <select class="form-select" name="module">
                    <option value="">All Modules</option>
                    <?php 
                    if ($module_result && mysqli_num_rows($module_result) > 0):
                        while($row = mysqli_fetch_assoc($module_result)): 
                    ?>
                        <option value="<?php echo htmlspecialchars($row['module']); ?>" 
                                <?php echo ($module_filter == $row['module']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['module']); ?>
                        </option>
                    <?php 
                        endwhile; 
                    endif; 
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <input type="text" class="form-control" name="action" 
                       placeholder="Search action..." value="<?php echo htmlspecialchars($action_filter); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="date" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list"></i> Activity Logs (Last 100)
    </div>
    <div class="card-body">
        <?php if (!$result): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle"></i> Database Error</h5>
                <p><?php echo isset($error_msg) ? htmlspecialchars($error_msg) : 'Unknown error'; ?></p>
            </div>
        <?php elseif(mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while($row = mysqli_fetch_assoc($result)): 
                            $badge_color = 'secondary';
                            if($row['module'] == 'Finance') $badge_color = 'success';
                            elseif($row['module'] == 'Admission') $badge_color = 'primary';
                            elseif($row['module'] == 'SSO') $badge_color = 'info';
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo htmlspecialchars($row['module']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['action']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['details'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No activity logs found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>