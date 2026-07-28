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
    <div class="alert alert-<?= $flash['type'] ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-user-graduate"></i> Enrolled Students Directory (<?= count($students) ?>)</h4>
    </div>
    <div class="page-header-actions">
        <a href="add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Student</a>
    </div>
</div>

<div class="filter-bar" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:10px;width:100%;">
        <input type="text" name="search" 
               placeholder="Search by Student ID, Name, or Father Name..." 
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Search
        </button>
        <?php if (!empty($search)): ?>
            <a href="index.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3>Students Directory</h3>
            <p>Active and past admitted students in admission module</p>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-users" style="font-size:2rem;margin-bottom:8px;color:var(--muted);"></i>
                <h5>No Students Found</h5>
                <?php if (!empty($search)): ?>
                    <p>No student records matching "<strong><?= htmlspecialchars($search) ?></strong>"</p>
                <?php else: ?>
                    <p>No students found. Students are automatically enrolled when applications are approved.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Father Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['student_id'] ?? 'N/A') ?></strong></td>
                            <td><?= htmlspecialchars($s['student_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($s['father_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($s['department_name'] ?? 'N/A') ?></td>
                            <td>
                                <?php $status = strtolower($s['status'] ?? 'active'); ?>
                                <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="view.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit.php?id=<?= $s['id'] ?? 0 ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
