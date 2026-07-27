<?php
// Correct path: Go up 1 level to admission folder, then into config
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
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Award Scholarship</a>
    </div>
</div>

<!-- Active Scholarships -->
<h6 class="mt-3"><i class="fas fa-check-circle text-success"></i> Active Scholarships Awarded</h6>
<div class="row">
    <?php 
    try {
        // Get all awarded scholarships with student names
        $active = $pdo->query("
            SELECT s.*, 
                   st.full_name AS student_name,
                   st.student_id AS student_id_number
            FROM admission_scholarships s
            LEFT JOIN admission_students st ON s.student_id = st.student_id
            WHERE s.status IN ('Active', 'active', 'Approved', 'approved')
            ORDER BY s.scholarship_id DESC
        ")->fetchAll();
    } catch (PDOException $e) {
        $active = [];
        echo '<div class="col-12"><div class="alert alert-danger">Error loading scholarships: ' . $e->getMessage() . '</div></div>';
    }
    
    if (empty($active)): 
    ?>
        <div class="col-12"><div class="alert alert-info">No active scholarships awarded yet.</div></div>
    <?php else: ?>
        <?php foreach($active as $s): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($s['scholarship_name'] ?? 'N/A') ?></strong>
                    <span class="badge bg-light text-dark"><?= htmlspecialchars($s['scholarship_type'] ?? 'Merit') ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Student:</strong> <?= htmlspecialchars($s['student_name'] ?? 'N/A') ?></p>
                    <p><strong>Student ID:</strong> <?= htmlspecialchars($s['student_id_number'] ?? 'N/A') ?></p>
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
                                <span class="badge bg-<?= in_array($s['status'], ['Active', 'active', 'Approved', 'approved']) ? 'success' : 'warning' ?>">
                                    <?= ucfirst($s['status'] ?? 'N/A') ?>
                                </span>
                            </strong>
                        </div>
                    </div>
                    <?php if (!empty($s['remarks'])): ?>
                    <div class="mt-2">
                        <small class="text-muted">Remarks:</small><br>
                        <small><?= htmlspecialchars($s['remarks']) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="view.php?id=<?= $s['scholarship_id'] ?>" class="btn btn-info btn-sm w-100">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- All Scholarship Awards -->
<div class="mt-4">
    <h6><i class="fas fa-list"></i> All Scholarship Awards</h6>
    <div class="table-responsive">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Scholarship Name</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                try {
                    $apps = $pdo->query("
                        SELECT s.*, 
                               st.full_name AS student_name,
                               st.student_id AS student_id_number
                        FROM admission_scholarships s
                        LEFT JOIN admission_students st ON s.student_id = st.student_id
                        ORDER BY s.created_at DESC
                    ")->fetchAll();
                } catch (PDOException $e) {
                    $apps = [];
                    echo '<tr><td colspan="7" class="text-center text-danger">Error loading scholarships: ' . $e->getMessage() . '</td></tr>';
                }
                
                if (empty($apps)): 
                ?>
                    <tr><td colspan="7" class="text-center text-muted">No scholarships awarded yet</td></tr>
                <?php else: ?>
                    <?php foreach($apps as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['student_id_number'] ?? 'N/A') ?></strong></td>
                        <td><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($app['scholarship_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($app['scholarship_type'] ?? 'N/A') ?></td>
                        <td><strong><?= isset($app['amount']) ? formatCurrency($app['amount']) : 'N/A' ?></strong></td>
                        <td>
                            <?php 
                            $status = $app['status'] ?? 'Pending';
                            $badge_color = match(strtolower($status)) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'active' => 'success',
                                'rejected' => 'danger',
                                'expired' => 'secondary',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $app['scholarship_id'] ?? 0 ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $app['scholarship_id'] ?? 0 ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>