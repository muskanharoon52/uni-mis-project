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

$programs = [];
$prog_result = mysqli_query($conn, "SELECT program_id, program_name FROM programs WHERE status = 'Active' ORDER BY program_name");
if ($prog_result) { while ($row = mysqli_fetch_assoc($prog_result)) { $programs[] = $row; } }

$sessions = [];
$ses_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($ses_result) { while ($row = mysqli_fetch_assoc($ses_result)) { $sessions[] = $row; } }

$semesters = [];
$sem_result = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
if ($sem_result) { while ($row = mysqli_fetch_assoc($sem_result)) { $semesters[] = $row; } }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $structure_name = mysqli_real_escape_string($conn, $_POST['structure_name']);
    $is_universal = isset($_POST['is_universal']) ? 1 : 0;
    
    if ($is_universal == 1) {
        $program_id = 'NULL'; $session_id = 'NULL'; $semester_id = 'NULL';
    } else {
        $program_id = isset($_POST['program_id']) && !empty($_POST['program_id']) ? (int)$_POST['program_id'] : 'NULL';
        $session_id = isset($_POST['session_id']) && !empty($_POST['session_id']) ? (int)$_POST['session_id'] : 'NULL';
        $semester_id = isset($_POST['semester_id']) && !empty($_POST['semester_id']) ? (int)$_POST['semester_id'] : 'NULL';
    }
    
    $total_amount = floatval($_POST['total_amount']);
    $status = $_POST['status'] ?? 'Active';
    $fee_type = $_POST['fee_type'] ?? 'General';
    
    $error = false;
    if ($is_universal == 0) {
        $check_result = mysqli_query($conn, "SELECT fee_structure_id FROM fee_structures WHERE program_id = $program_id AND session_id = $session_id AND semester_id = $semester_id");
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Fee structure already exists for this Program, Session, and Semester!";
            $message_type = 'error';
            $error = true;
        }
    }
    
    if (!$error) {
        $query = "INSERT INTO fee_structures (structure_name, program_id, session_id, semester_id, total_amount, status, fee_type, is_universal) 
                  VALUES ('$structure_name', $program_id, $session_id, $semester_id, $total_amount, '$status', '$fee_type', $is_universal)";
        if (mysqli_query($conn, $query)) {
            header('Location: index.php?tab=structures&msg=Fee structure created!');
            exit;
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

$pageTitle = 'Add Fee Structure';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php?tab=structures" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Structures</a>
</div>

<?php if ($message): ?><div class="alert alert-<?= $message_type ?>"><?= $message ?></div><?php endif; ?>

<div class="card" style="max-width:640px;">
    <div class="card-header"><h3>Create New Fee Structure</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Structure Name</label>
                    <input type="text" name="structure_name" placeholder="e.g., BS CS Fall 2024 Fee" required>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Type</label>
                    <select name="fee_type">
                        <option value="General">General</option>
                        <option value="Tuition">Tuition</option>
                        <option value="Lab">Lab</option>
                        <option value="Sports">Sports</option>
                        <option value="Library">Library</option>
                        <option value="Exam">Exam</option>
                        <option value="Registration">Registration</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_universal" id="is_universal" value="1" style="width:auto;">
                    <span><strong>Universal Fee</strong> <span class="muted">(Apply to all programs/semesters)</span></span>
                </label>
            </div>

            <div id="program_field" class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Program</label>
                    <select name="program_id">
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= $program['program_id'] ?>"><?= htmlspecialchars($program['program_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Session</label>
                    <select name="session_id">
                        <option value="">Select Session</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?= $session['session_id'] ?>"><?= htmlspecialchars($session['session_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Semester</label>
                    <select name="semester_id">
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?= $semester['semester_id'] ?>"><?= htmlspecialchars($semester['semester_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Total Amount</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="number" name="total_amount" placeholder="0.00" step="0.01" required style="flex:1;">
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="index.php?tab=structures" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('is_universal');
    var pf = document.getElementById('program_field');
    function toggle() {
        pf.style.display = cb.checked ? 'none' : 'grid';
        pf.querySelectorAll('select').forEach(function(el) { cb.checked ? el.removeAttribute('required') : el.setAttribute('required',''); });
    }
    cb.addEventListener('change', toggle);
    toggle();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
