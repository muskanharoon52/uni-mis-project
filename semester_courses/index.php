<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT c.*, d.department_name, GROUP_CONCAT(CONCAT(s.semester_name, ' (', s.semester_id, ')') SEPARATOR ', ') as assigned_semesters FROM courses c LEFT JOIN departments d ON c.department_id = d.department_id LEFT JOIN semester_courses sc ON c.course_id = sc.course_id LEFT JOIN semesters s ON sc.semester_id = s.semester_id WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) { $sql .= " AND (c.course_code LIKE ? OR c.course_name LIKE ?)"; $searchParam = "%$search%"; $params[] = $searchParam; $params[] = $searchParam; $types .= "ss"; }
if ($department > 0) { $sql .= " AND c.department_id = ?"; $params[] = $department; $types .= "i"; }
$sql .= " GROUP BY c.course_id ORDER BY c.course_code";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Error in query: " . $conn->error); }
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
$courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$semesters_result = $conn->query("SELECT * FROM semesters ORDER BY semester_name");
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Semester Courses Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-layer-group"></i> Semester Courses Management</h4>
    <div class="page-header-actions">
        <a href="assign.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Assign New Course</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;width:100%;">
        <input type="text" name="search" placeholder="Search by course code or title..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px;">
        <select name="department" style="min-width:180px;">
            <option value="0">All Departments</option>
            <?php foreach($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $department == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Reset</a>
    </form>
</div>

<?php if (!empty($courses)): ?>
    <div class="card">
        <div class="card-header"><h5>All Courses (<?= count($courses) ?>)</h5></div>
        <div style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Credit Hours</th>
                            <th>Department</th>
                            <th>Assigned Semesters</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($courses as $course): $isAssigned = !empty($course['assigned_semesters']); ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><strong style="color:var(--accent);"><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= $course['credit_hours'] ?> Credits</td>
                                <td><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if($isAssigned): ?>
                                        <?php $semestersList = explode(', ', $course['assigned_semesters']); foreach($semestersList as $sem): ?>
                                            <span class="status-badge Active" style="margin-right:4px;"><?= htmlspecialchars(trim($sem)) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="status-badge Inactive">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?= $isAssigned ? 'Active' : 'Inactive' ?>"><?= $isAssigned ? 'Assigned' : 'Unassigned' ?></span></td>
                                <td style="display:flex;gap:4px;">
                                    <a href="assign.php?course_id=<?= $course['course_id'] ?>" class="btn btn-ghost btn-sm" title="Assign to Semester"><i class="fas fa-plus-circle"></i> Assign</a>
                                    <?php if($isAssigned): ?>
                                        <a href="view.php?course_id=<?= $course['course_id'] ?>" class="btn btn-ghost btn-sm" title="View Assignments"><i class="fas fa-eye"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-book"></i>
        <h5>No Courses Found</h5>
        <p>No courses available. Please add courses first.</p>
        <a href="../Courses/add.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Course</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
