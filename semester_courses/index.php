<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();

// Check if connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : 'CS';
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;
$message = '';
$messageType = '';

// Get departments for filter
$dept_query = "SELECT department_id as id, department_name as name, department_code 
               FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);

if ($dept_result === false) {
    die("Error fetching departments: " . $conn->error);
}
$departments = $dept_result->fetch_all(MYSQLI_ASSOC);

// Get semesters for filter
$semester_query = "SELECT semester_id as id, semester_name as name, semester_number 
                  FROM semesters ORDER BY semester_number";
$semester_result = $conn->query($semester_query);

if ($semester_result === false) {
    die("Error fetching semesters: " . $conn->error);
}
$semesters = $semester_result->fetch_all(MYSQLI_ASSOC);

// Get all courses for dropdown
$course_query = "SELECT course_id as id, course_code, course_title, credit_hours, department_id 
                FROM courses WHERE status = 'Active' ORDER BY course_code";
$course_result = $conn->query($course_query);

if ($course_result === false) {
    die("Error fetching courses: " . $conn->error);
}
$all_courses = $course_result->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_course') {
        $semester_id = (int)($_POST['semester_id'] ?? 0);
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        // Validation
        if ($semester_id <= 0) {
            $message = "❌ Please select a semester!";
            $messageType = 'error';
        } elseif ($course_id <= 0) {
            $message = "❌ Please select a course!";
            $messageType = 'error';
        } else {
            // Check if course already exists in this semester
            $check_query = "SELECT id FROM semester_courses 
                           WHERE semester_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_query);
            
            if ($check_stmt === false) {
                $message = "❌ Error preparing check query: " . $conn->error;
                $messageType = 'error';
            } else {
                $check_stmt->bind_param("ii", $semester_id, $course_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "❌ This course is already assigned to this semester!";
                    $messageType = 'error';
                } else {
                    // Insert course into semester
                    $insert_query = "INSERT INTO semester_courses (semester_id, course_id) VALUES (?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    
                    if ($insert_stmt === false) {
                        $message = "❌ Error preparing insert query: " . $conn->error;
                        $messageType = 'error';
                    } else {
                        $insert_stmt->bind_param("ii", $semester_id, $course_id);
                        
                        if ($insert_stmt->execute()) {
                            // Get course details for message
                            $course_detail_query = "SELECT course_title FROM courses WHERE course_id = ?";
                            $detail_stmt = $conn->prepare($course_detail_query);
                            $detail_stmt->bind_param("i", $course_id);
                            $detail_stmt->execute();
                            $detail_result = $detail_stmt->get_result();
                            $course_detail = $detail_result->fetch_assoc();
                            $detail_stmt->close();
                            
                            $message = "✅ Course '" . htmlspecialchars($course_detail['course_title']) . "' added to semester successfully!";
                            $messageType = 'success';
                        } else {
                            $message = "❌ Error adding course: " . $insert_stmt->error;
                            $messageType = 'error';
                        }
                        $insert_stmt->close();
                    }
                }
                $check_stmt->close();
            }
        }
    }
    
    if ($action === 'remove_course') {
        $semester_course_id = (int)($_POST['semester_course_id'] ?? 0);
        
        if ($semester_course_id <= 0) {
            $message = "❌ Invalid course selection!";
            $messageType = 'error';
        } else {
            $delete_query = "DELETE FROM semester_courses WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            
            if ($delete_stmt === false) {
                $message = "❌ Error preparing delete query: " . $conn->error;
                $messageType = 'error';
            } else {
                $delete_stmt->bind_param("i", $semester_course_id);
                
                if ($delete_stmt->execute()) {
                    $message = "✅ Course removed from semester successfully!";
                    $messageType = 'success';
                } else {
                    $message = "❌ Error removing course: " . $delete_stmt->error;
                    $messageType = 'error';
                }
                $delete_stmt->close();
            }
        }
    }
}

// Get selected department ID
$selected_dept_id = 0;
foreach ($departments as $d) {
    if ($d['department_code'] == $department) {
        $selected_dept_id = $d['id'];
        break;
    }
}

// Get selected semester ID
$selected_semester_id = 0;
foreach ($semesters as $s) {
    if ($s['semester_number'] == $semester) {
        $selected_semester_id = $s['id'];
        break;
    }
}

