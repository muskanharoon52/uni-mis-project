<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Applications';
include __DIR__ . '/../includes/header.php';

$apps = $pdo->query("
    SELECT a.*, d.department_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    ORDER BY a.application_id DESC
")->fetchAll();

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-file-alt"></i> Applications (<?= count($apps) ?>)</h4>
    </div>
    <div class="page-header-actions">
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Application</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3>Applications Directory</h3>
            <p>List of all student admission applications</p>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($apps)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>No Applications Found</h5>
                <p>Start by adding a new student admission application.</p>
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Application</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Application #</th>
                            <th>Student Name</th>
                            <th>Father Name</th>
                            <th>Department</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($apps as $app): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($app['temp_application_no'] ?? 'N/A') ?></strong></td>
                            <td><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($app['father_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($app['department_name'] ?? 'N/A') ?></td>
                            <td><?= isset($app['submitted_at']) ? date('d M Y', strtotime($app['submitted_at'])) : 'N/A' ?></td>
                            <td>
                                <?php $status = $app['application_status'] ?? 'Submitted'; ?>
                                <span class="status-badge <?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="view.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                                    <?php if(in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])): ?>
                                    <a href="review.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Review</a>
                                    <?php endif; ?>
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
