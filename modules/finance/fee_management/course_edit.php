<?php
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . 'login.php'); exit; }

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) { header('Location: index.php?tab=course_fees&error=Invalid ID'); exit; }

$course_fee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM course_fees WHERE fee_id = $id"));
if (!$course_fee) { header('Location: index.php?tab=course_fees&error=Not found'); exit; }

$courses = [];
$course_result = mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
if ($course_result) { while ($row = mysqli_fetch_assoc($course_result)) { $courses[] = $row; } }

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $fee_amount = floatval($_POST['fee_amount']);
    $fee_type = $_POST['fee_type'] ?? 'Fixed';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $update_query = "UPDATE course_fees SET course_id=$course_id, fee_amount=$fee_amount, fee_type='$fee_type', is_active=$is_active WHERE fee_id=$id";
    if (mysqli_query($conn, $update_query)) {
        header('Location: index.php?tab=course_fees&msg=Course fee updated!');
        exit;
    } else {
        $error = mysqli_error($conn);
    }
}

$pageTitle = 'Edit Course Fee';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php?tab=course_fees" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Course Fees</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card" style="max-width:560px;">
    <div class="card-header"><h3>Update Course Fee</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="field">
                <label>Course</label>
                <select name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['course_id'] ?>" <?= ($course_fee['course_id'] == $course['course_id']) ? 'selected' : '' ?>><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($courses)): ?>
                    <p class="muted" style="margin-top:4px;font-size:.82rem;">No courses found! <a href="#" style="color:var(--accent);">Add a course first</a></p>
                <?php endif; ?>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Amount</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="number" name="fee_amount" value="<?= $course_fee['fee_amount'] ?>" step="0.01" required style="flex:1;">
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Type</label>
                    <select name="fee_type">
                        <option value="Fixed" <?= ($course_fee['fee_type'] == 'Fixed') ? 'selected' : '' ?>>Fixed</option>
                        <option value="Per Credit Hour" <?= ($course_fee['fee_type'] == 'Per Credit Hour') ? 'selected' : '' ?>>Per Credit Hour</option>
                        <option value="Lab Fee" <?= ($course_fee['fee_type'] == 'Lab Fee') ? 'selected' : '' ?>>Lab Fee</option>
                        <option value="Exam Fee" <?= ($course_fee['fee_type'] == 'Exam Fee') ? 'selected' : '' ?>>Exam Fee</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" <?= ($course_fee['is_active'] == 1) ? 'checked' : '' ?> style="width:auto;">
                    <span>Active</span>
                </label>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php?tab=course_fees" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
