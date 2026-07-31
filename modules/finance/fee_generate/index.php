<?php
$pageTitle = 'Bulk Fee Generation';
include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/lms_sync.php';

if (!isset($_SESSION['user_id'])) { header('Location: /uni-mis-project/'); exit(); }
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) { header('Location: /uni-mis-project/'); exit(); }

$error = '';
$success = '';
$generated_count = 0;
$skipped_count = 0;
$students = [];
$fee_heads = [];

// Get Dropdowns
$departments = mysqli_query($conn, "SELECT * FROM departments WHERE status = 'Active' ORDER BY department_name");
$sessions = mysqli_query($conn, "SELECT * FROM sessions WHERE status = 'Active' ORDER BY session_name");

$selected_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
$selected_session = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$selected_semester = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

// =============================================
// AUTO-DETECT THE PRICE COLUMN 
// =============================================
$price_column = 'amount'; 
$found_column = false;
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM fee_heads");
if ($col_check) {
    while ($col = mysqli_fetch_assoc($col_check)) {
        $col_name = $col['Field'];
        if (in_array($col_name, ['amount', 'fee_amount', 'price', 'cost', 'charges', 'total'])) {
            $price_column = $col_name;
            $found_column = true;
            break;
        }
    }
}

if (!$found_column) {
    $error = "AUTOMATIC COLUMN DETECTION FAILED. Please check your fee_heads table structure.";
}

