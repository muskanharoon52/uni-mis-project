<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $query = "SHOW COLUMNS FROM $table LIKE '$column'";
            $result = mysqli_query($conn, $query);
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) { return false; }
    }
}

if (!function_exists('getTableColumns')) {
    function getTableColumns($conn, $table) {
        try {
            $columns = [];
            $query = "SHOW COLUMNS FROM $table";
            $result = mysqli_query($conn, $query);
            if ($result) { while ($row = mysqli_fetch_assoc($result)) { $columns[] = $row['Field']; } }
            return $columns;
        } catch (Exception $e) { return []; }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$role_id = $_SESSION['role_id'] ?? 0;
$appColumns = getTableColumns($conn, 'applications');
$hasSubject = in_array('subject', $appColumns);
$hasDescription = in_array('description', $appColumns);
$hasAttachment = in_array('attachment', $appColumns);
$hasRemarks = in_array('remarks', $appColumns);
$hasReviewedBy = in_array('reviewed_by', $appColumns);
$hasReviewDate = in_array('review_date', $appColumns);
$hasStudentId = in_array('student_id', $appColumns);
$hasApplicationType = in_array('application_type', $appColumns);
$hasStatus = in_array('status', $appColumns);
$hasCreatedAt = in_array('created_at', $appColumns);

$studentColumns = getTableColumns($conn, 'students');
$hasStudentUserId = in_array('user_id', $studentColumns);
$hasStudentRollNo = in_array('roll_no', $studentColumns);
$hasStudentProgramId = in_array('program_id', $studentColumns);
$hasStudentFullName = in_array('full_name', $studentColumns);

$userColumns = getTableColumns($conn, 'users');
$hasUserPhone = in_array('phone', $userColumns);
$hasUserEmail = in_array('email', $userColumns);
$hasUserFullName = in_array('full_name', $userColumns);

$selectFields = ['a.application_id'];
if ($hasApplicationType) { $selectFields[] = 'a.application_type'; } else { $selectFields[] = "'N/A' as application_type"; }
if ($hasSubject) { $selectFields[] = 'a.subject'; } else { $selectFields[] = "'' as subject"; }
if ($hasDescription) { $selectFields[] = 'a.description'; } else { $selectFields[] = "'' as description"; }
if ($hasAttachment) { $selectFields[] = 'a.attachment'; } else { $selectFields[] = "NULL as attachment"; }
if ($hasStatus) { $selectFields[] = 'a.status'; } else { $selectFields[] = "'Pending' as status"; }
if ($hasRemarks) { $selectFields[] = 'a.remarks'; } else { $selectFields[] = "NULL as remarks"; }
if ($hasCreatedAt) { $selectFields[] = 'a.created_at'; } else { $selectFields[] = "NOW() as created_at"; }
if ($hasReviewDate) { $selectFields[] = 'a.review_date'; } else { $selectFields[] = "NULL as review_date"; }
if ($hasReviewedBy) { $selectFields[] = 'a.reviewed_by'; } else { $selectFields[] = "NULL as reviewed_by"; }
if ($hasStudentId) { $selectFields[] = 'a.student_id'; } else { $selectFields[] = "NULL as student_id"; }
if ($hasStudentRollNo) { $selectFields[] = 's.roll_no'; } else { $selectFields[] = "'N/A' as roll_no"; }
if ($hasStudentFullName) { $selectFields[] = 's.full_name as student_name'; } elseif ($hasUserFullName && $hasStudentUserId) { $selectFields[] = 'u.full_name as student_name'; } else { $selectFields[] = "'N/A' as student_name"; }
if ($hasUserEmail && $hasStudentUserId) { $selectFields[] = 'u.email as student_email'; } else { $selectFields[] = "'N/A' as student_email"; }
if ($hasUserPhone && $hasStudentUserId) { $selectFields[] = 'u.phone as student_phone'; } else { $selectFields[] = "'N/A' as student_phone"; }
if ($hasStudentProgramId) { $selectFields[] = 'p.program_name'; } else { $selectFields[] = "'N/A' as program_name"; }
$selectFields[] = 'u2.full_name as reviewer_name';

$sql = "SELECT " . implode(", ", $selectFields) . "\nFROM applications a";
if ($hasStudentId) {
    $sql .= "\nLEFT JOIN students s ON a.student_id = s.student_id";
    if ($hasStudentUserId) { $sql .= "\nLEFT JOIN users u ON s.user_id = u.user_id"; }
    if ($hasStudentProgramId) { $sql .= "\nLEFT JOIN programs p ON s.program_id = p.program_id"; }
} else {
    $sql .= "\nLEFT JOIN students s ON 1=0\nLEFT JOIN users u ON 1=0\nLEFT JOIN programs p ON 1=0";
}
if ($hasReviewedBy) { $sql .= "\nLEFT JOIN users u2 ON a.reviewed_by = u2.user_id"; } else { $sql .= "\nLEFT JOIN users u2 ON 1=0"; }
$sql .= "\nWHERE 1=1";

$params = []; $types = "";
if (!empty($search)) {
    $sc = [];
    if ($hasStudentId) { $sc[] = "a.student_id LIKE ?"; }
    if ($hasStudentRollNo) { $sc[] = "s.roll_no LIKE ?"; }
    if ($hasSubject) { $sc[] = "a.subject LIKE ?"; }
    if ($hasUserFullName && $hasStudentUserId) { $sc[] = "u.full_name LIKE ?"; }
    if ($hasUserEmail && $hasStudentUserId) { $sc[] = "u.email LIKE ?"; }
    if (empty($sc)) { $sc[] = "a.application_id LIKE ?"; }
    $sql .= " AND (" . implode(" OR ", $sc) . ")";
    foreach ($sc as $c) { $params[] = "%$search%"; $types .= "s"; }
}
if (!empty($type_filter) && $hasApplicationType) { $sql .= " AND a.application_type = ?"; $params[] = $type_filter; $types .= "s"; }
if (!empty($status_filter) && $hasStatus) { $sql .= " AND a.status = ?"; $params[] = $status_filter; $types .= "s"; }
if (!empty($date_from) && $hasCreatedAt) { $sql .= " AND DATE(a.created_at) >= ?"; $params[] = $date_from; $types .= "s"; }
if (!empty($date_to) && $hasCreatedAt) { $sql .= " AND DATE(a.created_at) <= ?"; $params[] = $date_to; $types .= "s"; }
$sql .= " ORDER BY a.application_id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Error in query: " . $conn->error); }
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
$applications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$stats_query = "SELECT COUNT(*) as total";
if ($hasStatus) {
    $stats_query .= ", SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected";
} else {
    $stats_query .= ", 0 as pending, 0 as approved, 0 as rejected";
}
$stats_query .= " FROM applications";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Applications Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Applications Management</h4>
    <div class="page-header-actions">
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4): ?>
            <a href="my_applications.php" class="btn btn-outline">My Applications</a>
            <a href="submit.php" class="btn btn-primary">+ New Application</a>
        <?php else: ?>
            <a href="submit.php" class="btn btn-primary">+ New Application</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="stat-row" style="margin-bottom:20px;">
    <div class="stat-card-v2">
        <div class="stat-label">Total</div>
        <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-label">Pending</div>
        <div class="stat-number" style="color:var(--warning);"><?= $stats['pending'] ?? 0 ?></div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-label">Approved</div>
        <div class="stat-number" style="color:var(--success);"><?= $stats['approved'] ?? 0 ?></div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-label">Rejected</div>
        <div class="stat-number" style="color:var(--danger);"><?= $stats['rejected'] ?? 0 ?></div>
    </div>
