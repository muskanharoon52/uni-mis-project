<?php
// Correct path: Go up 1 level to admission folder, then into config
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
            $scholarship_name, 
            $scholarship_type, 
            $description, 
            $amount, 
            $min_marks_percentage,
            $scholarship_percentage,
            $total_slots,
            $deadline,
            $status
        ]);
        
        setFlash('success', 'Scholarship added successfully!');
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-plus"></i> Add New Scholarship</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="scholarship_name" class="form-label">Scholarship Name *</label>
                    <input type="text" class="form-control" id="scholarship_name" name="scholarship_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="scholarship_type" class="form-label">Scholarship Type</label>
                    <select class="form-control" id="scholarship_type" name="scholarship_type">
                        <option value="Merit">Merit</option>
                        <option value="Need Based">Need Based</option>
                        <option value="Sports">Sports</option>
                        <option value="Research">Research</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="amount" class="form-label">Amount (Rs.)</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="min_marks_percentage" class="form-label">Minimum Marks %</label>
                    <input type="number" class="form-control" id="min_marks_percentage" name="min_marks_percentage" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="scholarship_percentage" class="form-label">Scholarship %</label>
                    <input type="number" class="form-control" id="scholarship_percentage" name="scholarship_percentage" step="0.01">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="total_slots" class="form-label">Total Slots</label>
                    <input type="number" class="form-control" id="total_slots" name="total_slots">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="deadline" class="form-label">Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Scholarship
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>