// =============================================
// LOAD STUDENTS AND FEE HEADS
// =============================================
if ($selected_dept > 0 && $selected_session > 0 && $found_column) {
    // 1. Fetch Fee Heads
    $sql = "SELECT fee_head_id, fee_head_name, $price_column, description FROM fee_heads WHERE status = 'Active'";
    $fh_result = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($fh_result)) {
        $fee_heads[] = $row;
    }

    // 2. Fetch Students in this Department 
    $prog_result = mysqli_query($conn, "SELECT program_id FROM programs WHERE department_id = '$selected_dept' AND status = 'Active'");
    $program_ids = [];
    while ($p = mysqli_fetch_assoc($prog_result)) { $program_ids[] = $p['program_id']; }

    if (!empty($program_ids)) {
        $prog_list = implode(',', $program_ids);
        
        // =============== UPDATED SQL QUERY ===============
        // Added s.student_id to the SELECT clause
        $stu_result = mysqli_query($conn, "
            SELECT s.student_id, s.full_name, s.roll_no, s.program_id, p.program_name
            FROM students s
            JOIN programs p ON p.program_id = s.program_id
            WHERE s.program_id IN ($prog_list) AND s.status = 'Active'
            ORDER BY s.full_name
        ");
        // =================================================

        while ($row = mysqli_fetch_assoc($stu_result)) {
            $fee_check = mysqli_query($conn, "SELECT student_fee_id FROM student_fee
                WHERE student_id = '{$row['student_id']}' AND session_id = '$selected_session' AND semester_id = '$selected_semester'");
            $row['fee_exists'] = (mysqli_num_rows($fee_check) > 0);
            $students[] = $row;
        }
    }
}

// =============================================
// HANDLE BULK GENERATION SUBMISSION
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_bulk']) && $found_column) {
    $selected_session = intval($_POST['session_id']);
    $selected_semester = intval($_POST['semester_id']);
    $selected_dept = intval($_POST['dept_id']);
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $selected_fee_ids = isset($_POST['fee_head_ids']) ? $_POST['fee_head_ids'] : [];
    $generated_by = $_SESSION['user_id'] ?? 1;
    $due_date = date('Y-m-d', strtotime('+30 days'));

    // 1. Calculate Total
    $fee_total = 0;
    $fee_head_details = [];
    if (!empty($selected_fee_ids)) {
        $id_list = implode(',', array_map('intval', $selected_fee_ids));
        $sql_details = "SELECT fee_head_id, fee_head_name, $price_column FROM fee_heads WHERE fee_head_id IN ($id_list) AND status = 'active'";
        $detail_res = mysqli_query($conn, $sql_details);
        while($row = mysqli_fetch_assoc($detail_res)) {
            $fee_total += $row[$price_column];
            $fee_head_details[] = $row; 
        }
    }

    if ($fee_total == 0) {
        $error = "Please select at least one fee head to generate.";
    } elseif (empty($student_ids)) {
        $error = "No students selected.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            foreach ($student_ids as $sid) {
                $sid = intval($sid);
                $check = mysqli_query($conn, "SELECT student_fee_id FROM student_fee WHERE student_id = '$sid' AND session_id = '$selected_session' AND semester_id = '$selected_semester'");
                if (mysqli_num_rows($check) > 0) {
                    $skipped_count++;
                    continue;
                }

                $insert = "INSERT INTO student_fee (student_id, semester_id, session_id, total_amount, paid_amount, due_date, generated_by)
                    VALUES ('$sid', '$selected_semester', '$selected_session', '$fee_total', 0, '$due_date', '$generated_by')";
                
                if (mysqli_query($conn, $insert)) {
                    $student_fee_id = mysqli_insert_id($conn);
                    syncFeeToLMS($conn, $sid, floatval($fee_total), 0, $due_date);

                    foreach ($fee_head_details as $head) {
                        mysqli_query($conn, "INSERT INTO student_fee_details (student_fee_id, fee_head_id, amount, discount_amount)
                            VALUES ('$student_fee_id', '{$head['fee_head_id']}', '{$head[$price_column]}', 0)");
                    }
                    $generated_count++;
                }
            }
            mysqli_commit($conn);
            $success = "Fee generated for $generated_count student(s).";
            if ($skipped_count > 0) $success .= " $skipped_count student(s) skipped (fee already exists).";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }

    $selected_dept = 0;
    $selected_session = 0;
    $selected_semester = 0;
    $students = [];
    $fee_heads = [];
}
?>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Bulk Fee Generation</h3>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="field" style="margin-bottom:0;">
                <label>Department <span style="color:var(--danger);">*</span></label>
                <select name="dept_id" required onchange="this.form.submit()">
                    <option value="">Select Department</option>
                    <?php while ($d = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?= $d['department_id'] ?>" <?= $selected_dept == $d['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Session <span style="color:var(--danger);">*</span></label>
                <select name="session_id" required onchange="this.form.submit()">
                    <option value="">Select Session</option>
                    <?php while ($s = mysqli_fetch_assoc($sessions)): ?>
                        <option value="<?= $s['session_id'] ?>" <?= $selected_session == $s['session_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['session_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Semester <span style="color:var(--danger);">*</span></label>
                <select name="semester_id" required onchange="this.form.submit()">
                    <option value="">Select Semester</option>
                    <?php
                    $semesters = mysqli_query($conn, "SELECT * FROM semesters GROUP BY semester_name ORDER BY CAST(SUBSTRING_INDEX(semester_name, ' ', -1) AS UNSIGNED)");
                    while ($sem = mysqli_fetch_assoc($semesters)):
                    ?>
                        <option value="<?= $sem['semester_id'] ?>" <?= $selected_semester == $sem['semester_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sem['semester_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <?php if ($selected_dept > 0 || $selected_session > 0 || $selected_semester > 0): ?>
            <div style="margin-top:12px;">
                <a href="generate.php" class="btn btn-ghost btn-sm">Clear Filters</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if (!empty($students) && $found_column): ?>
<form method="POST">
    <input type="hidden" name="dept_id" value="<?= $selected_dept ?>">
    <input type="hidden" name="session_id" value="<?= $selected_session ?>">
    <input type="hidden" name="semester_id" value="<?= $selected_semester ?>">
    
    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <h4 style="margin:0;">Select Fee Heads to Apply</h4>
        </div>
        <div class="card-content" style="padding:16px 20px;">
            <?php if (empty($fee_heads)): ?>
                <p class="muted">No fee heads found. Please create fee heads in the Fee Heads module first.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px;">
                    <?php foreach ($fee_heads as $fh): ?>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;background:#fafafa;">
                        <input type="checkbox" name="fee_head_ids[]" value="<?= $fh['fee_head_id'] ?>" checked>
                        <div>
                            <div style="font-weight:500;font-size:.9rem;"><?= htmlspecialchars($fh['fee_head_name']) ?></div>
                            <div style="font-size:.8rem;color:#2563eb;font-weight:600;">PKR <?= number_format($fh[$price_column] ?? 0, 2) ?></div>
                            <div style="font-size:.7rem;color:#6b7280;"><?= htmlspecialchars($fh['description'] ?? '') ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:12px;font-size:.85rem;color:#6b7280;">
                    <i class="fas fa-info-circle"></i> Uncheck any fee head you do NOT want to apply to these students.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h3>Students (<?= count($students) ?> found)</h3>
                <?php if (!empty($fee_heads)): ?>
                    <span style="font-size:.9rem;font-weight:600;">Total per student: <span id="dynamic_total">PKR 0</span></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (empty($fee_heads)): ?>
            <div class="alert" style="margin:16px;">No active fee heads found. Please create fee heads first.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="select_all" checked></th>
                        <!-- ========= NEW COLUMN ADDED ========= -->
                        <th>Student ID</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Program</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                    <tr>
                        <td><input type="checkbox" name="student_ids[]" value="<?= $stu['student_id'] ?>" class="student-check" <?= $stu['fee_exists'] ? 'disabled' : 'checked' ?>></td>
                        
                        <!-- ========= NEW DATA ADDED ========= -->
                        <td style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($stu['student_id'] ?? 'N/A') ?></td>
                        
                        <td style="font-weight:600;"><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td class="muted"><?= htmlspecialchars($stu['roll_no'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($stu['program_name']) ?></td>
                        <td>
                            <?php if ($stu['fee_exists']): ?>
                                <span class="badge badge-active">Already Generated</span>
                            <?php else: ?>
                                <span class="badge badge-outline">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:16px 22px;border-top:1px solid var(--border);">
            <button type="submit" name="generate_bulk" class="btn btn-primary" onclick="return confirm('Generate fee for all selected students with the selected fee heads?')">
                <i class="fas fa-bolt"></i> Generate Fee for Selected
            </button>
            <span class="muted" style="margin-left:12px;font-size:.82rem;">
                <span id="selected_count"><?= count($students) ?></span> student(s) selected
            </span>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
// Toggle Select All for Students
document.getElementById('select_all').addEventListener('change', function() {
    document.querySelectorAll('.student-check:not(:disabled)').forEach(cb => cb.checked = this.checked);
    updateCount();
});
document.querySelectorAll('.student-check').forEach(cb => cb.addEventListener('change', updateCount));
function updateCount() {
    var count = document.querySelectorAll('.student-check:checked').length;
    document.getElementById('selected_count').textContent = count;
}

// Dynamic Fee Total Calculation based on checked Fee Heads
document.querySelectorAll('input[name="fee_head_ids[]"]').forEach(function(cb) {
    cb.addEventListener('change', calculateTotal);
});
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('input[name="fee_head_ids[]"]:checked').forEach(function(cb) {
        let parent = cb.closest('label');
        let amountText = parent.querySelector('div:last-child div:nth-child(2)').innerText;
        let amount = parseFloat(amountText.replace(/[^0-9.]/g, ''));
        total += amount;
    });
    document.getElementById('dynamic_total').innerText = 'PKR ' + total.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
calculateTotal();
</script>
<?php elseif ($selected_dept > 0 || $selected_session > 0 || $selected_semester > 0): ?>
<div class="card" style="margin-top:18px;">
    <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
        <p>No students found for the selected filters.</p>
    </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>