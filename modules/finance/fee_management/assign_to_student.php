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
$message = '';
$message_type = '';

$students = [];
$student_result = mysqli_query($conn, "SELECT s.student_id, u.full_name, s.scholarship_percentage 
                                       FROM students s LEFT JOIN users u ON s.user_id = u.user_id ORDER BY u.full_name");
if ($student_result) { while ($row = mysqli_fetch_assoc($student_result)) { $students[] = $row; } }

$structures = [];
$struct_result = mysqli_query($conn, "SELECT fs.*, p.program_name, s.semester_name 
                                       FROM fee_structures fs
                                       LEFT JOIN programs p ON fs.program_id = p.program_id
                                       LEFT JOIN semesters s ON fs.semester_id = s.semester_id
                                       WHERE fs.status = 'Active' ORDER BY fs.created_at DESC");
if ($struct_result) { while ($row = mysqli_fetch_assoc($struct_result)) { $structures[] = $row; } }

$sessions = [];
$session_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($session_result) { while ($row = mysqli_fetch_assoc($session_result)) { $sessions[] = $row; } }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fee_structure_id = (int)$_POST['fee_structure_id'];
    $total_amount = floatval($_POST['total_amount']);
    $semester_id = (int)$_POST['semester_id'];
    $session_id = (int)$_POST['session_id'];
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $user_id = $_SESSION['user_id'];
    
    $scholarship_result = mysqli_query($conn, "SELECT scholarship_percentage FROM students WHERE student_id = '$student_id'");
    $scholarship_percent = 0;
    if ($scholarship_result && mysqli_num_rows($scholarship_result) > 0) {
        $sch_row = mysqli_fetch_assoc($scholarship_result);
        $scholarship_percent = (int)($sch_row['scholarship_percentage'] ?? 0);
    }
    
    $discount_amount = ($total_amount * $scholarship_percent) / 100;
    $net_amount = $total_amount - $discount_amount;
    
    $check_result = mysqli_query($conn, "SELECT student_fee_id FROM student_fee WHERE student_id = '$student_id' AND semester_id = $semester_id");
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $message = "Fee already assigned to this student for this semester!";
        $message_type = 'error';
    } else {
        $query = "INSERT INTO student_fee (student_id, semester_id, session_id, fee_structure_id, total_amount, paid_amount, due_date, status, generated_by) 
                  VALUES ('$student_id', $semester_id, $session_id, $fee_structure_id, $net_amount, 0, '$due_date', 'Unpaid', $user_id)";
        
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "INSERT INTO fee_records (student_id, semester, total_fee, paid_amount, due_date, status) VALUES ('$student_id', $semester_id, $net_amount, 0, '$due_date', 'pending')");
            
            if ($scholarship_percent > 0) {
                mysqli_query($conn, "INSERT INTO scholarships (student_id, scholarship_type, awarding_body, semester_id, discount_kind, discount_value, approved_by, remarks, status) VALUES ('$student_id', 'Applied', 'Fee Assignment', $semester_id, 'Percentage', $scholarship_percent, $user_id, 'Applied on fee - Original: $total_amount, Net: $net_amount', 'Active')");
            }
            
            $message = "Fee assigned successfully! Original: Rs. " . number_format($total_amount, 2) . " | Scholarship: $scholarship_percent% (Rs. " . number_format($discount_amount, 2) . ") | Net: Rs. " . number_format($net_amount, 2);
            $message_type = 'success';
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

$pageTitle = 'Assign Fee to Student';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Management</a>
</div>

<?php if ($message): ?><div class="alert alert-<?= $message_type ?>"><?= $message ?></div><?php endif; ?>

<div class="alert alert-info">
    <strong>Scholarship Info:</strong> <span id="scholarship_display">Select a student to see scholarship</span>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-header"><h3>Assign Fee to Student</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Select Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>" data-scholarship="<?= $student['scholarship_percentage'] ?? 0 ?>">
                                <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                <?php if (($student['scholarship_percentage'] ?? 0) > 0): ?> - <?= $student['scholarship_percentage'] ?>% Scholarship<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Structure</label>
                    <select name="fee_structure_id" id="fee_structure_id" required>
                        <option value="">-- Select Fee Structure --</option>
                        <?php foreach ($structures as $struct): ?>
                            <option value="<?= $struct['fee_structure_id'] ?>" data-amount="<?= $struct['total_amount'] ?>">
                                <?= htmlspecialchars($struct['program_name'] . ' - ' . $struct['semester_name'] . ' (Rs.' . number_format($struct['total_amount'], 0) . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Original Amount</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="number" name="total_amount" id="total_amount" readonly required style="flex:1;background:var(--bg);font-weight:700;">
                    </div>
                    <p class="muted" style="margin-top:4px;font-size:.78rem;">Auto-loaded from fee structure</p>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Net Amount (After Scholarship)</label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.82rem;color:var(--muted);">Rs.</span>
                        <input type="text" id="net_amount" readonly style="flex:1;font-weight:700;color:var(--success);font-size:1.05rem;">
                    </div>
                    <p id="discount_info" class="muted" style="margin-top:4px;font-size:.78rem;"></p>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Due Date</label>
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Semester</label>
                    <select name="semester_id" required>
                        <option value="">-- Select Semester --</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?><?= ($i == 1) ? 'st' : (($i == 2) ? 'nd' : (($i == 3) ? 'rd' : 'th')) ?> Semester</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Session</label>
                    <select name="session_id" required>
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?= $session['session_id'] ?>"><?= htmlspecialchars($session['session_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Assign Fee</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var s = document.getElementById('student_id');
    var f = document.getElementById('fee_structure_id');
    var amt = document.getElementById('total_amount');
    var net = document.getElementById('net_amount');
    var info = document.getElementById('discount_info');
    var disp = document.getElementById('scholarship_display');
    
    function calc() {
        var sch = parseFloat(s.options[s.selectedIndex].getAttribute('data-scholarship')) || 0;
        var t = parseFloat(f.options[f.selectedIndex].getAttribute('data-amount')) || 0;
        amt.value = t.toFixed(2);
        disp.innerHTML = sch > 0 ? '<span class="badge badge-active">' + sch + '% Scholarship Available</span>' : 'No scholarship available for this student';
        var d = (t * sch) / 100;
        var n = t - d;
        if (t > 0) {
            net.value = n.toFixed(2);
            if (sch > 0) { info.innerHTML = sch + '% discount applied (Rs. ' + d.toFixed(2) + ' saved)'; info.style.color = 'var(--success)'; }
            else { info.innerHTML = 'No discount applied'; }
        } else { net.value = ''; info.innerHTML = 'Select a fee structure first'; }
    }
    s.addEventListener('change', calc);
    f.addEventListener('change', calc);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
