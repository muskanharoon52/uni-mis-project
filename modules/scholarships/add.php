<?php
require_once '../../config/database.php';
$page_title = 'Add Scholarship';
include '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'scholarship_name' => sanitize($_POST['scholarship_name']),
            'description' => sanitize($_POST['description']),
            'scholarship_type' => $_POST['scholarship_type'],
            'amount' => $_POST['amount'],
            'min_marks_percentage' => $_POST['min_marks_percentage'],
            'max_marks_percentage' => $_POST['max_marks_percentage'] ?? 100,
            'total_slots' => $_POST['total_slots'] ?? 0,
            'deadline' => $_POST['deadline'],
            'status' => 'active'
        ];
        
        $sql = "INSERT INTO admission_scholarships SET ";
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts);
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            setFlash('success', 'Scholarship added successfully!');
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header"><h5><i class="fas fa-plus"></i> Add Scholarship</h5></div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-12">
        <label class="form-label">Scholarship Name *</label>
        <input type="text" name="scholarship_name" class="form-control" required>
    </div>
    
    <div class="col-md-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Describe the scholarship criteria..."></textarea>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Scholarship Type *</label>
        <select name="scholarship_type" class="form-select" required>
            <option value="Merit">Merit Based</option>
            <option value="Need Based">Need Based</option>
            <option value="Sports">Sports</option>
            <option value="Special">Special</option>
            <option value="Other">Other</option>
        </select>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Amount (₹) *</label>
        <input type="number" step="0.01" name="amount" class="form-control" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Total Slots</label>
        <input type="number" name="total_slots" class="form-control" value="10" placeholder="0 = Unlimited">
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Minimum Percentage (%) *</label>
        <input type="number" step="0.01" name="min_marks_percentage" class="form-control" required placeholder="e.g. 60">
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Maximum Percentage (%)</label>
        <input type="number" step="0.01" name="max_marks_percentage" class="form-control" value="100" placeholder="e.g. 100">
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Deadline *</label>
        <input type="date" name="deadline" class="form-control" required>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Scholarship</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>
<?php include '../../includes/footer.php'; ?>