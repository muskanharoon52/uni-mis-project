<?php
// 3. Departments/edit.php
// Edit department

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

requireAnyRole(['SuperAdmin', 'Admin', 'SSOStaff']);

$page_title = 'Edit Department';
$conn = getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$dept_id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch department data
$department = getSingleRecord("SELECT * FROM departments WHERE department_id = ?", [$dept_id], 'i');
if (!$department) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_name = sanitize($_POST['department_name']);
    $department_code = sanitize($_POST['department_code']);
    $duration_years = (int)$_POST['duration_years'];
    $total_semesters = (int)$_POST['total_semesters'];
    $status = sanitize($_POST['status']);
    
    if (empty($department_name) || empty($department_code)) {
        $error = "Department name and code are required!";
    } else {
        // Check duplicate code (excluding current)
        $check = getCount('departments', 'department_code = ? AND department_id != ?', [$department_code, $dept_id], 'si');
        if ($check > 0) {
            $error = "Department code '$department_code' already exists!";
        } else {
            $sql = "UPDATE departments SET 
                    department_name = ?, department_code = ?, duration_years = ?, 
                    total_semesters = ?, status = ? 
                    WHERE department_id = ?";
            $result = updateRecord($sql, [$department_name, $department_code, $duration_years, $total_semesters, $status, $dept_id], 'ssiisi');
            
            if ($result) {
                $success = "Department updated successfully!";
                // Refresh data
                $department = getSingleRecord("SELECT * FROM departments WHERE department_id = ?", [$dept_id], 'i');
            } else {
                $error = "Error updating department!";
            }
        }
    }
}
?>
<?php include '../../15. Includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-edit"></i> Edit Department</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Department Name <span class="text-danger">*</span></label>
                                    <input type="text" name="department_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($department['department_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Department Code <span class="text-danger">*</span></label>
                                    <input type="text" name="department_code" class="form-control" 
                                           value="<?php echo htmlspecialchars($department['department_code']); ?>" 
                                           placeholder="e.g., CS, ENG, MATH" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Duration (Years)</label>
                                    <input type="number" name="duration_years" class="form-control" 
                                           value="<?php echo $department['duration_years']; ?>" 
                                           min="1" max="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Total Semesters</label>
                                    <input type="number" name="total_semesters" class="form-control" 
                                           value="<?php echo $department['total_semesters']; ?>" 
                                           min="2" max="12">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Active" <?php echo $department['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $department['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Department
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../15. Includes/footer.php'; ?>