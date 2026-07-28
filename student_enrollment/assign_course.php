<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

if ($section_id <= 0) { header("Location: index.php?error=Invalid section ID"); exit; }

$section_query = "SELECT s.*, p.program_name, sm.semester_name FROM sections s LEFT JOIN programs p ON s.program_id = p.program_id LEFT JOIN semesters sm ON s.semester_id = sm.semester_id WHERE s.section_id = ?";
$section_stmt = $conn->prepare($section_query);
$section_stmt->bind_param("i", $section_id);
$section_stmt->execute();
$section_result = $section_stmt->get_result();
$section = $section_result->fetch_assoc();
$section_stmt->close();
if (!$section) { header("Location: index.php?error=Section not found"); exit; }

$assigned_query = "SELECT sc.*, c.course_code, c.course_name, c.credit_hours, t.teacher_name FROM section_courses sc LEFT JOIN courses c ON sc.course_id = c.course_id LEFT JOIN teachers t ON sc.teacher_id = t.teacher_id WHERE sc.section_id = ?";
$assigned_stmt = $conn->prepare($assigned_query);
$assigned_stmt->bind_param("i", $section_id);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$assigned_courses = $assigned_result ? $assigned_result->fetch_all(MYSQLI_ASSOC) : [];
$assigned_stmt->close();

$courses_query = "SELECT c.* FROM courses c WHERE c.course_id NOT IN (SELECT course_id FROM section_courses WHERE section_id = ?) ORDER BY c.course_code";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $section_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];
$courses_stmt->close();

$teachers = $conn->query("SELECT teacher_id, teacher_name FROM teachers WHERE status = 'Active' ORDER BY teacher_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'] ?? 0;
    $teacher_id = (int)$_POST['teacher_id'] ?? 0;
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if ($course_id <= 0) $error = "Please select a course";
    elseif ($teacher_id <= 0) $error = "Please select a teacher";

    if (empty($error)) {
        $insert_query = "INSERT INTO section_courses (section_id, course_id, teacher_id, is_primary) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiii", $section_id, $course_id, $teacher_id, $is_primary);
        if ($insert_stmt->execute()) { header("Location: assign_course.php?section=" . $section_id . "&success=Course assigned successfully!"); exit; }
        else { $error = "Error assigning course: " . $conn->error; }
        $insert_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Assign Course to Section';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-book"></i> Assign Courses to Section</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:16px;">
    <div class="card-content" style="padding:20px;">
        <div class="detail-row">
            <div class="detail-label">Section</div>
            <div class="detail-value"><strong><?= htmlspecialchars($section['section_name']) ?></strong></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Program</div>
            <div class="detail-value"><?= htmlspecialchars($section['program_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Semester</div>
            <div class="detail-value"><?= htmlspecialchars($section['semester_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Students</div>
            <div class="detail-value"><?= $section['enrolled_count'] ?>/<?= $section['capacity'] ?></div>
        </div>
    </div>
</div>

<div class="form-container">
    <h6 style="margin-bottom:16px;"><i class="fas fa-plus-circle"></i> Assign New Course</h6>
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Course <span class="required-star">*</span></label>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach($courses as $course): ?>
                        <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (<?= $course['credit_hours'] ?> Credits)</option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($courses)): ?>
                    <div class="hint" style="color:var(--success);">All available courses have been assigned!</div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Teacher <span class="required-star">*</span></label>
                <select name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php while($row = $teachers->fetch_assoc()): ?>
                        <option value="<?= $row['teacher_id'] ?>"><?= htmlspecialchars($row['teacher_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_primary" id="is_primary" style="width:auto;">
                Primary Instructor
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" <?= empty($courses) ? 'disabled' : '' ?>><i class="fas fa-plus"></i> Assign Course</button>
        </div>
    </form>
</div>

<div style="margin-top:24px;">
    <h6 style="margin-bottom:12px;"><i class="fas fa-list"></i> Assigned Courses</h6>
    <?php if (!empty($assigned_courses)): ?>
        <?php foreach($assigned_courses as $course): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;">
                <div>
                    <strong style="color:var(--accent);"><?= htmlspecialchars($course['course_code']) ?></strong> - <?= htmlspecialchars($course['course_name']) ?>
                    <span style="background:var(--accent-light);color:var(--accent);border:1px solid var(--info-border);border-radius:999px;font-size:11px;font-weight:600;padding:2px 8px;margin-left:8px;"><?= $course['credit_hours'] ?> Credits</span>
                    <?php if ($course['is_primary']): ?>
                        <span style="background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border);border-radius:999px;font-size:11px;font-weight:600;padding:2px 8px;margin-left:4px;">Primary</span>
                    <?php endif; ?>
                    <br><small style="color:var(--muted);">Teacher: <?= htmlspecialchars($course['teacher_name'] ?? 'Not Assigned') ?></small>
                </div>
                <a href="remove_course.php?section=<?= $section_id ?>&course=<?= $course['course_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this course from section?')"><i class="fas fa-times"></i> Remove</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:24px;">
            <p>No courses assigned to this section yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
