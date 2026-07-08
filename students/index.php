<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../includes/header.php';

$page_title = 'Students Management';
$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with all joins
$query = "SELECT s.*, 
          p.program_name,
          p.program_code,
          ses.session_name,
          sem.semester_name,
          a.application_status,
          a.temp_application_no
          FROM students s
          LEFT JOIN programs p ON s.program_id = p.program_id
          LEFT JOIN sessions ses ON s.current_session_id = ses.session_id
          LEFT JOIN semesters sem ON s.current_semester_id = sem.semester_id
          LEFT JOIN admission_applications a ON s.application_id = a.application_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (s.full_name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ? OR s.father_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

if ($program > 0) {
    $query .= " AND s.program_id = ?";
    $params[] = $program;
    $types .= "i";
}

if (!empty($status)) {
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
$program_query = "SELECT program_id as id, program_name as name FROM programs WHERE status = 'Active' ORDER BY program_name";
$program_result = $conn->query($program_query);

if ($program_result === false) {
    $programs = [];
} else {
    $programs = $program_result->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// INCLUDE ONLY SIDEBAR (NAVBAR REMOVED)
// ============================================
// include __DIR__ . '/../includes/navbar.php'; // REMOVED
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 16px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-freeze {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-graduated {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-dropped {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-suspended {
        background: #e2e3e5;
        color: #383d41;
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
    
    .roll-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: 13px;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-user-graduate"></i> Students Management</h4>
        <div>
            <a href="export.php" class="btn btn-success me-2">
                <i class="fas fa-file-export"></i> Export
            </a>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Student
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
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
                    <option value="Active" <?php echo $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Freeze" <?php echo $status == 'Freeze' ? 'selected' : ''; ?>>Freeze</option>
                    <option value="Graduated" <?php echo $status == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                    <option value="Dropped" <?php echo $status == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                    <option value="Suspended" <?php echo $status == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
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
        <div class="card-header">
            <h5>All Students (<?php echo count($students); ?>)</h5>
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
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <span class="roll-badge"><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="student-avatar me-2">
                                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['session_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['semester_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                                            <?php echo $student['status']; ?>
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
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add First Student
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>