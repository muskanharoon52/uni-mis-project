<?php
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . 'login.php'); exit; }

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';
$conn = getConnection();

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
$total_students = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses");
$total_courses = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers");
$total_teachers = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM applications");
$total_applications = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$recent_students = [];
$recent_result = mysqli_query($conn, "SELECT s.student_id, s.roll_no, u.full_name, s.enrollment_date 
                 FROM students s LEFT JOIN users u ON s.user_id = u.user_id 
                 ORDER BY s.enrollment_date DESC LIMIT 5");
if ($recent_result) { while ($row = mysqli_fetch_assoc($recent_result)) { $recent_students[] = $row; } }

$recent_applications = [];
$app_result = mysqli_query($conn, "SELECT a.*, s.full_name as student_name 
              FROM applications a LEFT JOIN students s ON a.student_id = s.student_id 
              ORDER BY a.created_at DESC LIMIT 5");
if ($app_result) { while ($row = mysqli_fetch_assoc($app_result)) { $recent_applications[] = $row; } }

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_students ?></p><p class="label">Students</p></div>
            <div class="icon" style="background:var(--info-light);color:var(--info);"><i class="fas fa-user-graduate"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_courses ?></p><p class="label">Courses</p></div>
            <div class="icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-book"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_teachers ?></p><p class="label">Teachers</p></div>
            <div class="icon" style="background:var(--warning-light);color:var(--warning);"><i class="fas fa-chalkboard-teacher"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_applications ?></p><p class="label">Applications</p></div>
            <div class="icon" style="background:var(--danger-light);color:var(--danger);"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <a href="<?= BASE_URL ?>students/export.php" style="text-decoration:none;">
        <div class="card" style="text-align:center;cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='var(--shadow)'">
            <div style="font-size:1.8rem;color:var(--accent);margin-bottom:8px;"><i class="fas fa-users"></i></div>
            <div style="font-weight:600;margin-bottom:4px;">Student Report</div>
            <div class="muted" style="font-size:.82rem;">View and export student data</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>fee_per_course/report.php" style="text-decoration:none;">
        <div class="card" style="text-align:center;cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='var(--shadow)'">
            <div style="font-size:1.8rem;color:var(--success);margin-bottom:8px;"><i class="fas fa-money-bill-wave"></i></div>
            <div style="font-weight:600;margin-bottom:4px;">Fee Report</div>
            <div class="muted" style="font-size:.82rem;">View fee collection status</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>attendance/report.php" style="text-decoration:none;">
        <div class="card" style="text-align:center;cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='var(--shadow)'">
            <div style="font-size:1.8rem;color:var(--warning);margin-bottom:8px;"><i class="fas fa-clipboard-check"></i></div>
            <div style="font-weight:600;margin-bottom:4px;">Attendance Report</div>
            <div class="muted" style="font-size:.82rem;">View attendance summary</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>applications/index.php" style="text-decoration:none;">
        <div class="card" style="text-align:center;cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='var(--shadow)'">
            <div style="font-size:1.8rem;color:var(--info);margin-bottom:8px;"><i class="fas fa-file-alt"></i></div>
            <div style="font-weight:600;margin-bottom:4px;">Applications Report</div>
            <div class="muted" style="font-size:.82rem;">View application status</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>student_enrollment/index.php" style="text-decoration:none;">
        <div class="card" style="text-align:center;cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='var(--shadow)'">
            <div style="font-size:1.8rem;color:var(--muted);margin-bottom:8px;"><i class="fas fa-user-graduate"></i></div>
            <div style="font-weight:600;margin-bottom:4px;">Enrollment Report</div>
            <div class="muted" style="font-size:.82rem;">View student enrollments</div>
        </div>
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header"><h3>Recent Students</h3></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (!empty($recent_students)): ?>
                        <?php foreach ($recent_students as $student): ?>
                        <tr>
                            <td class="muted"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($student['full_name'] ?? 'N/A') ?></td>
                            <td class="muted"><?= isset($student['enrollment_date']) ? date('M j, Y', strtotime($student['enrollment_date'])) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="muted text-center" style="padding:20px;">No students found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Recent Applications</h3></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Student</th><th>Type</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!empty($recent_applications)): ?>
                        <?php foreach ($recent_applications as $app): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($app['student_name'] ?? 'N/A') ?></td>
                            <td class="muted"><?= htmlspecialchars($app['application_type'] ?? 'N/A') ?></td>
                            <td>
                                <?php
                                $s = $app['status'] ?? 'Pending';
                                $bc = $s == 'Approved' ? 'badge-active' : ($s == 'Rejected' ? 'badge-inactive' : 'badge-pending');
                                ?>
                                <span class="badge <?= $bc ?>"><?= $s ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="muted text-center" style="padding:20px;">No applications found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
