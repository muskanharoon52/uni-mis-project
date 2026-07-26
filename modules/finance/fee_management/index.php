<?php
// fee_management/index.php - Complete Fee Management (ALL TABS FIXED)

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin', 'account'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'structures';

// Stats
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_structures");
$total_structures = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM course_fees");
$total_course_fees = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_records");
$total_fee_records = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_records");
$total_collected = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Fee Structures
$structures_query = "SELECT fs.*, p.program_name, s.semester_name, ses.session_name 
                     FROM fee_structures fs
                     LEFT JOIN programs p ON fs.program_id = p.program_id
                     LEFT JOIN semesters s ON fs.semester_id = s.semester_id
                     LEFT JOIN sessions ses ON fs.session_id = ses.session_id
                     ORDER BY fs.created_at DESC";
$structures_result = mysqli_query($conn, $structures_query);
$structures = [];
if ($structures_result) {
    while ($row = mysqli_fetch_assoc($structures_result)) {
        $structures[] = $row;
    }
}

// Course Fees (No semester_id)
$course_fees_query = "SELECT cf.*, c.course_code, c.course_name, c.credit_hours 
                      FROM course_fees cf
                      LEFT JOIN courses c ON cf.course_id = c.course_id
                      ORDER BY cf.created_at DESC";
$course_fees_result = mysqli_query($conn, $course_fees_query);
$course_fees = [];
if ($course_fees_result) {
    while ($row = mysqli_fetch_assoc($course_fees_result)) {
        $course_fees[] = $row;
    }
}

// Scholarships
$scholarships = [];
$scholarship_query = "SELECT s.*, u.full_name as student_name 
                      FROM scholarships s
                      LEFT JOIN students st ON s.student_id = st.student_id
                      LEFT JOIN users u ON st.user_id = u.user_id
                      ORDER BY s.created_at DESC";
