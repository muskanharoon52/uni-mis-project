<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!function_exists('isLoggedIn')) { die("isLoggedIn() function not found in auth.php"); }
if (!isLoggedIn()) { header('Location: /uni-mis-project/'); exit; }
$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');
if (!in_array($role, ['sso', 'admin'])) { header('Location: ../dashboard.php'); exit; }

$conn = getConnection();
$error = '';
$success = '';

$teachers = $conn->query("SELECT teacher_id, teacher_name FROM teachers WHERE status = 'Active' ORDER BY teacher_name");
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$sessions = $conn->query("SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room_no = trim($_POST['room_no'] ?? '');
    $section = trim($_POST['section'] ?? '');

    if ($course_id <= 0) $error = "Please select a course";
    elseif ($teacher_id <= 0) $error = "Please select a teacher";
    elseif ($semester_id <= 0) $error = "Please select a semester";
    elseif ($session_id <= 0) $error = "Please select a session";
    elseif (empty($day_of_week)) $error = "Please select a day";
    elseif (empty($start_time)) $error = "Please select start time";
    elseif (empty($end_time)) $error = "Please select end time";
    elseif (empty($room_no)) $error = "Please enter room number";
    elseif (empty($section)) $error = "Please enter section";

    if (empty($error)) {
        $check_sql = "SELECT id FROM timetable WHERE day_of_week = ? AND room_no = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssssss", $day_of_week, $room_no, $start_time, $start_time, $end_time, $end_time);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Room is already booked at this time slot!";
        } else {
            $check_teacher_sql = "SELECT id FROM timetable WHERE day_of_week = ? AND teacher_id = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
            $check_teacher_stmt = $conn->prepare($check_teacher_sql);
            $check_teacher_stmt->bind_param("sissss", $day_of_week, $teacher_id, $start_time, $start_time, $end_time, $end_time);
            $check_teacher_stmt->execute();
            $check_teacher_result = $check_teacher_stmt->get_result();

            if ($check_teacher_result->num_rows > 0) {
                $error = "Teacher is already assigned to another class at this time!";
            } else {
                $insert_sql = "INSERT INTO timetable (course_id, teacher_id, semester_id, session_id, day_of_week, start_time, end_time, room_no, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiiisssss", $course_id, $teacher_id, $semester_id, $session_id, $day_of_week, $start_time, $end_time, $room_no, $section);
                if ($insert_stmt->execute()) { $insert_stmt->close(); header("Location: index.php?success=Class added successfully!"); exit; }
                else { $error = "Error adding class: " . $conn->error; }
                $insert_stmt->close();
            }
            $check_teacher_stmt->close();
        }
        $check_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Timetable Entry';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-plus-circle"></i> Add New Class</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Course <span class="required-star">*</span></label>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while($row = $courses->fetch_assoc()): ?>
                        <option value="<?= $row['course_id'] ?>" <?= ($_POST['course_id'] ?? '') == $row['course_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Teacher <span class="required-star">*</span></label>
                <select name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php while($row = $teachers->fetch_assoc()): ?>
                        <option value="<?= $row['teacher_id'] ?>" <?= ($_POST['teacher_id'] ?? '') == $row['teacher_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['teacher_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Semester <span class="required-star">*</span></label>
                <select name="semester_id" required>
                    <option value="">Select Semester</option>
                    <?php while($row = $semesters->fetch_assoc()): ?>
                        <option value="<?= $row['semester_id'] ?>" <?= ($_POST['semester_id'] ?? '') == $row['semester_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['semester_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Session <span class="required-star">*</span></label>
                <select name="session_id" required>
                    <option value="">Select Session</option>
                    <?php while($row = $sessions->fetch_assoc()): ?>
                        <option value="<?= $row['session_id'] ?>" <?= ($_POST['session_id'] ?? '') == $row['session_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['session_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="form-group">
                <label>Day <span class="required-star">*</span></label>
                <select name="day_of_week" required>
                    <option value="">Select Day</option>
                    <option value="Monday" <?= ($_POST['day_of_week'] ?? '') == 'Monday' ? 'selected' : '' ?>>Monday</option>
                    <option value="Tuesday" <?= ($_POST['day_of_week'] ?? '') == 'Tuesday' ? 'selected' : '' ?>>Tuesday</option>
                    <option value="Wednesday" <?= ($_POST['day_of_week'] ?? '') == 'Wednesday' ? 'selected' : '' ?>>Wednesday</option>
                    <option value="Thursday" <?= ($_POST['day_of_week'] ?? '') == 'Thursday' ? 'selected' : '' ?>>Thursday</option>
                    <option value="Friday" <?= ($_POST['day_of_week'] ?? '') == 'Friday' ? 'selected' : '' ?>>Friday</option>
                    <option value="Saturday" <?= ($_POST['day_of_week'] ?? '') == 'Saturday' ? 'selected' : '' ?>>Saturday</option>
                    <option value="Sunday" <?= ($_POST['day_of_week'] ?? '') == 'Sunday' ? 'selected' : '' ?>>Sunday</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start Time <span class="required-star">*</span></label>
                <input type="time" name="start_time" value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>End Time <span class="required-star">*</span></label>
                <input type="time" name="end_time" value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Room No <span class="required-star">*</span></label>
                <input type="text" name="room_no" placeholder="e.g., Room 101" value="<?= htmlspecialchars($_POST['room_no'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Section <span class="required-star">*</span></label>
                <input type="text" name="section" placeholder="e.g., A, B, C" value="<?= htmlspecialchars($_POST['section'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Class</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
