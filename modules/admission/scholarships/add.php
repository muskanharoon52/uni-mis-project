<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Scholarship';
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $scholarship_name = trim($_POST['scholarship_name'] ?? '');
        $scholarship_type = trim($_POST['scholarship_type'] ?? 'Merit');
        $description = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $min_marks_percentage = floatval($_POST['min_marks_percentage'] ?? 0);
        $scholarship_percentage = floatval($_POST['scholarship_percentage'] ?? 0);
        $total_slots = !empty($_POST['total_slots']) ? intval($_POST['total_slots']) : null;
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $status = $_POST['status'] ?? 'active';
        
        if (empty($scholarship_name)) {
            throw new Exception('Scholarship name is required.');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO admission_scholarships 
            (scholarship_name, scholarship_type, description, amount, min_marks_percentage, 
             scholarship_percentage, total_slots, deadline, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $scholarship_name, $scholarship_type, $description, $amount, 
            $min_marks_percentage, $scholarship_percentage, $total_slots, $deadline, $status
        ]);
        
        setFlash('success', 'Scholarship added successfully!');
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Add New Scholarship</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="post">
        <div class="form-row">
            <div class="form-group">
                <label>Scholarship Name *</label>
                <input type="text" name="scholarship_name" required>
            </div>
            <div class="form-group">
                <label>Scholarship Type</label>
                <select name="scholarship_type">
                    <option value="Merit">Merit</option>
                    <option value="Need Based">Need Based</option>
                    <option value="Sports">Sports</option>
                    <option value="Research">Research</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Amount (PKR)</label>
                <input type="number" name="amount" step="0.01">
            </div>
            <div class="form-group">
                <label>Minimum Marks %</label>
                <input type="number" name="min_marks_percentage" step="0.01">
            </div>
            <div class="form-group">
                <label>Scholarship %</label>
                <input type="number" name="scholarship_percentage" step="0.01">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Total Slots</label>
                <input type="number" name="total_slots">
            </div>
            <div class="form-group">
                <label>Deadline</label>
                <input type="date" name="deadline">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Scholarship</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
