<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/sso/login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

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

// Helper function to get table columns
if (!function_exists('getTableColumns')) {
    function getTableColumns($conn, $table) {
        try {
            $columns = [];
            $query = "SHOW COLUMNS FROM $table";
            $result = mysqli_query($conn, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row['Field'];
                }
            }
            return $columns;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Use global $conn
global $conn;

// Check which columns exist in users table
$userColumns = getTableColumns($conn, 'users');
$hasUserPhone = in_array('phone', $userColumns);
$hasUserEmail = in_array('email', $userColumns);
$hasUserFullName = in_array('full_name', $userColumns);

// Check which columns exist in students table
$studentColumns = getTableColumns($conn, 'students');
$hasStudentRollNo = in_array('roll_no', $studentColumns);
$hasStudentFatherName = in_array('father_name', $studentColumns);
$hasStudentProgramId = in_array('program_id', $studentColumns);
$hasStudentSemester = in_array('semester', $studentColumns);
$hasStudentStatus = in_array('status', $studentColumns);
$hasStudentUserId = in_array('user_id', $studentColumns);

// Build query with all joins - only include columns that exist
$query = "SELECT s.*";

// Add program fields
$query .= ", p.program_name, p.program_code";

// Add user fields - only if they exist AND if students table has user_id column
if ($hasStudentUserId) {
    if ($hasUserFullName) {
        $query .= ", u.full_name";
    } else {
        $query .= ", NULL as full_name";
    }

    if ($hasUserEmail) {
        $query .= ", u.email";
    } else {
        $query .= ", NULL as email";
    }

    if ($hasUserPhone) {
        $query .= ", u.phone";
    } else {
        $query .= ", NULL as phone";
    }
} else {
    // If no user_id column, set user fields to NULL
    $query .= ", NULL as full_name, NULL as email, NULL as phone";
}

$query .= " FROM students s
          LEFT JOIN programs p ON s.program_id = p.program_id";

// Only join users table if students table has user_id column
if ($hasStudentUserId) {
    $query .= " LEFT JOIN users u ON s.user_id = u.user_id";
}

$query .= " WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $searchConditions = [];
    
    if ($hasUserFullName && $hasStudentUserId) {
        $searchConditions[] = "u.full_name LIKE ?";
    }
    if ($hasStudentRollNo) {
        $searchConditions[] = "s.roll_no LIKE ?";
    }
    if ($hasUserEmail && $hasStudentUserId) {
        $searchConditions[] = "u.email LIKE ?";
    }
    if ($hasStudentFatherName) {
        $searchConditions[] = "s.father_name LIKE ?";
    }
    
    // If no search conditions available, search by student_id
    if (empty($searchConditions)) {
        $searchConditions[] = "s.student_id LIKE ?";
    }
    
    $query .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $searchParam = "%$search%";
    
    foreach ($searchConditions as $condition) {
        $params[] = $searchParam;
        $types .= "s";
    }
}

if ($program > 0 && $hasStudentProgramId) {
    $query .= " AND s.program_id = ?";
    $params[] = $program;
    $types .= "i";
}

if (!empty($status) && $hasStudentStatus) {
    $query .= " AND s.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY s.student_id DESC";

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
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get programs for filter
$program_query = "SELECT program_id as id, program_name as name FROM programs ORDER BY program_name";
$program_result = $conn->query($program_query);

if ($program_result === false) {
    $programs = [];
} else {
    $programs = $program_result->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="container-fluid">
        <!-- Page Header with Add Student Button -->
        <div class="page-header">
            <h2><i class="fas fa-user-graduate"></i> Student Management</h2>
            <div class="btn-group">
                <a href="add.php" class="btn btn-add-student">
                    <i class="fas fa-user-plus"></i> Add Student
                </a>
                <a href="export.php" class="btn btn-export">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, roll no, father name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="graduated" <?php echo $status == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
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

        <!-- Students Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>All Students (<?php echo count($students); ?>)</h5>
                <?php if (count($students) > 0): ?>
                    <span class="badge bg-primary"><?php echo count($students); ?> records</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student</th>
                                    <th>Father's Name</th>
                                    <th>Program</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <span class="roll-badge">
                                                <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar me-2">
                                                    <?php echo strtoupper(substr($student['full_name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></div>
                                                    <?php if (!empty($student['email']) && $student['email'] != 'N/A'): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($student['phone']) && $student['phone'] != 'N/A'): ?>
                                                        <br>
                                                        <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="father-name">
                                            <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $semester_num = $student['semester'] ?? 1;
                                            $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
                                            echo ($semester_num >= 1 && $semester_num <= 8) 
                                                ? $ordinal[$semester_num - 1] . ' Semester' 
                                                : 'Semester ' . $semester_num;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = match($student['status']) {
                                                'active' => 'active',
                                                'confirmed' => 'confirmed',
                                                'pending' => 'pending',
                                                'inactive' => 'inactive',
                                                'graduated' => 'graduated',
                                                default => 'inactive'
                                            };
                                            ?>
                                            <span class="status-badge status-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($student['status'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="view.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-danger btn-sm" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <h5>No Students Found</h5>
                        <p class="text-muted">Start by adding your first student.</p>
                        <a href="add.php" class="btn btn-primary mt-3">
                            <i class="fas fa-user-plus"></i> Add First Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>