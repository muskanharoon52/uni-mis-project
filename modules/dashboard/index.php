<?php
require_once '../../config/database.php';
$page_title = 'Dashboard';
include '../../includes/header.php';

try {
    $stats['applications'] = $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn();
    $stats['pending'] = $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE application_status IN ('Submitted', 'Under Review')")->fetchColumn();
    $stats['students'] = $pdo->query("SELECT COUNT(*) FROM admission_students")->fetchColumn();
    $stats['departments'] = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    
    $recent = $pdo->query("
        SELECT a.*, d.department_name 
        FROM admission_applications a 
        LEFT JOIN departments d ON a.program_id = d.department_id 
        ORDER BY a.application_id DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $stats = ['applications' => 0, 'pending' => 0, 'students' => 0, 'departments' => 0];
    $recent = [];
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small class="text-muted">Applications</small><div class="number"><?= $stats['applications'] ?? 0 ?></div></div>
                <div class="text-primary"><i class="fas fa-file-alt fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small class="text-muted">Pending</small><div class="number"><?= $stats['pending'] ?? 0 ?></div></div>
                <div class="text-warning"><i class="fas fa-clock fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small class="text-muted">Students</small><div class="number"><?= $stats['students'] ?? 0 ?></div></div>
                <div class="text-success"><i class="fas fa-user-graduate fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div><small class="text-muted">Departments</small><div class="number"><?= $stats['departments'] ?? 0 ?></div></div>
                <div class="text-info"><i class="fas fa-building fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="page-header">
    <h6><i class="fas fa-bell"></i> Recent Applications</h6>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No applications found</td></tr>
                <?php else: ?>
                    <?php foreach($recent as $app): ?>
                    <tr>
                        <td><?= $app['temp_application_no'] ?? 'N/A' ?></td>
                        <td><?= $app['full_name'] ?? 'N/A' ?></td>
                        <td><?= $app['department_name'] ?? 'N/A' ?></td>
                        <td><?= isset($app['submitted_at']) ? date('d M Y', strtotime($app['submitted_at'])) : 'N/A' ?></td>
                        <td><span class="badge bg-<?= getStatusBadge($app['application_status'] ?? 'Submitted') ?>"><?= $app['application_status'] ?? 'Submitted' ?></span></td>
                        <td><a href="../applications/view.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-info">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>