</div>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search student or subject..." value="<?= htmlspecialchars($search) ?>" style="min-width:160px;">
    <select name="type" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;background:#fff;">
        <option value="">All Types</option>
        <option value="Leave" <?= $type_filter == 'Leave' ? 'selected' : '' ?>>Leave</option>
        <option value="Bonafide Certificate" <?= $type_filter == 'Bonafide Certificate' ? 'selected' : '' ?>>Bonafide Certificate</option>
        <option value="Transcript" <?= $type_filter == 'Transcript' ? 'selected' : '' ?>>Transcript</option>
        <option value="ID Card" <?= $type_filter == 'ID Card' ? 'selected' : '' ?>>ID Card</option>
        <option value="Semester Freeze" <?= $type_filter == 'Semester Freeze' ? 'selected' : '' ?>>Semester Freeze</option>
        <option value="Course Withdrawal" <?= $type_filter == 'Course Withdrawal' ? 'selected' : '' ?>>Course Withdrawal</option>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;background:#fff;">
        <option value="">All Status</option>
        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= $status_filter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>
    <input type="date" name="date_from" value="<?= $date_from ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;">
    <input type="date" name="date_to" value="<?= $date_to ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search || $type_filter || $status_filter || $date_from || $date_to): ?>
        <a href="index.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
</form>

<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3>Applications (<?= count($applications) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; ?>
                <?php foreach($applications as $app): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></strong>
                            <div class="muted" style="font-size:11px;">ID: <?= htmlspecialchars($app['student_id'] ?? 'N/A') ?></div>
                            <?php if (!empty($app['roll_no']) && $app['roll_no'] != 'N/A'): ?>
                                <div class="muted" style="font-size:11px;">Roll: <?= htmlspecialchars($app['roll_no']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-outline"><?= htmlspecialchars($app['application_type'] ?? 'N/A') ?></span></td>
                        <td>
                            <?= htmlspecialchars($app['subject'] ?? 'N/A') ?>
                            <?php if (!empty($app['description'])): ?>
                                <div class="muted" style="font-size:11px;"><?= htmlspecialchars(substr($app['description'], 0, 50)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?= $app['status'] ?? 'Pending' ?>"><?= $app['status'] ?? 'Pending' ?></span></td>
                        <td>
                            <?= !empty($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A' ?>
                            <?php if (!empty($app['created_at'])): ?>
                                <div class="muted" style="font-size:11px;"><?= date('h:i A', strtotime($app['created_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="view.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-outline">View</a>
                                <?php if (($app['status'] ?? '') == 'Pending' && in_array($role_id, [1, 2])): ?>
                                    <a href="review.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-primary">Review</a>
                                <?php endif; ?>
                                <?php if (($app['status'] ?? '') == 'Pending'): ?>
                                    <a href="edit.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <a href="delete.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="7" class="muted" style="text-align:center;padding:40px;">No applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
