<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!function_exists('isLoggedIn')) { die("isLoggedIn() function not found in auth.php"); }
if (!isLoggedIn()) { header('Location: ../modules/sso/login.php'); exit; }

$conn = getConnection();
$error = '';
$success = '';

$student_id = isset($_GET['student']) ? $_GET['student'] : '';
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

$sections_query = "SELECT s.*, p.program_name, sm.semester_name FROM sections s LEFT JOIN programs p ON s.program_id = p.program_id LEFT JOIN semesters sm ON s.semester_id = sm.semester_id WHERE s.status = 'Active' ORDER BY p.program_name, s.section_name";
$sections_result = $conn->query($sections_query);
$sections = $sections_result ? $sections_result->fetch_all(MYSQLI_ASSOC) : [];

$students_query = "SELECT s.student_id, s.roll_no, s.full_name, p.program_name FROM students s LEFT JOIN programs p ON s.program_id = p.program_id WHERE s.status = 'active' ORDER BY s.full_name";
$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $section_id = (int)$_POST['section_id'] ?? 0;
    $semester_id = (int)$_POST['semester_id'] ?? 0;

    if (empty($student_id)) $error = "Please select a student";
    elseif ($section_id <= 0) $error = "Please select a section";
    elseif ($semester_id <= 0) $error = "Please select a semester";

    if (empty($error)) {
        $check_query = "SELECT enrollment_id FROM student_enrollments WHERE student_id = ? AND section_id = ? AND semester_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("sii", $student_id, $section_id, $semester_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Student is already enrolled in this section!";
        } else {
            $capacity_query = "SELECT capacity, enrolled_count FROM sections WHERE section_id = ?";
            $capacity_stmt = $conn->prepare($capacity_query);
            $capacity_stmt->bind_param("i", $section_id);
            $capacity_stmt->execute();
            $capacity_result = $capacity_stmt->get_result();
            $section_data = $capacity_result->fetch_assoc();
            $capacity_stmt->close();

            if ($section_data && $section_data['enrolled_count'] >= $section_data['capacity']) {
                $error = "Section is full! Capacity: " . $section_data['capacity'];
            } else {
                $conn->begin_transaction();
                try {
                    $insert_query = "INSERT INTO student_enrollments (student_id, section_id, semester_id, enrollment_date, status) VALUES (?, ?, ?, CURDATE(), 'Enrolled')";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("sii", $student_id, $section_id, $semester_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();

                    $update_query = "UPDATE sections SET enrolled_count = enrolled_count + 1 WHERE section_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $section_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    $update_student = "UPDATE students SET section_id = ?, semester = ? WHERE student_id = ?";
                    $update_student_stmt = $conn->prepare($update_student);
                    $update_student_stmt->bind_param("iis", $section_id, $semester_id, $student_id);
                    $update_student_stmt->execute();
                    $update_student_stmt->close();

                    $conn->commit();
                    header("Location: index.php?success=Student enrolled successfully!");
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error enrolling student: " . $e->getMessage();
                }
            }
        }
        $check_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Enroll Student';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-user-plus"></i> Enroll Student in Section</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label>Student <span class="required-star">*</span></label>
            <select name="student_id" required>
                <option value="">Select Student</option>
                <?php foreach($students as $student): ?>
                    <option value="<?= $student['student_id'] ?>" <?= $student_id == $student['student_id'] ? 'selected' : '' ?>><?= htmlspecialchars($student['full_name'] ?? 'Unknown') ?> (<?= htmlspecialchars($student['student_id']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Section <span class="required-star">*</span></label>
            <select name="section_id" required>
                <option value="">Select Section</option>
                <?php foreach($sections as $section): ?>
                    <option value="<?= $section['section_id'] ?>" <?= $section_id == $section['section_id'] ? 'selected' : '' ?>><?= htmlspecialchars($section['section_name']) ?> - <?= htmlspecialchars($section['program_name'] ?? 'N/A') ?> (<?= htmlspecialchars($section['semester_name'] ?? 'N/A') ?>) [<?= $section['enrolled_count'] ?>/<?= $section['capacity'] ?>]</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Semester <span class="required-star">*</span></label>
            <select name="semester_id" required>
                <option value="">Select Semester</option>
                <?php $semesters_query = "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name"; $semesters_result = $conn->query($semesters_query); if ($semesters_result) { while($row = $semesters_result->fetch_assoc()): ?>
                    <option value="<?= $row['semester_id'] ?>"><?= htmlspecialchars($row['semester_name']) ?></option>
                <?php endwhile; } ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Enroll Student</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
