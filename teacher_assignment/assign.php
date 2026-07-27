<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

$check_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions");
if ($check_sessions) {
    $session_count = $check_sessions->fetch_assoc();
    if ($session_count['count'] == 0) {
        $default_sessions = [
            ['Fall 2024', '2024-09-01', '2024-12-31', 'Active'],
            ['Spring 2025', '2025-01-15', '2025-05-30', 'Active'],
            ['Summer 2025', '2025-06-01', '2025-08-31', 'Active'],
            ['Fall 2025', '2025-09-01', '2025-12-31', 'Inactive']
        ];
        foreach ($default_sessions as $session) {
            $insert_session = $conn->prepare("INSERT INTO sessions (session_name, start_date, end_date, status) VALUES (?, ?, ?, ?)");
            $insert_session->bind_param("ssss", $session[0], $session[1], $session[2], $session[3]);
            $insert_session->execute();
            $insert_session->close();
        }
        $success = 'Default sessions have been created automatically!';
    }
}

$teachers_query = "SELECT teacher_id, teacher_name, teacher_code FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teachers_result = $conn->query($teachers_query);
$teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];

$courses_query = "SELECT course_id, course_code, course_name, credit_hours FROM courses ORDER BY course_code";
$courses_result = $conn->query($courses_query);
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];

$semesters_query = "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name";
$semesters_result = $conn->query($semesters_query);
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

$sessions_query = "SELECT session_id, session_name, status FROM sessions ORDER BY session_name DESC";
$sessions_result = $conn->query($sessions_query);
$sessions = $sessions_result ? $sessions_result->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $section = trim($_POST['section'] ?? 'A');
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';

    if ($teacher_id <= 0 || $course_id <= 0 || $semester_id <= 0 || $session_id <= 0) {
        $error = 'Please select teacher, course, semester, and session.';
    } else {
        $check_sql = "SELECT id FROM teacher_courses 
                      WHERE teacher_id = ? AND course_id = ? AND semester_id = ? AND session_id = ? AND section = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iiiis", $teacher_id, $course_id, $semester_id, $session_id, $section);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'This assignment already exists for the same teacher, course, semester, session, and section.';
        } else {
            $insert_sql = "INSERT INTO teacher_courses 
                          (teacher_id, course_id, semester_id, session_id, section, is_primary, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiiisis", $teacher_id, $course_id, $semester_id, $session_id, $section, $is_primary, $status);
            
            if ($insert_stmt->execute()) {
                header("Location: index.php?success=Assignment created successfully!");
                exit();
            } else {
                $error = "Error creating assignment: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Create Teacher Assignment';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4>Create Teacher Assignment</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Teacher <span class="required-star">*</span></label>
                <select name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php foreach($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacher_id'] ?>" <?= (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['teacher_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['teacher_name']) ?> (<?= htmlspecialchars($teacher['teacher_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Course <span class="required-star">*</span></label>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach($courses as $course): ?>
                        <option value="<?= $course['course_id'] ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?> (<?= $course['credit_hours'] ?> Credits)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Semester <span class="required-star">*</span></label>
                <select name="semester_id" required>
                    <option value="">Select Semester</option>
                    <?php foreach($semesters as $semester): ?>
                        <option value="<?= $semester['semester_id'] ?>" <?= (isset($_POST['semester_id']) && $_POST['semester_id'] == $semester['semester_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($semester['semester_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Session <span class="required-star">*</span></label>
                <select name="session_id" required>
                    <option value="">Select Session</option>
                    <?php foreach($sessions as $session): ?>
                        <option value="<?= $session['session_id'] ?>" <?= (isset($_POST['session_id']) && $_POST['session_id'] == $session['session_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($session['session_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Section <span class="required-star">*</span></label>
                <input type="text" name="section" placeholder="e.g., A" required value="<?= htmlspecialchars($_POST['section'] ?? 'A') ?>">
                <div class="hint">Section letter (A, B, C, etc.)</div>
            </div>
            <div class="form-group">
                <label>Assignment Type</label>
                <div style="margin-top:8px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                        <input type="checkbox" name="is_primary" value="1" <?= (isset($_POST['is_primary']) && $_POST['is_primary']) ? 'checked' : '' ?>>
                        Mark as Primary Instructor
                    </label>
                    <div class="hint" style="margin-top:4px;">Check if this teacher is the primary instructor for this course</div>
                </div>
            </div>
        </div>

        <div class="form-group" style="max-width:280px;">
            <label>Status</label>
            <select name="status">
                <option value="Active" <?= (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Assignment</button>
            <button type="reset" class="btn btn-outline">Reset</button>
            <a href="index.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
