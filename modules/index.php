<?php
require_once '../../config/database.php';
$page_title = 'Students';
include '../../includes/header.php';

try {
    $students = $pdo->query("
        SELECT s.*, d.department_name 
        FROM admission_students s 
        LEFT JOIN departments d ON s.program_id = d.department_id 
        ORDER BY s.id DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $students = [];
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h5><i class="fas fa-users"></i> Students (<?= count($students) ?>)</h5>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Student</a>
</div>

<?php if (empty($students)): ?>
    <div class="alert alert-info">No students found. <a href="add.php">Add new student</a></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover datatable">
            <thead><tr><th>Student ID</th><th>Name</th><th>Father Name</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach($students as $s): ?>
                <tr>
                    <td><strong><?= $s['student_id'] ?? 'N/A' ?></strong></td>
                    <td><?= $s['student_name'] ?? 'N/A' ?></td>
                    <td><?= $s['father_name'] ?? 'N/A' ?></td>
                    <td><?= $s['department_name'] ?? 'N/A' ?></td>
                    <td>
                        <?php 
                        $status = $s['status'] ?? 'active';
                        $badge_color = match($status) {
                            'active' => 'success', 'inactive' => 'secondary', 
                            'graduated' => 'primary', 'suspended' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                    </td>
                    <td>
                        <a href="view.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                        <a href="edit.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>