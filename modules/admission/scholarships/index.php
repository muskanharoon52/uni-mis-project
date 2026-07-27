<?php
// Correct path: Go up 1 level to admission folder, then into config
require_once __DIR__ . '/../config/database.php';
$page_title = 'Scholarships';
include __DIR__ . '/../includes/header.php';

// REMOVED: formatCurrency() function is already defined in database.php

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h5><i class="fas fa-trophy"></i> Scholarships</h5>
    <div>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Scholarship</a>
        <a href="apply.php" class="btn btn-success"><i class="fas fa-hand-holding-heart"></i> Apply Scholarship</a>
    </div>
</div>

<!-- Active Scholarships -->
<h6 class="mt-3"><i class="fas fa-check-circle text-success"></i> Active Scholarships</h6>
<div class="row">
    <?php 
    try {
        // FIXED: Changed 'id' to 'scholarship_id'
        $active = $pdo->query("SELECT * FROM admission_scholarships WHERE status='active' ORDER BY scholarship_id DESC")->fetchAll();
    } catch (PDOException $e) {
        $active = [];
        echo '<div class="col-12"><div class="alert alert-danger">Error loading scholarships: ' . $e->getMessage() . '</div></div>';
    }
    
    if (empty($active)): 
    ?>
        <div class="col-12"><div class="alert alert-info">No active scholarships available.</div></div>
    <?php else: ?>
        <?php foreach($active as $s): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <!-- FIXED: Changed $s['id'] to $s['scholarship_id'] -->
                    <strong><?= htmlspecialchars($s['scholarship_name']) ?></strong>
                    <span class="badge bg-light text-dark"><?= htmlspecialchars($s['scholarship_type'] ?? 'Merit') ?></span>
                </div>
                <div class="card-body">
                    <p><?= htmlspecialchars($s['remarks'] ?? 'No description') ?></p>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Amount</small><br>
                            <strong><?= isset($s['amount']) ? formatCurrency($s['amount']) : 'N/A' ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Percentage</small><br>
                            <strong><?= number_format($s['percentage'] ?? 0, 2) ?>%</strong>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <small class="text-muted">Duration</small><br>
                            <strong><?= htmlspecialchars($s['duration'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Status</small><br>
                            <strong>
                                <span class="badge bg-<?= $s['status'] == 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($s['status'] ?? 'N/A') ?>
                                </span>
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <!-- FIXED: Changed $s['id'] to $s['scholarship_id'] -->
                    <a href="apply.php?scholarship_id=<?= $s['scholarship_id'] ?>" class="btn btn-success btn-sm w-100">
                        <i class="fas fa-hand-holding-heart"></i> Apply Now
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Scholarship Applications -->
<div class="mt-4">
    <h6><i class="fas fa-list"></i> Scholarship Applications</h6>
    <div class="table-responsive">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Scholarship</th>
                    <th>Percentage</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                try {
                    // FIXED: Changed table names and column references
                    $apps = $pdo->query("
                        SELECT sa.*, 
                               st.full_name AS student_name, 
                               sch.scholarship_name 
                        FROM admission_scholarship_applications sa
                        LEFT JOIN students st ON sa.student_id = st.student_id
                        LEFT JOIN admission_scholarships sch ON sa.scholarship_id = sch.scholarship_id
                        ORDER BY sa.created_at DESC
                    ")->fetchAll();
                } catch (PDOException $e) {
                    $apps = [];
                    echo '<tr><td colspan="6" class="text-center text-danger">Error loading applications: ' . $e->getMessage() . '</td></tr>';
                }
                
                if (empty($apps)): 
                ?>
                    <tr><td colspan="6" class="text-center text-muted">No scholarship applications</td></tr>
                <?php else: ?>
                    <?php foreach($apps as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($app['scholarship_name'] ?? 'N/A') ?></td>
                        <td><strong><?= number_format($app['percentage'] ?? 0, 2) ?>%</strong></td>
                        <td><?= isset($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A' ?></td>
                        <td>
                            <?php 
                            $status = $app['status'] ?? 'pending';
                            $badge_color = match($status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'active' => 'success',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td>
                            <a href="view_application.php?id=<?= $app['id'] ?? 0 ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if($status == 'pending' || $status == 'Under Review'): ?>
                            <a href="review_application.php?id=<?= $app['id'] ?? 0 ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>