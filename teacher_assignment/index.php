<?php
// teacher_assignment/index.php - Teacher Assignment Management

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';
$conn = getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$teachers_query = "SELECT t.*, d.department_name 
                   FROM teachers t
                   LEFT JOIN departments d ON t.department_id = d.department_id
                   ORDER BY t.teacher_name";
$teachers_result = mysqli_query($conn, $teachers_query);
$all_teachers = [];
if ($teachers_result) {
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $all_teachers[] = $row;
    }
}

if ($search) {
    $filtered = [];
    foreach ($all_teachers as $t) {
        if (stripos($t['teacher_name'] ?? '', $search) !== false ||
            stripos($t['teacher_code'] ?? '', $search) !== false ||
            stripos($t['email'] ?? '', $search) !== false ||
            stripos($t['department_name'] ?? '', $search) !== false) {
            $filtered[] = $t;
        }
    }
    $all_teachers = $filtered;
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Teacher Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Teacher Management</h4>
    <div class="page-header-actions">
        <a href="add_teacher.php" class="btn btn-success">
            + Add Teacher
        </a>
        <a href="assign.php" class="btn btn-primary">
            + Assign Course
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search teachers by name, code, email, or department..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?>
        <a href="index.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
</form>

<?php if (!empty($all_teachers)): ?>
    <div class="teacher-grid">
        <?php foreach ($all_teachers as $teacher): ?>
            <div class="teacher-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="flex:1;min-width:0;">
                        <div class="teacher-name"><?= htmlspecialchars($teacher['teacher_name'] ?? 'N/A') ?></div>
                        <div class="teacher-code"><i class="fas fa-id-badge"></i> <?= htmlspecialchars($teacher['teacher_code'] ?? 'N/A') ?></div>
                        <div class="teacher-dept"><i class="fas fa-building"></i> <?= htmlspecialchars($teacher['department_name'] ?? 'N/A') ?></div>
                        <div class="teacher-info"><i class="fas fa-envelope"></i> <?= htmlspecialchars($teacher['email'] ?? 'N/A') ?></div>
                        <div class="teacher-info"><i class="fas fa-phone"></i> <?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></div>
                        <?php if (!empty($teacher['specialization'])): ?>
                            <div class="teacher-info"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($teacher['specialization']) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="status-badge <?= $teacher['status'] ?? 'Active' ?>">
                        <?= $teacher['status'] ?? 'Active' ?>
                    </span>
                </div>
                <div class="teacher-actions">
                    <a href="edit_teacher.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <a href="assign.php?teacher_id=<?= $teacher['teacher_id'] ?>" class="btn btn-sm btn-primary">Assign</a>
                    <a href="delete.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-chalkboard-teacher"></i>
        <h5>No Teachers Found</h5>
        <p>Add your first teacher to get started.</p>
        <a href="add_teacher.php" class="btn btn-success">+ Add Teacher</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
