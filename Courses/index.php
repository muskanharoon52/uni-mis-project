<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

// Helper function to check if table exists
if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

// Helper function to check if column exists
if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $query = "SHOW COLUMNS FROM $table LIKE '$column'";
            $result = mysqli_query($conn, $query);
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) {
            return false;
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Check if columns exist in courses table
$hasProgramId = columnExists($conn, 'courses', 'program_id');
$hasDescription = columnExists($conn, 'courses', 'description');
$hasStatus = columnExists($conn, 'courses', 'status');
$hasCreditHours = columnExists($conn, 'courses', 'credit_hours');

// Check if programs table exists
$programsTableExists = tableExists($conn, 'programs');

// Build query with joins - only if columns exist
$query = "SELECT c.*";
if ($programsTableExists && $hasProgramId) {
    $query .= ", p.program_name";
} else {
    $query .= ", NULL as program_name";
}
$query .= " FROM courses c";

if ($programsTableExists && $hasProgramId) {
    $query .= " LEFT JOIN programs p ON c.program_id = p.program_id";
}

$query .= " WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (c.course_code LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if ($program > 0 && $programsTableExists && $hasProgramId) {
    $query .= " AND c.program_id = ?";
    $params[] = $program;
    $types .= "i";
}

$query .= " ORDER BY c.course_code ASC";

// Prepare and execute
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get programs for filter - only if table exists
$programs = [];
if ($programsTableExists) {
    $program_query = "SELECT program_id as id, program_name as name FROM programs ORDER BY program_name";
    $program_result = $conn->query($program_query);
    if ($program_result) {
        $programs = $program_result->fetch_all(MYSQLI_ASSOC);
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Courses Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .course-code-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #3498db;
        background: #e8f0fe;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .credit-badge {
        background: #f3e8ff;
        color: #7c3aed;
        padding: 2px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .table-actions .btn {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .empty-state {
        padding: 60px 0;
        text-align: center;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .courses-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.Active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    @media (max-width: 768px) {
        .courses-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="courses-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-book"></i> Courses Management</h4>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add New Course
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by course code or title..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="program" class="form-select">
                        <option value="0">All Programs</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?php echo $prog['id']; ?>" 
                                <?php echo $program == $prog['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prog['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Courses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>All Courses (<?php echo count($courses); ?>)</h5>
                <?php if (count($courses) > 0): ?>
                    <span class="badge bg-primary"><?php echo count($courses); ?> records</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Credit Hours</th>
                                    <th>Program</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <span class="course-code-badge">
                                                <?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($course['course_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <span class="credit-badge">
                                                <?php echo htmlspecialchars($course['credit_hours'] ?? '0'); ?> Credits
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['program_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($course['description'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($course['description'], 0, 30)) . '...'; ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">No description</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-actions">
                                            <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                                               class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $course['course_id']; ?>" 
                                               class="btn btn-danger btn-sm" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this course?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php if ($hasProgramId && !empty($course['program_id'])): ?>
                                                <a href="../semester_courses/index.php?program=<?php echo $course['program_id']; ?>" 
                                                   class="btn btn-success btn-sm" title="Add to Semester">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <h5>No Courses Found</h5>
                        <p class="text-muted">Start by adding your first course.</p>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add First Course
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>