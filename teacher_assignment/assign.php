<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$message = '';
$message_type = '';

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $section = isset($_POST['section']) ? trim($_POST['section']) : 'A';
    
    // New Teacher Fields
    $new_teacher_name = isset($_POST['new_teacher_name']) ? trim($_POST['new_teacher_name']) : '';
    $new_teacher_email = isset($_POST['new_teacher_email']) ? trim($_POST['new_teacher_email']) : '';
    $new_teacher_designation = isset($_POST['new_teacher_designation']) ? trim($_POST['new_teacher_designation']) : '';
    $new_teacher_department = isset($_POST['new_teacher_department']) ? (int)$_POST['new_teacher_department'] : 0;
    $new_teacher_phone = isset($_POST['new_teacher_phone']) ? trim($_POST['new_teacher_phone']) : '';
    
    // Check if adding new teacher
    $is_new_teacher = isset($_POST['is_new_teacher']) && $_POST['is_new_teacher'] == '1';
    
    if ($is_new_teacher) {
        // Validate new teacher fields
        if (empty($new_teacher_name) || empty($new_teacher_email) || $new_teacher_department == 0) {
            $message = 'Please fill all required teacher fields (Name, Email, Department)';
            $message_type = 'danger';
        } else {
            // Check if teacher already exists
            $check_sql = "SELECT teacher_id FROM teachers WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $new_teacher_email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $teacher_row = $check_result->fetch_assoc();
                $teacher_id = $teacher_row['teacher_id'];
                $message = 'Teacher already exists with this email. Using existing teacher.';
                $message_type = 'warning';
            } else {
                // Insert new teacher
                $insert_sql = "INSERT INTO teachers (teacher_name, email, designation, department_id, phone, status) 
                               VALUES (?, ?, ?, ?, ?, 'Active')";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sssis", $new_teacher_name, $new_teacher_email, 
                                         $new_teacher_designation, $new_teacher_department, $new_teacher_phone);
                
                if ($insert_stmt->execute()) {
                    $teacher_id = $insert_stmt->insert_id;
                    $message = 'New teacher added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding teacher: ' . $insert_stmt->error;
                    $message_type = 'danger';
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    // If teacher_id is valid, create assignment
    if ($teacher_id > 0 && empty($message_type) || ($message_type == 'warning' || $message_type == 'success')) {
        // Validate assignment fields
        if ($course_id == 0 || $semester_id == 0 || $session_id == 0) {
            $message = 'Please select Course, Semester, and Session';
            $message_type = 'danger';
        } else {
            // Check if assignment already exists
            $check_sql = "SELECT id FROM teacher_courses 
                          WHERE teacher_id = ? AND course_id = ? AND semester_id = ? 
                          AND session_id = ? AND section = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iiiis", $teacher_id, $course_id, $semester_id, $session_id, $section);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = 'This assignment already exists!';
                $message_type = 'warning';
            } else {
                // Insert assignment
                $insert_sql = "INSERT INTO teacher_courses (teacher_id, course_id, semester_id, session_id, section) 
                               VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiiis", $teacher_id, $course_id, $semester_id, $session_id, $section);
                
                if ($insert_stmt->execute()) {
                    $message = 'Teacher assigned to course successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating assignment: ' . $insert_stmt->error;
                    $message_type = 'danger';
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// ============================================
// GET DATA FOR DROPDOWNS
// ============================================

// Teachers
$teacher_query = "SELECT teacher_id, teacher_name, email, designation, department_id 
                  FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teacher_result = $conn->query($teacher_query);
$teachers = $teacher_result ? $teacher_result->fetch_all(MYSQLI_ASSOC) : [];

// Courses
$course_query = "SELECT c.course_id, c.course_code, c.course_title, c.credit_hours, 
                        d.department_name
                 FROM courses c
                 LEFT JOIN departments d ON c.department_id = d.department_id
                 WHERE c.status = 'Active' 
                 ORDER BY c.course_code";
$course_result = $conn->query($course_query);
$courses = $course_result ? $course_result->fetch_all(MYSQLI_ASSOC) : [];

// Semesters
$semester_query = "SELECT semester_id, semester_name, semester_number 
                   FROM semesters ORDER BY semester_number";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Sessions
$session_query = "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name";
$session_result = $conn->query($session_query);
$sessions = $session_result ? $session_result->fetch_all(MYSQLI_ASSOC) : [];

// Departments (for new teacher)
$dept_query = "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// INCLUDE HEADER AND SIDEBAR
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Assign Teacher to Course';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .form-section h5 {
        margin-bottom: 20px;
        color: #2c3e50;
        border-bottom: 2px solid #e8f0fe;
        padding-bottom: 10px;
    }
    
    .toggle-teacher-btn {
        margin-top: 5px;
    }
    
    .new-teacher-fields {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        margin-top: 15px;
        display: none;
    }
    
    .new-teacher-fields.show {
        display: block;
    }
    
    .required-field::after {
        content: ' *';
        color: #dc3545;
        font-weight: bold;
    }
    
    .section-input {
        max-width: 80px;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-user-plus"></i> Assign Teacher to Course</h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : ($message_type == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Assignment Form -->
    <div class="form-section">
        <form method="POST" action="" id="assignmentForm">
            <div class="row">
                <!-- Teacher Selection -->
                <div class="col-md-6 mb-3">
                    <label class="form-label required-field">Select Teacher</label>
                    <div class="d-flex gap-2">
                        <select name="teacher_id" id="teacherSelect" class="form-select" required>
                            <option value="0">-- Select Existing Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['teacher_id']; ?>">
                                    <?php echo htmlspecialchars($t['teacher_name']); ?> 
                                    (<?php echo htmlspecialchars($t['email']); ?>)
                                    <?php echo $t['designation'] ? ' - ' . htmlspecialchars($t['designation']) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary toggle-teacher-btn" 
                                onclick="toggleNewTeacher()">
                            <i class="fas fa-plus"></i> New
                        </button>
                    </div>
                    <input type="hidden" name="is_new_teacher" id="isNewTeacher" value="0">
                </div>

                <!-- Course Selection -->
                <div class="col-md-6 mb-3">
                    <label class="form-label required-field">Select Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="0">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['course_id']; ?>">
                                <?php echo htmlspecialchars($c['course_code']); ?> - 
                                <?php echo htmlspecialchars($c['course_title']); ?>
                                (<?php echo $c['credit_hours']; ?> Credits)
                                <?php echo $c['department_name'] ? ' - ' . htmlspecialchars($c['department_name']) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- New Teacher Fields -->
            <div class="new-teacher-fields" id="newTeacherFields">
                <h6><i class="fas fa-user-plus text-primary"></i> Add New Teacher</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Full Name</label>
                        <input type="text" name="new_teacher_name" class="form-control" 
                               placeholder="Enter teacher name">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" name="new_teacher_email" class="form-control" 
                               placeholder="teacher@university.edu">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="new_teacher_phone" class="form-control" 
                               placeholder="0300-1234567">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Department</label>
                        <select name="new_teacher_department" class="form-select">
                            <option value="0">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo $d['department_id']; ?>">
                                    <?php echo htmlspecialchars($d['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Designation</label>
                        <input type="text" name="new_teacher_designation" class="form-control" 
                               placeholder="e.g., Assistant Professor, Lecturer">
                    </div>
                </div>
                <div class="text-muted small mt-2">
                    <i class="fas fa-info-circle"></i> 
                    New teacher will be created and then assigned to this course.
                </div>
            </div>

            <hr>

            <!-- Assignment Details -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label required-field">Semester</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="0">-- Select Semester --</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?php echo $s['semester_id']; ?>">
                                <?php echo htmlspecialchars($s['semester_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label required-field">Session</label>
                    <select name="session_id" class="form-select" required>
                        <option value="0">-- Select Session --</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?php echo $s['session_id']; ?>">
                                <?php echo htmlspecialchars($s['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label required-field">Section</label>
                    <input type="text" name="section" class="form-control section-input" 
                           value="A" maxlength="5" placeholder="A" required>
                    <small class="text-muted">e.g., A, B, C, or A1</small>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Assign Teacher
                </button>
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleNewTeacher() {
    const fields = document.getElementById('newTeacherFields');
    const isNew = document.getElementById('isNewTeacher');
    const teacherSelect = document.getElementById('teacherSelect');
    
    if (fields.classList.contains('show')) {
        fields.classList.remove('show');
        isNew.value = '0';
        teacherSelect.disabled = false;
        teacherSelect.required = true;
        document.querySelector('.toggle-teacher-btn').innerHTML = '<i class="fas fa-plus"></i> New';
    } else {
        fields.classList.add('show');
        isNew.value = '1';
        teacherSelect.disabled = true;
        teacherSelect.value = '0';
        teacherSelect.required = false;
        document.querySelector('.toggle-teacher-btn').innerHTML = '<i class="fas fa-times"></i> Cancel';
    }
}

// Form validation
document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    const isNew = document.getElementById('isNewTeacher').value;
    
    if (isNew === '1') {
        const name = document.querySelector('input[name="new_teacher_name"]').value.trim();
        const email = document.querySelector('input[name="new_teacher_email"]').value.trim();
        const dept = document.querySelector('select[name="new_teacher_department"]').value;
        
        if (!name || !email || dept === '0') {
            e.preventDefault();
            alert('Please fill all required teacher fields (Name, Email, Department)');
            return false;
        }
    } else {
        const teacher = document.getElementById('teacherSelect').value;
        if (teacher === '0') {
            e.preventDefault();
            alert('Please select a teacher or add a new one.');
            return false;
        }
    }
    
    const course = document.querySelector('select[name="course_id"]').value;
    const semester = document.querySelector('select[name="semester_id"]').value;
    const session = document.querySelector('select[name="session_id"]').value;
    const section = document.querySelector('input[name="section"]').value.trim();
    
    if (course === '0' || semester === '0' || session === '0' || !section) {
        e.preventDefault();
        alert('Please fill all required fields (Course, Semester, Session, Section)');
        return false;
    }
    
    return true;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>