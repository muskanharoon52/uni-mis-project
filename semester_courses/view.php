<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id == 0) { echo "<script>window.location.href='index.php?error=Invalid course ID';</script>"; exit; }

$course_query = "SELECT c.*, d.department_name, p.program_name FROM courses c LEFT JOIN departments d ON c.department_id = d.department_id LEFT JOIN programs p ON c.program_id = p.program_id WHERE c.course_id = ?";
$course_stmt = $conn->prepare($course_query);
if ($course_stmt === false) { die("Error preparing course query: " . $conn->error); }
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course = $course_result->fetch_assoc();
$course_stmt->close();
if (!$course) { echo "<script>window.location.href='index.php?error=Course not found';</script>"; exit; }

$assignments_query = "SELECT sc.*, s.semester_name FROM semester_courses sc LEFT JOIN semesters s ON sc.semester_id = s.semester_id WHERE sc.course_id = ? ORDER BY s.semester_name";
$assignments_stmt = $conn->prepare($assignments_query);
if ($assignments_stmt === false) { die("Error preparing assignments query: " . $conn->error); }
$assignments_stmt->bind_param("i", $course_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
$assignments = $assignments_result ? $assignments_result->fetch_all(MYSQLI_ASSOC) : [];
$assignments_stmt->close();

require_once __DIR__ . '/../includes/header.php';
$page_title = 'View Course Assignments';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-eye"></i> View Course Assignments</h4>
    <div class="page-header-actions">
        <a href="assign.php?course_id=<?= $course_id ?>" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Assign to Semester</a>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:16px;">
    <div class="card-content" style="padding:20px;">
        <div class="grid-2">
            <div>
                <div class="detail-row">
                    <div class="detail-label">Course Code</div>
                    <div class="detail-value"><strong style="color:var(--accent);font-size:1.1rem;"><?= htmlspecialchars($course['course_code']) ?></strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Course Title</div>
                    <div class="detail-value"><?= htmlspecialchars($course['course_name']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Credit Hours</div>
                    <div class="detail-value"><?= $course['credit_hours'] ?> Credits</div>
                </div>
            </div>
            <div>
                <div class="detail-row">
                    <div class="detail-label">Department</div>
                    <div class="detail-value"><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></div>
                </div>
                <?php if (!empty($course['program_name'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Program</div>
                    <div class="detail-value"><?= htmlspecialchars($course['program_name']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($course['description'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value"><?= htmlspecialchars($course['description']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-calendar-alt"></i> Assigned Semesters (<?= count($assignments) ?>)</h5></div>
    <div style="padding:0;">
        <?php if (!empty($assignments)): ?>
            <?php foreach($assignments as $assignment): $assignment_id = $assignment['id'] ?? $assignment['semester_course_id'] ?? $assignment['assignment_id'] ?? 0; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-calendar-alt" style="color:var(--accent);"></i>
                            <strong><?= htmlspecialchars($assignment['semester_name'] ?? 'N/A') ?></strong>
                        </div>
                        <?php if (!empty($assignment['year'])): ?>
                            <small style="color:var(--muted);margin-left:24px;">Year: <?= htmlspecialchars($assignment['year']) ?></small>
                        <?php endif; ?>
                        <small style="color:var(--muted);margin-left:24px;">Assigned: <?= date('d M Y', strtotime($assignment['created_at'] ?? date('Y-m-d'))) ?></small>
                    </div>
                    <div>
                        <?php if ($assignment_id > 0): ?>
                            <a href="remove.php?assignment_id=<?= $assignment_id ?>&course_id=<?= $course_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this course from this semester?')"><i class="fas fa-trash"></i> Remove</a>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:.8rem;">Cannot remove</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fas fa-calendar-alt"></i>
                <h5>No Assignments</h5>
                <p>This course is not assigned to any semester yet.</p>
                <a href="assign.php?course_id=<?= $course_id ?>" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Assign to Semester</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
