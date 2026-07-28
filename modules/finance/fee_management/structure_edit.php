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
if ($id == 0) { header('Location: index.php?tab=structures&error=Invalid ID'); exit; }

$query = "SELECT * FROM fee_structures WHERE fee_structure_id = $id";
$result = mysqli_query($conn, $query);
$structure = mysqli_fetch_assoc($result);
if (!$structure) { header('Location: index.php?tab=structures&error=Not found'); exit; }

$programs = []; $prog_result = mysqli_query($conn, "SELECT program_id, program_name FROM programs WHERE status = 'Active' ORDER BY program_name");
if ($prog_result) { while ($row = mysqli_fetch_assoc($prog_result)) { $programs[] = $row; } }

$sessions = []; $ses_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($ses_result) { while ($row = mysqli_fetch_assoc($ses_result)) { $sessions[] = $row; } }

$semesters = []; $sem_result = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
if ($sem_result) { while ($row = mysqli_fetch_assoc($sem_result)) { $semesters[] = $row; } }

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $structure_name = mysqli_real_escape_string($conn, $_POST['structure_name']);
    $program_id = (int)$_POST['program_id'];
    $session_id = (int)$_POST['session_id'];
    $semester_id = (int)$_POST['semester_id'];
    $total_amount = floatval($_POST['total_amount']);
    $status = $_POST['status'] ?? 'Active';
    
    $update_query = "UPDATE fee_structures SET structure_name='$structure_name', program_id=$program_id, session_id=$session_id, semester_id=$semester_id, total_amount=$total_amount, status='$status' WHERE fee_structure_id=$id";
    if (mysqli_query($conn, $update_query)) {
        header('Location: index.php?tab=structures&msg=Fee structure updated!');
        exit;
    } else {
        $error = mysqli_error($conn);
    }
}

$pageTitle = 'Edit Fee Structure';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php?tab=structures" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Structures</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card" style="max-width:640px;">
    <div class="card-header"><h3>Update Fee Structure</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Structure Name</label>
                    <input type="text" name="structure_name" value="<?= htmlspecialchars($structure['structure_name']) ?>" required>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Total Amount</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="number" name="total_amount" value="<?= $structure['total_amount'] ?>" step="0.01" required style="flex:1;">
                    </div>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Program</label>
                    <select name="program_id" required>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= $program['program_id'] ?>" <?= ($structure['program_id'] == $program['program_id']) ? 'selected' : '' ?>><?= htmlspecialchars($program['program_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Session</label>
                    <select name="session_id" required>
                        <option value="">Select Session</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?= $session['session_id'] ?>" <?= ($structure['session_id'] == $session['session_id']) ? 'selected' : '' ?>><?= htmlspecialchars($session['session_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Semester</label>
                    <select name="semester_id" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?= $semester['semester_id'] ?>" <?= ($structure['semester_id'] == $semester['semester_id']) ? 'selected' : '' ?>><?= htmlspecialchars($semester['semester_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="Active" <?= ($structure['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($structure['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php?tab=structures" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
