<?php
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . 'login.php'); exit; }

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin', 'account'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'structures';

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_structures");
$total_structures = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM course_fees");
$total_course_fees = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$result = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_records");
$total_collected = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM fee_records");
$total_fee_records = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['count'] : 0;

$structures = [];
$structures_result = mysqli_query($conn, "SELECT fs.*, p.program_name, s.semester_name, ses.session_name 
                     FROM fee_structures fs
                     LEFT JOIN programs p ON fs.program_id = p.program_id
                     LEFT JOIN semesters s ON fs.semester_id = s.semester_id
                     LEFT JOIN sessions ses ON fs.session_id = ses.session_id
                     ORDER BY fs.created_at DESC");
if ($structures_result) { while ($row = mysqli_fetch_assoc($structures_result)) { $structures[] = $row; } }

$course_fees = [];
$course_fees_result = mysqli_query($conn, "SELECT cf.*, c.course_code, c.course_name, c.credit_hours 
                      FROM course_fees cf
                      LEFT JOIN courses c ON cf.course_id = c.course_id
                      ORDER BY cf.created_at DESC");
if ($course_fees_result) { while ($row = mysqli_fetch_assoc($course_fees_result)) { $course_fees[] = $row; } }

$pageTitle = 'Fee Management';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_structures ?></p><p class="label">Fee Structures</p></div>
            <div class="icon" style="background:var(--info-light);color:var(--info);"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_course_fees ?></p><p class="label">Course Fees</p></div>
            <div class="icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-book"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number">Rs. <?= number_format($total_collected, 0) ?></p><p class="label">Total Collected</p></div>
            <div class="icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><p class="number"><?= $total_fee_records ?></p><p class="label">Total Records</p></div>
            <div class="icon" style="background:var(--danger-light);color:var(--danger);"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
    </div>
</div>

<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--border);">
    <a href="?tab=structures" class="tab-btn <?= $active_tab == 'structures' ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> Fee Structures</a>
    <a href="?tab=course_fees" class="tab-btn <?= $active_tab == 'course_fees' ? 'active' : '' ?>"><i class="fas fa-book"></i> Fee Per Course</a>
</div>

<?php if ($active_tab == 'structures'): ?>
<div>
    <div class="card-header" style="border-radius:var(--radius) var(--radius) 0 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>Fee Structures List</h3>
            <div class="actions">
                <a href="../scholarship/calculate.php" class="btn btn-outline btn-sm">GPA Scholarship</a>
                <a href="assign_to_student.php" class="btn btn-outline btn-sm">Assign to Student</a>
                <a href="structure_add.php" class="btn btn-primary btn-sm">+ Add Fee Structure</a>
            </div>
        </div>
    </div>
    <?php if (!empty($structures)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>#</th><th>Program</th><th>Session</th><th>Semester</th><th>Total Amount</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($structures as $fs): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($fs['program_name'] ?? 'N/A') ?></td>
                    <td class="muted"><?= htmlspecialchars($fs['session_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($fs['semester_name'] ?? 'N/A') ?></td>
                    <td style="font-weight:700;">Rs. <?= number_format($fs['total_amount'] ?? 0, 2) ?></td>
                    <td><span class="badge <?= ($fs['status'] ?? 'Active') == 'Active' ? 'badge-active' : 'badge-outline' ?>"><?= $fs['status'] ?? 'Active' ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="structure_edit.php?id=<?= $fs['fee_structure_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="structure_delete.php?id=<?= $fs['fee_structure_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this fee structure?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="muted text-center" style="padding:32px;">
            <div style="font-size:2rem;margin-bottom:8px;">&#128196;</div>
            <p>No fee structures found.</p>
            <a href="structure_add.php" class="btn btn-primary" style="margin-top:8px;">Create Fee Structure</a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($active_tab == 'course_fees'): ?>
<div>
    <div class="card-header" style="border-radius:var(--radius) var(--radius) 0 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>Fee Per Course List</h3>
            <a href="course_add.php" class="btn btn-primary btn-sm">+ Add Course Fee</a>
        </div>
    </div>
    <?php if (!empty($course_fees)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>#</th><th>Course</th><th>Fee Amount</th><th>Fee Type</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($course_fees as $cf): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($cf['course_code'] ?? 'N/A') ?></strong><br><span class="muted" style="font-size:.82rem;"><?= htmlspecialchars($cf['course_name'] ?? 'N/A') ?></span></td>
                    <td style="font-weight:700;">Rs. <?= number_format($cf['fee_amount'] ?? 0, 2) ?></td>
                    <td><span class="badge badge-outline"><?= htmlspecialchars($cf['fee_type'] ?? 'Fixed') ?></span></td>
                    <td><span class="badge <?= ($cf['is_active'] ?? 1) == 1 ? 'badge-active' : 'badge-outline' ?>"><?= ($cf['is_active'] ?? 1) == 1 ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="course_edit.php?id=<?= $cf['fee_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="course_delete.php?id=<?= $cf['fee_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course fee?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="muted text-center" style="padding:32px;">
            <div style="font-size:2rem;margin-bottom:8px;">&#128218;</div>
            <p>No course fees found.</p>
            <a href="course_add.php" class="btn btn-primary" style="margin-top:8px;">Add Course Fee</a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
