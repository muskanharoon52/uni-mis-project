<?php
// 3. Departments/index.php
// List all departments with management options

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

$page_title = 'Department Management';
$conn = getConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    requireAnyRole(['SuperAdmin', 'Admin', 'SSOStaff']);
    $id = (int)$_GET['delete'];
    
    // Check if department has students or courses
    $students = getCount('students', 'program_id = ?', [$id], 'i');
    $courses = getCount('courses', 'department_id = ?', [$id], 'i');
    
    if ($students > 0 || $courses > 0) {
        $error = "Cannot delete department with $students students and $courses courses assigned!";
    } else {
        if (deleteRecord("DELETE FROM departments WHERE department_id = ?", [$id], 'i')) {
            $success = "Department deleted successfully!";
        } else {
            $error = "Error deleting department!";
        }
    }
}

// Fetch all departments
$departments = getAllRecords("SELECT * FROM departments ORDER BY department_name");
?>
<?php include '../../15. Includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h5><i class="fas fa-building"></i> All Departments</h5>
        </div>
        <div class="col-md-6 text-end">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Department
            </a>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Code</th>
                        <th>Duration</th>
                        <th>Semesters</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($departments as $dept): ?>
                    <tr>
                        <td><?php echo $dept['department_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                        <td><span class="badge bg-info"><?php echo $dept['department_code']; ?></span></td>
                        <td><?php echo $dept['duration_years']; ?> years</td>
                        <td><?php echo $dept['total_semesters']; ?></td>
                        <td>
                            <span class="badge <?php echo $dept['status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $dept['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $dept['department_id']; ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $dept['department_id']; ?>" 
                               class="btn btn-danger btn-sm delete-confirm">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../15. Includes/footer.php'; ?>