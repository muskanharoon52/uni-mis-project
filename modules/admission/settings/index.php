<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Settings';
include __DIR__ . '/../includes/header.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update settings logic here
        setFlash('success', 'Settings updated successfully!');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header">
    <h5><i class="fas fa-cog"></i> System Settings</h5>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-university"></i> General Settings</h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Application Start Date</label>
                        <input type="date" class="form-control" name="app_start_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application End Date</label>
                        <input type="date" class="form-control" name="app_end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application Fee (PKR)</label>
                        <input type="number" class="form-control" name="app_fee" value="1000">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> System Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>System Name:</strong></td>
                        <td>Admission Management System</td>
                    </tr>
                    <tr>
                        <td><strong>Version:</strong></td>
                        <td>1.0.0</td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server Time:</strong></td>
                        <td><?= date('Y-m-d H:i:s') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td><?= DB_NAME ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>