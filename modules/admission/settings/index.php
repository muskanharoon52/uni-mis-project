<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Settings';
include __DIR__ . '/../includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        setFlash('success', 'Settings updated successfully!');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-cog"></i> System Settings</h4>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>General Admission Settings</h3>
                <p>Configure admission timelines and application fee</p>
            </div>
        </div>
        <div class="card-content">
            <form method="post">
                <div class="field" style="margin-bottom:16px;">
                    <label>Application Start Date</label>
                    <input type="date" name="app_start_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="field" style="margin-bottom:16px;">
                    <label>Application End Date</label>
                    <input type="date" name="app_end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="field" style="margin-bottom:20px;">
                    <label>Application Fee (PKR)</label>
                    <input type="number" name="app_fee" value="1000">
                </div>
                <div class="form-actions" style="border-top:1px solid var(--border);padding-top:16px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div>
                <h3>System Information</h3>
                <p>Environment and database configuration</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">System Name</div>
                <div class="detail-value">Admission Management System</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Version</div>
                <div class="detail-value">1.0.0</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">PHP Version</div>
                <div class="detail-value"><?= phpversion() ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Server Time</div>
                <div class="detail-value"><?= date('Y-m-d H:i:s') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Database</div>
                <div class="detail-value"><code><?= DB_NAME ?></code></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
