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
        // FIXED: Changed 'id' to 'scholarship_id' to match your actual database column
        $active = $pdo->query("SELECT * FROM admission_scholarships ORDER BY scholarship_id DESC")->fetchAll();
    } catch (PDOException $e) {
        echo '<div class="alert alert-error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $active = [];
    }
    
    if (empty($active)): 
    ?>
        <div style="grid-column:1/-1;">
            <div class="empty-state">
                <i class="fas fa-award"></i>
                <h5>No Scholarships Found</h5>
                <p>Please add a scholarship scheme via the "Add Scholarship" button first.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($active as $s): ?>
        <div class="card" style="display:flex;flex-direction:column;padding:20px;">
            <div class="card-header" style="margin-bottom:12px;">
                <h3 style="font-size:1rem;"><?= htmlspecialchars($s['scholarship_name']) ?></h3>
                <span class="status-badge" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);">
                    <?= htmlspecialchars($s['scholarship_type'] ?? 'Merit') ?>
                </span>
            </div>
            <div class="card-content" style="flex:1;">
                <p style="color:var(--text-secondary);font-size:.84rem;margin-bottom:14px;line-height:1.4;">
                    <?= htmlspecialchars($s['description'] ?? 'No description provided') ?>
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;">
                    <div>
                        <div class="muted">Amount</div>
                        <div style="font-weight:700;color:var(--text-strong);">
                            <?= isset($s['amount']) ? formatCurrency($s['amount']) : 'N/A' ?>
                        </div>
                    </div>
                    <div>
                        <div class="muted">Min %</div>
                        <div style="font-weight:700;color:var(--text-strong);">
                            <?= number_format($s['percentage'] ?? 0, 2) ?>%
                        </div>
                    </div>
                    <div>
                        <div class="muted">Duration</div>
                        <div style="font-weight:700;color:var(--text-strong);">
                            <?= htmlspecialchars($s['duration'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div>
                        <div class="muted">Status</div>
                        <div style="font-weight:700;color:var(--text-strong);">
                            <span class="status-badge <?= strtolower($s['status'] ?? 'pending') ?>">
                                <?= htmlspecialchars($s['status'] ?? 'Pending') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);">
                <!-- FIXED: Changed id to scholarship_id in the link as well -->
                <a href="apply.php?scholarship_id=<?= $s['scholarship_id'] ?>" class="btn btn-sm btn-primary" style="width:100%;">
                    <i class="fas fa-hand-holding-heart"></i> Apply Now
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>



<?php include __DIR__ . '/../includes/footer.php'; ?>