// Get courses for selected semester with department filter
$courses = [];
$total_courses = 0;

if ($selected_semester_id > 0) {
    $course_query = "SELECT sc.id as semester_course_id, sc.semester_id, sc.course_id,
                            c.course_code, c.course_title, c.credit_hours,
                            d.department_name, d.department_code,
                            s.semester_name, s.semester_number
                    FROM semester_courses sc
                    LEFT JOIN courses c ON sc.course_id = c.course_id
                    LEFT JOIN departments d ON c.department_id = d.department_id
                    LEFT JOIN semesters s ON sc.semester_id = s.semester_id
                    WHERE sc.semester_id = ?";
    
    $params = [];
    $types = "i";
    $params[] = $selected_semester_id;
    
    if ($selected_dept_id > 0) {
        $course_query .= " AND c.department_id = ?";
        $params[] = $selected_dept_id;
        $types .= "i";
    }
    
    $course_query .= " ORDER BY c.course_code ASC";
    
    $stmt = $conn->prepare($course_query);
    
    if ($stmt !== false) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    // Get total courses for department
    if ($selected_dept_id > 0) {
        $total_query = "SELECT COUNT(*) as total 
                       FROM semester_courses sc
                       LEFT JOIN courses c ON sc.course_id = c.course_id
                       WHERE c.department_id = ? AND sc.semester_id = ?";
        $total_stmt = $conn->prepare($total_query);
        
        if ($total_stmt !== false) {
            $total_stmt->bind_param("ii", $selected_dept_id, $selected_semester_id);
            $total_stmt->execute();
            $total_result = $total_stmt->get_result();
            $total_row = $total_result->fetch_assoc();
            $total_courses = $total_row['total'] ?? 0;
            $total_stmt->close();
        }
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Semester Courses Management';

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
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
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
    
    .department-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .badge-cs { background: #bee3f8; color: #2a69ac; }
    .badge-se { background: #c6f6d5; color: #276749; }
    .badge-ai { background: #fefcbf; color: #975a16; }
    .badge-ds { background: #e9d8fd; color: #6b46c1; }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }
    
    .stat-box {
        background: #f7fafc;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }
    
    .stat-box .number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2b6cb0;
    }
    
    .stat-box .label {
        color: #718096;
        font-size: 0.85rem;
        margin-top: 5px;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-layer-group"></i> Semester Courses Management</h4>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?>">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-building"></i> Department</label>
                <select name="department" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_code']; ?>" 
                            <?php echo $department == $dept['department_code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-layer-group"></i> Semester</label>
                <select name="semester" class="form-select" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                            <?php echo $semester == $i ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="number"><?php echo count($courses); ?></div>
            <div class="label">Courses in Semester <?php echo $semester; ?></div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo $total_courses; ?></div>
            <div class="label">Total Courses in <?php echo $department; ?></div>
        </div>
    </div>

    <!-- Courses Table -->
    <div class="card">
        <div class="card-header">
            <h5>
                <i class="fas fa-list"></i> 
                Courses for <?php echo htmlspecialchars($department); ?> Department - Semester <?php echo $semester; ?>
                <span class="department-badge badge-<?php echo strtolower($department); ?>">
                    <?php echo htmlspecialchars($department); ?>
                </span>
            </h5>
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
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <span class="course-code-badge">
                                            <?php echo htmlspecialchars($course['course_code']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                    <td>
                                        <span class="credit-badge">
                                            <?php echo htmlspecialchars($course['credit_hours']); ?> Credits
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($course['semester_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="remove_course">
                                            <input type="hidden" name="semester_course_id" value="<?php echo $course['semester_course_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to remove this course from this semester?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
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
                    <p class="text-muted">No courses assigned to <?php echo htmlspecialchars($department); ?> - Semester <?php echo $semester; ?></p>
                    <p class="text-muted">Add courses using the form below.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Course Form -->
    <div class="card mt-3">
        <div class="card-header">
            <h5><i class="fas fa-plus-circle"></i> Add Course to Semester</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="add_course">
                
                <div class="col-md-5">
                    <label class="form-label">Select Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select a Course</option>
                        <?php foreach ($all_courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_title'] . ' (' . $c['credit_hours'] . ' Credits)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>" 
                                <?php echo $selected_semester_id == $sem['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sem['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-plus"></i> Add to Semester
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>