$scholarship_result = mysqli_query($conn, $scholarship_query);
if ($scholarship_result) {
    while ($row = mysqli_fetch_assoc($scholarship_result)) {
        $scholarships[] = $row;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .fee-content { margin-left: 250px; padding: 20px; min-height: 100vh; background: #f5f6fa; }
    .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .stat-card .number { font-size: 28px; font-weight: 700; }
    .stat-card .label { color: #666; font-size: 14px; }
    .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .nav-tabs .nav-link { border: none; color: #666; font-weight: 500; padding: 12px 25px; border-radius: 10px 10px 0 0; }
    .nav-tabs .nav-link:hover { background: #f0f2f5; color: #2c3e50; }
    .nav-tabs .nav-link.active { background: white; color: #667eea; border-bottom: 3px solid #667eea; }
    .nav-tabs .nav-link i { margin-right: 8px; }
    .tab-content { background: white; border-radius: 0 0 15px 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; overflow-y: auto; z-index: 1000; }
    .sidebar .brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar .brand h4 { font-weight: 700; margin: 0; }
    .sidebar .brand small { color: #a8a8b3; }
    .sidebar .nav-link { color: #a8a8b3; padding: 12px 20px; border-radius: 0; transition: all 0.3s; }
    .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
    .sidebar .nav-link.active { color: white; background: rgba(102, 126, 234, 0.3); border-left: 3px solid #667eea; }
    .sidebar .nav-link i { width: 20px; margin-right: 10px; }
    @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .fee-content { margin-left: 0; } }
</style>

<div class="fee-content">
    <div class="container-fluid">
        
        <div class="topbar">
            <div>
                <h5 class="mb-0"><i class="fas fa-money-bill-wave text-primary"></i> Fee Management</h5>
                <small class="text-muted">Manage fees, structures, and scholarships</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary"><?php echo ucfirst($role); ?></span>
                <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="number"><?php echo $total_structures; ?></p>
                            <p class="label">Fee Structures</p>
                        </div>
                        <div class="icon" style="background: #e3f2fd; color: #1976d2;"><i class="fas fa-file-invoice"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="number"><?php echo $total_course_fees; ?></p>
                            <p class="label">Course Fees</p>
                        </div>
                        <div class="icon" style="background: #e8f5e9; color: #388e3c;"><i class="fas fa-book"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="number">Rs. <?php echo number_format($total_collected, 0); ?></p>
                            <p class="label">Total Collected</p>
                        </div>
                        <div class="icon" style="background: #e8f5e9; color: #388e3c;"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="number"><?php echo $total_fee_records; ?></p>
                            <p class="label">Total Records</p>
                        </div>
                        <div class="icon" style="background: #fce4ec; color: #c62828;"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TABS -->
        <!-- ========================================== -->
        <ul class="nav nav-tabs" id="feeTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'structures' ? 'active' : ''; ?>" href="?tab=structures">
                    <i class="fas fa-file-invoice"></i> Fee Structures
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'course_fees' ? 'active' : ''; ?>" href="?tab=course_fees">
                    <i class="fas fa-book"></i> Fee Per Course
                </a>
            </li>
          
        </ul>

        <div class="tab-content">
            
            <!-- ========================================== -->
            <!-- TAB 1: FEE STRUCTURES -->
            <!-- ========================================== -->
            <?php if ($active_tab == 'structures'): ?>
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Fee Structures List</h6>
                    <div>
                        <a href="../scholarship/calculate.php" class="btn btn-info btn-sm me-2">
                            <i class="fas fa-calculator"></i> GPA Scholarship
                        </a>
                        <a href="assign_to_student.php" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-user-plus"></i> Assign to Student
                        </a>
                        <a href="structure_add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Fee Structure
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($structures)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Program</th>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($structures as $fs): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($fs['program_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($fs['session_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($fs['semester_name'] ?? 'N/A'); ?></td>
                                <td><strong>Rs. <?php echo number_format($fs['total_amount'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo ($fs['status'] ?? 'Active') == 'Active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $fs['status'] ?? 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="structure_edit.php?id=<?php echo $fs['fee_structure_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="structure_delete.php?id=<?php echo $fs['fee_structure_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this fee structure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <p>No fee structures found.</p>
                    <a href="structure_add.php" class="btn btn-primary">Create Fee Structure</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- TAB 2: FEE PER COURSE -->
            <!-- ========================================== -->
            <?php if ($active_tab == 'course_fees'): ?>
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Fee Per Course List</h6>
                    <a href="course_add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Course Fee
                    </a>
                </div>
                
                <?php if (!empty($course_fees)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course</th>
                                <th>Fee Amount</th>
                                <th>Fee Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($course_fees as $cf): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cf['course_code'] ?? 'N/A'); ?></strong>
                                    <br>
                                    <small><?php echo htmlspecialchars($cf['course_name'] ?? 'N/A'); ?></small>
                                </td>
                                <td><strong>Rs. <?php echo number_format($cf['fee_amount'] ?? 0, 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($cf['fee_type'] ?? 'Fixed'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($cf['is_active'] ?? 1) == 1 ? 'success' : 'secondary'; ?>">
                                        <?php echo ($cf['is_active'] ?? 1) == 1 ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="course_edit.php?id=<?php echo $cf['fee_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="course_delete.php?id=<?php echo $cf['fee_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course fee?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <p>No course fees found.</p>
                    <a href="course_add.php" class="btn btn-primary">Add Course Fee</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- TAB 3: SCHOLARSHIPS -->
            <!-- ========================================== -->
            <?php if ($active_tab == 'scholarships'): ?>
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Scholarships List</h6>
                    <div>
                        <a href="scholarship_add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Scholarship
                        </a>
                        <a href="../scholarship/calculate.php" class="btn btn-info btn-sm">
                            <i class="fas fa-calculator"></i> GPA Based
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($scholarships)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Scholarship Type</th>
                                <th>Awarding Body</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($scholarships as $sch): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($sch['student_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($sch['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($sch['scholarship_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($sch['awarding_body'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($sch['discount_kind'] == 'Percentage'): ?>
                                        <span class="badge bg-info"><?php echo $sch['discount_value']; ?>%</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Rs. <?php echo number_format($sch['discount_value'], 0); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($sch['status'] ?? 'Active') == 'Active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $sch['status'] ?? 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="scholarship_edit.php?id=<?php echo $sch['scholarship_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="scholarship_delete.php?id=<?php echo $sch['scholarship_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this scholarship?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <p>No scholarships found.</p>
                    <p class="text-muted small">Add a scholarship using the button above.</p>
                    <a href="scholarship_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Scholarship
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>