<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php?error=Invalid ID"); exit; }

$sql = "SELECT t.*, c.course_code, c.course_name as course_title, c.credit_hours, tch.teacher_name, tch.specialization, tch.email as teacher_email, tch.phone as teacher_phone, s.semester_name, ses.session_name, d.department_name, p.program_name FROM timetable t LEFT JOIN courses c ON t.course_id = c.course_id LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id LEFT JOIN semesters s ON t.semester_id = s.semester_id LEFT JOIN sessions ses ON t.session_id = ses.session_id LEFT JOIN departments d ON c.department_id = d.department_id LEFT JOIN programs p ON c.program_id = p.program_id WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();
if (!$class) { header("Location: index.php?error=Record not found"); exit; }

require_once __DIR__ . '/../includes/header.php';
$page_title = 'View Class Details';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-eye"></i> Class Details</h4>
    <div class="page-header-actions">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-calendar-alt"></i> Class Schedule Details</h5>
    </div>
    <div class="card-content">
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-calendar-day"></i> Day</div>
            <div class="detail-value"><span class="status-badge Active"><?= htmlspecialchars($class['day_of_week']) ?></span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-clock"></i> Time</div>
            <div class="detail-value"><strong><?= date('g:i A', strtotime($class['start_time'])) ?></strong> to <strong><?= date('g:i A', strtotime($class['end_time'])) ?></strong></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-book"></i> Course</div>
            <div class="detail-value"><strong><?= htmlspecialchars($class['course_title']) ?></strong><br><small style="color:var(--accent);"><?= htmlspecialchars($class['course_code']) ?></small><?php if($class['credit_hours']): ?><span style="background:var(--accent-light);color:var(--accent);border:1px solid var(--info-border);border-radius:999px;font-size:11px;font-weight:600;padding:2px 8px;margin-left:8px;"><?= $class['credit_hours'] ?> Credits</span><?php endif; ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-user-tie"></i> Teacher</div>
            <div class="detail-value"><strong><?= htmlspecialchars($class['teacher_name']) ?></strong><?php if($class['specialization']): ?><br><small style="color:var(--muted);"><?= htmlspecialchars($class['specialization']) ?></small><?php endif; ?><?php if($class['teacher_email']): ?><br><small><i class="fas fa-envelope" style="margin-right:4px;"></i><?= htmlspecialchars($class['teacher_email']) ?></small><?php endif; ?><?php if($class['teacher_phone']): ?><br><small><i class="fas fa-phone" style="margin-right:4px;"></i><?= htmlspecialchars($class['teacher_phone']) ?></small><?php endif; ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-door-open"></i> Room</div>
            <div class="detail-value"><strong><?= htmlspecialchars($class['room_no']) ?></strong></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-users"></i> Section</div>
            <div class="detail-value"><span class="status-badge Inactive" style="background:var(--warning-bg);color:#92400e;border-color:var(--warning-border);"><?= htmlspecialchars($class['section']) ?></span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-graduation-cap"></i> Semester</div>
            <div class="detail-value"><?= htmlspecialchars($class['semester_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-calendar-alt"></i> Session</div>
            <div class="detail-value"><?= htmlspecialchars($class['session_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-building"></i> Department</div>
            <div class="detail-value"><?= htmlspecialchars($class['department_name'] ?? 'N/A') ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fas fa-tag"></i> Program</div>
            <div class="detail-value"><?= htmlspecialchars($class['program_name'] ?? 'N/A') ?></div>
        </div>
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Class</a>
    <a href="delete.php?id=<?= $id ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this class?')"><i class="fas fa-trash"></i> Delete Class</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
