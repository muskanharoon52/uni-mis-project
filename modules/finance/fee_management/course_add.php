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
$message = '';
$message_type = '';

$courses = [];
$course_result = mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
if ($course_result) { while ($row = mysqli_fetch_assoc($course_result)) { $courses[] = $row; } }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $fee_amount = floatval($_POST['fee_amount']);
    $fee_type = $_POST['fee_type'] ?? 'Fixed';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $check_result = mysqli_query($conn, "SELECT fee_id FROM course_fees WHERE course_id = $course_id");
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $message = "Fee already exists for this course!";
        $message_type = 'error';
    } else {
        $query = "INSERT INTO course_fees (course_id, fee_amount, fee_type, is_active) VALUES ($course_id, $fee_amount, '$fee_type', $is_active)";
        if (mysqli_query($conn, $query)) {
            header('Location: index.php?tab=course_fees&msg=Course fee added!');
            exit;
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

$pageTitle = 'Add Course Fee';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php?tab=course_fees" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Course Fees</a>
</div>

<?php if ($message): ?><div class="alert alert-<?= $message_type ?>"><?= $message ?></div><?php endif; ?>

<div class="card" style="max-width:560px;">
    <div class="card-header"><h3>Add Course Fee</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="field">
                <label>Select Course</label>
                <select name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Amount</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="number" name="fee_amount" placeholder="0.00" step="0.01" required style="flex:1;">
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Type</label>
                    <select name="fee_type">
                        <option value="Fixed">Fixed</option>
                        <option value="Per Credit Hour">Per Credit Hour</option>
                        <option value="Lab Fee">Lab Fee</option>
                        <option value="Exam Fee">Exam Fee</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" checked style="width:auto;">
                    <span>Active</span>
                </label>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="index.php?tab=course_fees" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
