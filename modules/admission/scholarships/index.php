<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Scholarships';
include __DIR__ . '/../includes/header.php';

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
    $active = $pdo->query("SELECT * FROM admission_scholarships WHERE status='active' ORDER BY id DESC")->fetchAll();
    if (empty($active)): 
    ?>
        <div class="col-12"><div class="alert alert-info">No active scholarships available.</div></div>
    <?php else: ?>
        <?php foreach($active as $s): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <strong><?= $s['scholarship_name'] ?></strong>
                    <span class="badge bg-light text-dark"><?= $s['scholarship_type'] ?? 'Merit' ?></span>
                </div>
                <div class="card-body">
                    <p><?= $s['description'] ?? 'No description' ?></p>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Amount</small><br>
                            <strong><?= formatCurrency($s['amount'] ?? 0) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Min %</small><br>
                            <strong><?= $s['min_marks_percentage'] ?? 0 ?>%</strong>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <small class="text-muted">Slots</small><br>
                            <strong><?= $s['total_slots'] ?? 'Unlimited' ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Deadline</small><br>
                            <strong><?= isset($s['deadline']) ? date('d M Y', strtotime($s['deadline'])) : 'N/A' ?></strong>
                        </div>
                    </div>
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
                $apps = $pdo->query("
                    SELECT sa.*, s.student_name, sch.scholarship_name 
                    FROM admission_scholarship_applications sa
                    LEFT JOIN admission_students s ON sa.student_id = s.id
                    LEFT JOIN admission_scholarships sch ON sa.scholarship_id = sch.id
                    ORDER BY sa.application_date DESC
                ")->fetchAll();
                
                if (empty($apps)): 
                ?>
                    <tr><td colspan="6" class="text-center text-muted">No scholarship applications</td></tr>
                <?php else: ?>
                    <?php foreach($apps as $app): ?>
                    <tr>
                        <td><?= $app['student_name'] ?? 'N/A' ?></td>
                        <td><?= $app['scholarship_name'] ?? 'N/A' ?></td>
                        <td><strong><?= number_format($app['percentage'] ?? 0, 2) ?>%</strong></td>
                        <td><?= isset($app['application_date']) ? date('d M Y', strtotime($app['application_date'])) : 'N/A' ?></td>
                        <td>
                            <?php 
                            $status = $app['status'] ?? 'pending';
                            $badge_color = match($status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td>
                            <a href="view_application.php?id=<?= $app['id'] ?? 0 ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                            <?php if($status == 'pending'): ?>
                            <a href="review_application.php?id=<?= $app['id'] ?? 0 ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- In the card display -->
<div class="col-6">
    <small class="text-muted">Scholarship</small><br>
    <strong><?= $s['scholarship_percentage'] ?? '0' ?>%</strong>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>