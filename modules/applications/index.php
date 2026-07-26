<?php
require_once '../../config/database.php';
$page_title = 'Applications';
include '../../includes/header.php';

$apps = $pdo->query("
    SELECT a.*, d.department_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    ORDER BY a.application_id DESC
")->fetchAll();

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h5><i class="fas fa-file-alt"></i> Applications (<?= count($apps) ?>)</h5>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Application</a>
</div>

<?php if (empty($apps)): ?>
    <div class="alert alert-info">No applications found. <a href="add.php">Add new application</a></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($apps as $app): ?>
                <tr>
                    <td><strong><?= $app['temp_application_no'] ?? 'N/A' ?></strong></td>
                    <td><?= $app['full_name'] ?? 'N/A' ?></td>
                    <td><?= $app['father_name'] ?? 'N/A' ?></td>
                    <td><?= $app['department_name'] ?? 'N/A' ?></td>
                    <td><?= isset($app['submitted_at']) ? date('d M Y', strtotime($app['submitted_at'])) : 'N/A' ?></td>
                    <td><span class="badge bg-<?= getStatusBadge($app['application_status'] ?? 'Submitted') ?>"><?= $app['application_status'] ?? 'Submitted' ?></span></td>
                    <td>
                        <a href="view.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                        <?php if(in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])): ?>
                        <a href="review.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>