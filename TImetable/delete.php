<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php?error=Invalid ID"); exit; }

$sql = "SELECT t.*, c.course_name, c.course_code, tch.teacher_name, s.semester_name FROM timetable t LEFT JOIN courses c ON t.course_id = c.course_id LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id LEFT JOIN semesters s ON t.semester_id = s.semester_id WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();
if (!$class) { header("Location: index.php?error=Record not found"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $delete_sql = "DELETE FROM timetable WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) { header("Location: index.php?success=Class deleted successfully!"); exit; }
    else { $error = "Error deleting class: " . $conn->error; }
    $delete_stmt->close();
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Class';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-trash-alt" style="color:var(--danger);"></i> Delete Class</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="max-width:560px;margin:0 auto;">
    <div class="card-content" style="padding:32px;text-align:center;">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--danger-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-exclamation-triangle" style="font-size:28px;color:var(--danger);"></i>
        </div>
        <h5 style="margin-bottom:4px;">Are you sure you want to delete this class?</h5>
        <p style="color:var(--muted);margin-bottom:24px;">This action cannot be undone.</p>

        <div style="text-align:left;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:24px;">
            <div class="detail-row">
                <div class="detail-label">Course</div>
                <div class="detail-value"><?= htmlspecialchars($class['course_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Code</div>
                <div class="detail-value"><strong style="color:var(--accent);"><?= htmlspecialchars($class['course_code']) ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Teacher</div>
                <div class="detail-value"><?= htmlspecialchars($class['teacher_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Day</div>
                <div class="detail-value"><?= htmlspecialchars($class['day_of_week']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Time</div>
                <div class="detail-value"><?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Room</div>
                <div class="detail-value"><?= htmlspecialchars($class['room_no']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Section</div>
                <div class="detail-value"><?= htmlspecialchars($class['section']) ?></div>
            </div>
        </div>

        <form method="POST" action="">
            <div style="display:flex;gap:12px;justify-content:center;">
                <button type="submit" name="confirm" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
                <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
