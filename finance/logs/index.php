<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

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

// ---- SAFE QUERY WITH ERROR HANDLING ----
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

// Check if query failed
if (!$result) {
    $error_msg = mysqli_error($conn);
    $result = false;
}

// ---- MODULE FILTER ----
$module_sql = "SELECT DISTINCT module FROM activity_logs ORDER BY module";
$module_result = mysqli_query($conn, $module_sql);
if (!$module_result) {
    $module_result = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-history text-primary"></i> Activity Logs</h2>
            <p class="text-muted">View all finance module activities (auto-logged via triggers)</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Refresh</a>
            <a href="../fee_heads/index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Finance
            </a>
        </div>
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
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Activity Logs (Last 100)</span>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <span class="badge bg-light text-dark"><?php echo mysqli_num_rows($result); ?> records</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            
            <?php 
            // ---- ERROR HANDLING ----
            if (!$result): 
            ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Database Error</h5>
                    <p><strong>Error:</strong> <?php echo isset($error_msg) ? htmlspecialchars($error_msg) : 'Unknown error'; ?></p>
                    <hr>
                    <p><strong>Solution:</strong> The <code>activity_logs</code> table may not exist in your database. 
                    Please run the following SQL to create it:</p>
                    <pre class="bg-light p-2 border rounded" style="white-space: pre-wrap;">
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    reference_table VARCHAR(100) NULL,
    reference_id BIGINT NULL,
    performed_by INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Then insert a test log
INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details) 
VALUES ('Finance', 'System Test', 'test', 1, NULL, 'Activity logs table created successfully');
                    </pre>
                    <a href="index.php" class="btn btn-primary mt-2">
                        <i class="fas fa-sync"></i> Refresh After Creating Table
                    </a>
                </div>
            <?php elseif(mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Reference</th>
                                <th>Details</th>
                                <th>Performed By</th>
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
                                elseif($row['module'] == 'Examination') $badge_color = 'warning';
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                        <?php echo htmlspecialchars($row['module']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['action']); ?></strong></td>
                                <td>
                                    <?php if($row['reference_table']): ?>
                                        <small>
                                            <?php echo htmlspecialchars($row['reference_table']); ?>
                                            #<?php echo htmlspecialchars($row['reference_id']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['details'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['performed_by_name'] ?? 'System'); ?></td>
                                <td><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No activity logs found. 
                    Logs are auto-generated when you perform actions like:
                    <ul class="mt-2">
                        <li>Add/Edit/Delete Fee Heads</li>
                        <li>Generate Student Fee</li>
                        <li>Receive Payment</li>
                        <li>Generate Receipt</li>
                    </ul>
                    <a href="../fee_heads/index.php" class="btn btn-sm btn-success mt-2">
                        <i class="fas fa-plus"></i> Go to Fee Heads
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3 text-center text-muted">
        <small>Finance Module - University MIS &copy; <?php echo date('Y'); ?></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>