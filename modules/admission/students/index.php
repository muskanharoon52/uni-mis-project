<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Students';
include __DIR__ . '/../includes/header.php';

// Get search parameter
$search = $_GET['search'] ?? '';

// Build query - Only show students from confirmed applications
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT s.*, d.department_name 
            FROM admission_students s 
            LEFT JOIN departments d ON s.program_id = d.department_id 
            WHERE s.student_id LIKE ? 
               OR s.student_name LIKE ? 
               OR s.father_name LIKE ?
            ORDER BY s.id DESC
        ");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $students = $stmt->fetchAll();
    } else {
        $students = $pdo->query("
            SELECT s.*, d.department_name 
            FROM admission_students s 
            LEFT JOIN departments d ON s.program_id = d.department_id 
            ORDER BY s.id DESC
        ")->fetchAll();
    }
} catch (PDOException $e) {
    $students = [];
    setFlash('error', 'Error loading students: ' . $e->getMessage());
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header">
    <h5><i class="fas fa-users"></i> Students (<?= count($students) ?>)</h5>
    <p class="text-muted small">Showing only admitted/confirmed students</p>
</div>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-9">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search by Student ID, Name, or Father Name..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
    </div>
</form>

<?php if (empty($students)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <?php if (!empty($search)): ?>
            No students found matching "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php else: ?>
            No students found. Students are added automatically when applications are approved.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Father Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
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
                            'active' => 'success',
                            'inactive' => 'secondary',
                            'graduated' => 'primary',
                            'suspended' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                    </td>
                    <td>
                        <a href="view.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="edit.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>