<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . 'login.php'); exit; }
$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');
if (!in_array($role, ['sso', 'admin'])) { header('Location: ' . BASE_URL . 'dashboard.php'); exit; }

$conn = getConnection();
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;
if ($section_id <= 0) { header("Location: index.php?error=Invalid section ID"); exit; }

$section_query = "SELECT s.*, p.program_name, sm.semester_name FROM sections s LEFT JOIN programs p ON s.program_id = p.program_id LEFT JOIN semesters sm ON s.semester_id = sm.semester_id WHERE s.section_id = $section_id";
$section_result = mysqli_query($conn, $section_query);
$section = mysqli_fetch_assoc($section_result);
if (!$section) { header("Location: index.php?error=Section not found"); exit; }

$students_query = "SELECT s.student_id, s.roll_no, s.enrollment_date, u.full_name, u.email, u.phone, p.program_name FROM students s LEFT JOIN users u ON s.user_id = u.user_id LEFT JOIN programs p ON s.program_id = p.program_id WHERE s.section_id = $section_id ORDER BY u.full_name";
$students_result = mysqli_query($conn, $students_query);
$students = [];
if ($students_result) { while ($row = mysqli_fetch_assoc($students_result)) { $students[] = $row; } }

include __DIR__ . '/../includes/header.php';
$page_title = 'Section Students';
include __DIR__ . '/../includes/sidebar.php';
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="page-header">
    <h4><i class="fas fa-user-graduate"></i> Students in Section</h4>
    <div class="page-header-actions">
        <a href="enroll_student.php?section=<?= $section_id ?>" class="btn btn-success"><i class="fas fa-user-plus"></i> Add Student</a>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Sections</a>
    </div>
</div>

<?php if ($success_msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
<?php if ($error_msg): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:16px;">
    <div class="card-content" style="padding:20px;">
        <div class="grid-2">
            <div>
                <div class="detail-row">
                    <div class="detail-label">Section</div>
                    <div class="detail-value"><strong><?= htmlspecialchars($section['section_name']) ?></strong>
                        <span class="status-badge <?= ($section['status'] ?? 'Active') == 'Active' ? 'Active' : 'Inactive' ?>" style="margin-left:8px;"><?= htmlspecialchars($section['status'] ?? 'Active') ?></span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Program</div>
                    <div class="detail-value"><?= htmlspecialchars($section['program_name'] ?? 'N/A') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Semester</div>
                    <div class="detail-value"><?= htmlspecialchars($section['semester_name'] ?? $section['semester_id'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div>
                <div class="detail-row">
                    <div class="detail-label">Enrolled</div>
                    <div class="detail-value"><strong><?= count($students) ?> / <?= $section['capacity'] ?? 30 ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($students)): ?>
    <div class="card">
        <div class="card-header"><h5><i class="fas fa-list"></i> Students (<?= count($students) ?>)</h5></div>
        <div style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Enrollment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($students as $student): ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><?= htmlspecialchars($student['roll_no'] ?? 'N/A') ?></td>
                                <td><strong><?= htmlspecialchars($student['full_name'] ?? 'N/A') ?></strong><br><small style="color:var(--muted);"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></small></td>
                                <td><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                                <td><?= isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A' ?></td>
                                <td>
                                    <a href="remove_from_section.php?student_id=<?= urlencode($student['student_id']) ?>&section_id=<?= $section_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this student from this section?')"><i class="fas fa-user-minus"></i> Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>No students enrolled in this section yet.</p>
        <a href="enroll_student.php?section=<?= $section_id ?>" class="btn btn-success"><i class="fas fa-user-plus"></i> Enroll Student</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
