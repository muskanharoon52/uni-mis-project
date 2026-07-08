<?php
// 3. Departments/add.php
// Add new department

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

requireAnyRole(['SuperAdmin', 'Admin', 'SSOStaff']);

$page_title = 'Add Department';
$conn = getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_name = sanitize($_POST['department_name']);
    $department_code = sanitize($_POST['department_code']);
    $duration_years = (int)$_POST['duration_years'];
    $total_semesters = (int)$_POST['total_semesters'];
    $status = sanitize($_POST['status']);
    
    // Validation
    if (empty($department_name) || empty($department_code)) {
        $error = "Department name and code are required!";
    } elseif ($duration_years < 1 || $duration_years > 6) {
        $error = "Duration must be between 1 and 6 years!";
    } else {
        // Check for duplicate code
        $check = getCount('departments', 'department_code = ?', [$department_code], 's');
        if ($check > 0) {
            $error = "Department code '$department_code' already exists!";
        } else {
            $sql = "INSERT INTO departments (department_name, department_code, duration_years, total_semesters, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $id = insertRecord($sql, [$department_name, $department_code, $duration_years, $total_semesters, $status], 'ssiis');
            
            if ($id > 0) {
                $success = "Department added successfully!";
                // Clear form data after success
                $_POST = [];
            } else {
                $error = "Error adding department!";
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
                    <h5><i class="fas fa-plus-circle"></i> Add New Department</h5>
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
                                           value="<?php echo isset($_POST['department_name']) ? htmlspecialchars($_POST['department_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Department Code <span class="text-danger">*</span></label>
                                    <input type="text" name="department_code" class="form-control" 
                                           value="<?php echo isset($_POST['department_code']) ? htmlspecialchars($_POST['department_code']) : ''; ?>" 
                                           placeholder="e.g., CS, ENG, MATH" required>
                                    <small class="text-muted">Unique code for the department (max 20 characters)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Duration (Years)</label>
                                    <input type="number" name="duration_years" class="form-control" 
                                           value="<?php echo isset($_POST['duration_years']) ? $_POST['duration_years'] : 4; ?>" 
                                           min="1" max="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Total Semesters</label>
                                    <input type="number" name="total_semesters" class="form-control" 
                                           value="<?php echo isset($_POST['total_semesters']) ? $_POST['total_semesters'] : 8; ?>" 
                                           min="2" max="12">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Department
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Help</h5>
                </div>
                <div class="card-body">
                    <h6>Department Guidelines:</h6>
                    <ul>
                        <li><strong>Name:</strong> Full department name (e.g., Computer Science)</li>
                        <li><strong>Code:</strong> Short unique code (e.g., CS)</li>
                        <li><strong>Duration:</strong> Usually 4 years for BS programs</li>
                        <li><strong>Semesters:</strong> 2 semesters per year</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> Example: BSCS - 4 years - 8 semesters
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../15. Includes/footer.php'; ?>