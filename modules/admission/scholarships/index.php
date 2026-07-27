<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Scholarships';
include __DIR__ . '/../includes/header.php';

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-award"></i> Scholarships Management</h4>
    </div>
    <div class="page-header-actions">
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Scholarship</a>
        <a href="apply.php" class="btn btn-outline"><i class="fas fa-hand-holding-heart"></i> Apply</a>
    </div>
</div>

<h6 style="margin-bottom:14px;color:var(--navy);font-weight:700;font-size:.92rem;">Active Scholarships</h6>
<div class="grid-3" style="margin-bottom:24px;">
    <?php 
    try {
        $active = $pdo->query("SELECT * FROM admission_scholarships WHERE status='active' ORDER BY id DESC")->fetchAll();
    } catch (PDOException $e) {
        $active = [];
    }
    
    if (empty($active)): 
    ?>
        <div style="grid-column:1/-1;">
            <div class="empty-state">
                <i class="fas fa-award"></i>
                <h5>No Active Scholarships</h5>
                <p>No active scholarship schemes configured.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($active as $s): ?>
        <div class="card" style="display:flex;flex-direction:column;padding:20px;">
            <div class="card-header" style="margin-bottom:12px;">
                <h3 style="font-size:1rem;"><?= htmlspecialchars($s['scholarship_name']) ?></h3>
                <span class="status-badge" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);"><?= htmlspecialchars($s['scholarship_type'] ?? 'Merit') ?></span>
            </div>
            <div class="card-content" style="flex:1;">
                <p style="color:var(--text-secondary);font-size:.84rem;margin-bottom:14px;line-height:1.4;"><?= htmlspecialchars($s['description'] ?? 'No description provided') ?></p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;">
                    <div>
                        <div class="muted">Amount</div>
                        <div style="font-weight:700;color:var(--text-strong);"><?= formatCurrency($s['amount'] ?? 0) ?></div>
                    </div>
                    <div>
                        <div class="muted">Min %</div>
                        <div style="font-weight:700;color:var(--text-strong);"><?= number_format($s['min_marks_percentage'] ?? 0, 2) ?>%</div>
                    </div>
                    <div>
                        <div class="muted">Total Slots</div>
                        <div style="font-weight:700;color:var(--text-strong);"><?= $s['total_slots'] ?? 'Unlimited' ?></div>
                    </div>
                    <div>
                        <div class="muted">Deadline</div>
                        <div style="font-weight:700;color:var(--text-strong);"><?= isset($s['deadline']) ? date('d M Y', strtotime($s['deadline'])) : 'N/A' ?></div>
                    </div>
                </div>
                <div style="margin-top:14px;padding-top:10px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span class="muted">Scholarship Coverage</span>
                    <strong style="color:var(--accent);font-size:14px;"><?= number_format($s['scholarship_percentage'] ?? 0, 2) ?>%</strong>
                </div>
            </div>
            <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);">
                <a href="apply.php?scholarship_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="width:100%;">
                    <i class="fas fa-hand-holding-heart"></i> Apply Now
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3>Scholarship Applications</h3>
            <p>Student applications for scholarship awards</p>
        </div>
    </div>
    <div class="card-content">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Scholarship</th>
                        <th>Percentage</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    try {
                        $apps = $pdo->query("
                            SELECT sa.*, 
                                   COALESCE(s.full_name, s.student_name, 'N/A') AS student_name, 
                                   sch.scholarship_name 
                            FROM admission_scholarship_applications sa
                            LEFT JOIN admission_students s ON sa.student_id = s.id
                            LEFT JOIN admission_scholarships sch ON sa.scholarship_id = sch.id
                            ORDER BY sa.application_date DESC
                        ")->fetchAll();
                    } catch (PDOException $e) {
                        $apps = [];
                    }
                    
                    if (empty($apps)): 
                    ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h5>No Applications</h5>
                                    <p>No scholarship applications recorded yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($apps as $app): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></strong></td>
                            <td><?= htmlspecialchars($app['scholarship_name'] ?? 'N/A') ?></td>
                            <td><strong><?= number_format($app['percentage'] ?? 0, 2) ?>%</strong></td>
                            <td><?= isset($app['application_date']) ? date('d M Y', strtotime($app['application_date'])) : 'N/A' ?></td>
                            <td>
                                <?php $status = strtolower($app['status'] ?? 'pending'); ?>
                                <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="review_application.php?id=<?= $app['id'] ?? 0 ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-check"></i> Review
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>