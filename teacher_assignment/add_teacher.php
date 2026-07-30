<?php
// teacher_assignment/add_teacher.php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// ============================================
// 1. DYNAMIC COLUMN DETECTION (Fixes the errors)
// ============================================
$teacher_id_column = 'teacher_id';
$teacher_name_column = 'full_name';

// Check the actual columns in the teachers table
$check_cols = $conn->query("SHOW COLUMNS FROM teachers");
if ($check_cols) {
    $cols = [];
    while($col = $check_cols->fetch_assoc()) {
        $cols[] = $col['Field'];
    }
    
    // Detect the ID column
    if (in_array('teacher_id', $cols)) {
        $teacher_id_column = 'teacher_id';
    } elseif (in_array('id', $cols)) {
        $teacher_id_column = 'id';
    } elseif (in_array('employee_id', $cols)) {
        $teacher_id_column = 'employee_id';
    }
    
    // Detect the Name column
    if (in_array('full_name', $cols)) {
        $teacher_name_column = 'full_name';
    } elseif (in_array('name', $cols)) {
        $teacher_name_column = 'name';
    } elseif (in_array('teacher_name', $cols)) {
        $teacher_name_column = 'teacher_name';
    }
}

// ============================================
// 2. FETCH TEACHERS (Using the detected columns)
// ============================================
// NOTE: We escape the column names with backticks ` ` to be safe!
$teachers_sql = "SELECT `$teacher_id_column` as id, `$teacher_name_column` as name, email 
                 FROM teachers 
                 WHERE status = 'Active' 
                 ORDER BY `$teacher_name_column`";
$teachers_result = $conn->query($teachers_sql);

if ($teachers_result === false) {
    die("Database Error fetching teachers: " . $conn->error);
}
$teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);

// ============================================
// 3. GET COURSES
// ============================================
$courses_sql = "SELECT c.course_id as id, c.course_code, c.course_name, p.program_name 
                FROM courses c 
                LEFT JOIN programs p ON c.program_id = p.program_id 
                ORDER BY c.course_code";
$courses_result = $conn->query($courses_sql);
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// 4. HANDLE FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
    $assigned_date = date('Y-m-d');

    if ($teacher_id == 0 || $course_id == 0 || $semester_id == 0) {
        $error = "Please select a Teacher, Course, and Semester.";
    } else {
        // Check if already assigned
        $check_sql = "SELECT * FROM teacher_assignments WHERE teacher_id = ? AND course_id = ? AND semester_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            $error = "Database error preparing check: " . $conn->error;
        } else {
            $check_stmt->bind_param("iii", $teacher_id, $course_id, $semester_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "This teacher is already assigned to this course for the selected semester.";
            } else {
                // Insert the assignment
                $insert_sql = "INSERT INTO teacher_assignments (teacher_id, course_id, semester_id, assigned_date) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt === false) {
                    $error = "Database error preparing insert: " . $conn->error;
                } else {
                    $insert_stmt->bind_param("iiis", $teacher_id, $course_id, $semester_id, $assigned_date);
                    if ($insert_stmt->execute()) {
                        $success = "Teacher successfully assigned to course!";
                        // Reset form
                        $teacher_id = 0; $course_id = 0; $semester_id = 0;
                    } else {
                        $error = "Error assigning teacher: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}

// ============================================
// HEADER & SIDEBAR
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Assign Teacher to Course';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="page-header">
        <h4><i class="fas fa-user-tie"></i> Assign Teacher to Course</h4>
        <div class="page-header-actions">
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Select Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="0">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= (isset($teacher_id) && $teacher_id == $teacher['id']) ? 'selected' : '' ?>>
                                    <!-- USING THE DETECTED NAME COLUMN HERE -->
                                    <?= htmlspecialchars($teacher['name']) ?> 
                                    <?= !empty($teacher['email']) ? '(' . htmlspecialchars($teacher['email']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="required-field">Select Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="0">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" <?= (isset($course_id) && $course_id == $course['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                                    <?= !empty($course['program_name']) ? '('.htmlspecialchars($course['program_name']).')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="required-field">Select Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="0">-- Select Semester --</option>
                            <?php 
                            // Fetch semesters dynamically
                            $sem_sql = "SELECT semester_id as id, semester_name FROM semesters GROUP BY semester_name ORDER BY semester_name";
                            $sem_res = $conn->query($sem_sql);
                            if ($sem_res) {
                                while($sem = $sem_res->fetch_assoc()) {
                                    echo '<option value="' . $sem['id'] . '" ' . ((isset($semester_id) && $semester_id == $sem['id']) ? 'selected' : '') . '>' . htmlspecialchars($sem['semester_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Assign Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>