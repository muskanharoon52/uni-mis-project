<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

if ($section_id <= 0) {
    header("Location: index.php?error=Invalid section ID");
    exit;
}

// Get section details
$section_query = "SELECT s.*, p.program_name, sm.semester_name 
                  FROM sections s
                  LEFT JOIN programs p ON s.program_id = p.program_id
                  LEFT JOIN semesters sm ON s.semester_id = sm.semester_id
                  WHERE s.section_id = ?";
$section_stmt = $conn->prepare($section_query);
$section_stmt->bind_param("i", $section_id);
$section_stmt->execute();
$section_result = $section_stmt->get_result();
$section = $section_result->fetch_assoc();
$section_stmt->close();

if (!$section) {
    header("Location: index.php?error=Section not found");
    exit;
}

// Fetch courses already assigned to this section
$assigned_query = "SELECT sc.*, c.course_code, c.course_name, c.credit_hours, t.teacher_name
                   FROM section_courses sc
                   LEFT JOIN courses c ON sc.course_id = c.course_id
                   LEFT JOIN teachers t ON sc.teacher_id = t.teacher_id
                   WHERE sc.section_id = ?";
$assigned_stmt = $conn->prepare($assigned_query);
$assigned_stmt->bind_param("i", $section_id);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$assigned_courses = $assigned_result ? $assigned_result->fetch_all(MYSQLI_ASSOC) : [];
$assigned_stmt->close();

// Fetch all courses (excluding already assigned)
$courses_query = "SELECT c.* FROM courses c 
                  WHERE c.course_id NOT IN (
                      SELECT course_id FROM section_courses WHERE section_id = ?
                  )
                  ORDER BY c.course_code";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $section_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];
$courses_stmt->close();

// Fetch teachers
$teachers = $conn->query("SELECT teacher_id, teacher_name FROM teachers WHERE status = 'Active' ORDER BY teacher_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'] ?? 0;
    $teacher_id = (int)$_POST['teacher_id'] ?? 0;
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if ($course_id <= 0) $error = "Please select a course";
    elseif ($teacher_id <= 0) $error = "Please select a teacher";

    if (empty($error)) {
        $insert_query = "INSERT INTO section_courses (section_id, course_id, teacher_id, is_primary) 
                        VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiii", $section_id, $course_id, $teacher_id, $is_primary);
        
        if ($insert_stmt->execute()) {
            header("Location: assign_course.php?section=" . $section_id . "&success=Course assigned successfully!");
            exit;
        } else {
            $error = "Error assigning course: " . $conn->error;
        }
        $insert_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Assign Course to Section';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .assign-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .section-info {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .form-container {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .course-list {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .course-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f2f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .course-item:last-child {
        border-bottom: none;
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    @media (max-width: 768px) {
        .assign-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="assign-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-book"></i> Assign Courses to Section</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Section Info -->
        <div class="section-info">
            <div class="row">
                <div class="col-md-3">
                    <strong>Section:</strong> <?= htmlspecialchars($section['section_name']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Program:</strong> <?= htmlspecialchars($section['program_name']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Semester:</strong> <?= htmlspecialchars($section['semester_name']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Students:</strong> <?= $section['enrolled_count'] ?>/<?= $section['capacity'] ?>
                </div>
            </div>
        </div>

        <!-- Assign New Course -->
        <div class="form-container">
            <h6 class="mb-3"><i class="fas fa-plus-circle"></i> Assign New Course</h6>
            <form method="POST" action="" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Course <span class="required-star">*</span></label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>">
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                (<?= $course['credit_hours'] ?> Credits)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($courses)): ?>
                        <small class="text-success">All available courses have been assigned!</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teacher <span class="required-star">*</span></label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Select Teacher</option>
                        <?php while($row = $teachers->fetch_assoc()): ?>
                            <option value="<?= $row['teacher_id'] ?>">
                                <?= htmlspecialchars($row['teacher_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary">
                        <label class="form-check-label" for="is_primary">
                            Primary Instructor
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" <?= empty($courses) ? 'disabled' : '' ?>>
                        <i class="fas fa-plus"></i> Assign Course
                    </button>
                </div>
            </form>
        </div>

        <!-- Assigned Courses -->
        <div class="course-list">
            <h6 class="mb-3"><i class="fas fa-list"></i> Assigned Courses</h6>
            <?php if (!empty($assigned_courses)): ?>
                <?php foreach($assigned_courses as $course): ?>
                    <div class="course-item">
                        <div>
                            <strong><?= htmlspecialchars($course['course_code']) ?></strong>
                            - <?= htmlspecialchars($course['course_name']) ?>
                            <span class="badge bg-info ms-2"><?= $course['credit_hours'] ?> Credits</span>
                            <?php if ($course['is_primary']): ?>
                                <span class="badge bg-warning text-dark">Primary</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted">Teacher: <?= htmlspecialchars($course['teacher_name'] ?? 'Not Assigned') ?></small>
                        </div>
                        <div>
                            <a href="remove_course.php?section=<?= $section_id ?>&course=<?= $course['course_id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Remove this course from section?')">
                                <i class="fas fa-times"></i> Remove
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted">No courses assigned to this section yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>