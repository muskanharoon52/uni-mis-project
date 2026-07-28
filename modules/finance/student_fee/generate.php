<?php
$pageTitle = 'Generate Student Fee';
include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/lms_sync.php';

if (!isset($_SESSION['user_id'])) { header('Location: /uni-mis-project/modules/sso/login.php'); exit(); }
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) { header('Location: /uni-mis-project/modules/sso/login.php?error=Access denied'); exit(); }

$error = '';
$success = '';
$fee_structure = [];
$student_name = '';
$total_amount = 0;
$generated_fee_id = 0;
$search_results = [];
$search_term = '';
$selected_student = null;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = "SELECT student_id, full_name, roll_no, program_id 
                   FROM students 
                   WHERE status = 'Active' 
                   AND (full_name LIKE '%$search_term%' OR roll_no LIKE '%$search_term%' OR student_id LIKE '%$search_term%')
                   ORDER BY full_name LIMIT 20";
    $search_results = mysqli_query($conn, $search_sql);
}

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['student_id']);
    $sel_sql = "SELECT student_id, full_name, roll_no, program_id 
                FROM students WHERE student_id = '$student_id' AND status = 'Active'";
    $sel_result = mysqli_query($conn, $sel_sql);
    if (mysqli_num_rows($sel_result) > 0) {
        $selected_student = mysqli_fetch_assoc($sel_result);
    }
}

$semester_result = mysqli_query($conn, "SELECT * FROM semesters ORDER BY semester_number");
$session_result = mysqli_query($conn, "SELECT * FROM sessions WHERE status = 'Active' ORDER BY session_name");
$fee_heads_result = mysqli_query($conn, "SELECT fee_head_id, fee_head_name, description FROM fee_heads WHERE status = 'Active' AND deleted_at IS NULL ORDER BY fee_head_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_fee'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $semester_id = mysqli_real_escape_string($conn, $_POST['semester_id']);
    $session_id = mysqli_real_escape_string($conn, $_POST['session_id']);
    $fee_head_id = isset($_POST['fee_head_id']) ? intval($_POST['fee_head_id']) : 0;
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $installment_count = isset($_POST['installment_count']) ? intval($_POST['installment_count']) : 1;
    $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
    $payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : 'Cash';
    $transaction_ref = isset($_POST['transaction_ref']) ? mysqli_real_escape_string($conn, $_POST['transaction_ref']) : '';
    $generated_by = $_SESSION['user_id'] ?? 1;

    $check_result = mysqli_query($conn, "SELECT * FROM student_fee WHERE student_id = '$student_id' AND semester_id = '$semester_id'");
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Fee already generated for this student for this semester!";
    } else {
        $prog_result = mysqli_query($conn, "SELECT program_id FROM students WHERE student_id = '$student_id'");
        $prog_row = mysqli_fetch_assoc($prog_result);
        $program_id = $prog_row['program_id'];

        $fs_result = mysqli_query($conn, "SELECT fee_structure_id, total_amount FROM fee_structures 
                   WHERE program_id = '$program_id' AND session_id = '$session_id' AND semester_id = '$semester_id' AND status = 'Active'");
        
        if (mysqli_num_rows($fs_result) == 0) {
            $error = "No fee structure found for this student's program, session, and semester!";
        } else {
            $fs_row = mysqli_fetch_assoc($fs_result);
            $fee_structure_id = $fs_row['fee_structure_id'];
            $total_amount = $fs_row['total_amount'];

            $fee_head_amount = 0;
            if ($fee_head_id > 0) {
                $head_result = mysqli_query($conn, "SELECT amount FROM fee_structure_details WHERE fee_structure_id = '$fee_structure_id' AND fee_head_id = '$fee_head_id'");
                if (mysqli_num_rows($head_result) > 0) {
                    $head_row = mysqli_fetch_assoc($head_result);
                    $fee_head_amount = $head_row['amount'];
                } else {
                    $error = "Selected fee head not found in this fee structure!";
                }
            }

            if (empty($error)) {
                if ($amount_paid > $fee_head_amount && $fee_head_id > 0) {
                    $error = "Amount paid cannot exceed fee head amount (PKR " . number_format($fee_head_amount, 2) . ")";
                } elseif ($amount_paid < 0) {
                    $error = "Amount paid cannot be negative!";
                } else {
                    $due_date = date('Y-m-d', strtotime('+30 days'));
                    $insert_sql = "INSERT INTO student_fee 
                                  (student_id, semester_id, session_id, fee_structure_id, total_amount, paid_amount, due_date, generated_by) 
                                  VALUES ('$student_id', '$semester_id', '$session_id', '$fee_structure_id', '$total_amount', '$amount_paid', '$due_date', '$generated_by')";
                    
                    if (mysqli_query($conn, $insert_sql)) {
                        $student_fee_id = mysqli_insert_id($conn);
                        syncFeeToLMS($conn, intval($student_id), floatval($total_amount), floatval($amount_paid), $due_date);
                        
                        $detail_result = mysqli_query($conn, "SELECT fee_head_id, amount FROM fee_structure_details WHERE fee_structure_id = '$fee_structure_id'");
                        while ($detail_row = mysqli_fetch_assoc($detail_result)) {
                            mysqli_query($conn, "INSERT INTO student_fee_details (student_fee_id, fee_head_id, amount, discount_amount) VALUES ('$student_fee_id', '{$detail_row['fee_head_id']}', '{$detail_row['amount']}', 0)");
                        }

                        if ($payment_type == 'installments' && $installment_count > 1) {
                            $installment_amount = round($total_amount / $installment_count, 2);
                            $remainder = round($total_amount - ($installment_amount * $installment_count), 2);
                            for ($i = 1; $i <= $installment_count; $i++) {
                                $amount = $installment_amount + ($i == $installment_count ? $remainder : 0);
                                $due = date('Y-m-d', strtotime("+$i months"));
                                mysqli_query($conn, "INSERT INTO installments (student_fee_id, installment_no, amount, due_date, paid_amount, status) VALUES ('$student_fee_id', '$i', '$amount', '$due', 0, 'Pending')");
                            }
                        }

                        if ($amount_paid > 0) {
                            mysqli_query($conn, "INSERT INTO payments (student_fee_id, student_id, amount_paid, payment_method, transaction_ref, received_by) VALUES ('$student_fee_id', '$student_id', '$amount_paid', '$payment_method', '$transaction_ref', '$generated_by')");
                        }

                        $generated_fee_id = $student_fee_id;
                        $success = "Fee generated successfully!";
                        
                        $name_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM students WHERE student_id = '$student_id'"));
                        $student_name = $name_row['full_name'];
                        
                        $fs_detail_result = mysqli_query($conn, "SELECT fh.fee_head_name, fsd.amount FROM fee_structure_details fsd JOIN fee_heads fh ON fh.fee_head_id = fsd.fee_head_id WHERE fsd.fee_structure_id = '$fee_structure_id'");
                        while ($row = mysqli_fetch_assoc($fs_detail_result)) {
                            $fee_structure[] = $row;
                        }
                    } else {
                        $error = "Error: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
}
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Student Fees</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <div style="font-size:.95rem;font-weight:600;margin-bottom:6px;"><?= htmlspecialchars($success) ?></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">
            <div><span class="muted" style="font-size:.82rem;display:block;">Student</span><strong><?= htmlspecialchars($student_name) ?></strong></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Total Amount</span><strong>PKR <?= number_format($total_amount, 2) ?></strong></div>
            <div><span class="muted" style="font-size:.82rem;display:block;">Remaining</span><strong>PKR <?= number_format($total_amount - ($amount_paid ?? 0), 2) ?></strong></div>
        </div>
        <?php if (!empty($fee_structure)): ?>
            <div style="font-size:.82rem;font-weight:600;margin-bottom:4px;">Fee Breakdown:</div>
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($fee_structure as $item): ?>
                    <li><?= htmlspecialchars($item['fee_head_name']) ?>: PKR <?= number_format($item['amount'], 2) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions" style="margin-top:12px;">
            <a href="view.php?id=<?= $generated_fee_id ?>" class="btn btn-primary btn-sm">View Fee Details</a>
            <a href="generate.php" class="btn btn-outline btn-sm">Generate Another</a>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($success)): ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Search Student</h3>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div style="display:flex;gap:8px;">
            <input type="text" name="search" placeholder="Search by Student Name, Roll No, or ID..." value="<?= htmlspecialchars($search_term) ?>" style="flex:1;">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="generate.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </div>
    </form>
    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
        <?php if (mysqli_num_rows($search_results) > 0): ?>
            <div class="table-responsive" style="padding:0 22px 18px;">
                <div style="font-size:.82rem;color:var(--muted);margin-bottom:8px;">Search Results (<?= mysqli_num_rows($search_results) ?> found)</div>
                <table>
                    <thead><tr><th>Student ID</th><th>Name</th><th>Roll No</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($search_results)): ?>
                        <tr>
                            <td><?= $row['student_id'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                            <td><a href="generate.php?student_id=<?= $row['student_id'] ?>" class="btn btn-sm btn-primary">Select</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert" style="margin:0 22px 18px;">No students found matching "<strong><?= htmlspecialchars($search_term) ?></strong>"</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($selected_student): ?>
    <div class="alert alert-success">
        <div style="display:flex;justify-content:space-between;align-items:start;">
            <div>
                <strong>Selected Student</strong>
                <div style="margin-top:4px;">
                    <span class="muted">Name:</span> <strong><?= htmlspecialchars($selected_student['full_name']) ?></strong> &nbsp;
                    <span class="muted">Roll No:</span> <?= htmlspecialchars($selected_student['roll_no'] ?? 'N/A') ?> &nbsp;
                    <span class="muted">ID:</span> <?= $selected_student['student_id'] ?>
                </div>
            </div>
            <a href="generate.php" class="btn btn-ghost btn-sm">Clear Selection</a>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width:720px;">
    <div class="card-header"><h3>Generate Fee</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Student <span style="color:var(--danger);">*</span></label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php
                        $all_students = mysqli_query($conn, "SELECT student_id, full_name, roll_no FROM students WHERE status = 'Active' ORDER BY full_name");
                        while ($row = mysqli_fetch_assoc($all_students)):
                            $selected = ($selected_student && $selected_student['student_id'] == $row['student_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $row['student_id'] ?>" <?= $selected ?>><?= htmlspecialchars($row['full_name'] . ' (' . ($row['roll_no'] ?? 'No Roll') . ')') ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Semester <span style="color:var(--danger);">*</span></label>
                    <select name="semester_id" required>
                        <option value="">Select Semester</option>
                        <?php while ($row = mysqli_fetch_assoc($semester_result)): ?>
                            <option value="<?= $row['semester_id'] ?>"><?= htmlspecialchars($row['semester_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Session <span style="color:var(--danger);">*</span></label>
                    <select name="session_id" required>
                        <option value="">Select Session</option>
                        <?php while ($row = mysqli_fetch_assoc($session_result)): ?>
                            <option value="<?= $row['session_id'] ?>"><?= htmlspecialchars($row['session_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Fee Head <span style="color:var(--danger);">*</span></label>
                    <select name="fee_head_id" required>
                        <option value="">-- Select Fee Head --</option>
                        <?php while ($row = mysqli_fetch_assoc($fee_heads_result)): ?>
                            <option value="<?= $row['fee_head_id'] ?>"><?= htmlspecialchars($row['fee_head_name']) ?><?= $row['description'] ? ' (' . htmlspecialchars($row['description']) . ')' : '' ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Payment Type <span style="color:var(--danger);">*</span></label>
                    <select name="payment_type" id="payment_type" required>
                        <option value="full">Full Payment (One Time)</option>
                        <option value="installments">Installments</option>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;" id="installment_options" class="hidden-field">
                    <label>Number of Installments</label>
                    <select name="installment_count">
                        <option value="2">2 Installments</option>
                        <option value="3" selected>3 Installments</option>
                    </select>
                </div>
            </div>

            <div style="border-top:1px solid var(--border);margin:18px 0;padding-top:18px;">
                <div style="font-size:.9rem;font-weight:600;margin-bottom:14px;">Payment Details</div>
                <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                    <div class="field" style="margin-bottom:0;">
                        <label>Amount Paying Now</label>
                        <input type="number" name="amount_paid" placeholder="0.00" step="0.01" min="0" value="0">
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label>Transaction Reference</label>
                        <input type="text" name="transaction_ref" placeholder="e.g. Txn-12345">
                    </div>
                </div>
            </div>

            <div class="actions" style="margin-top:18px;">
                <button type="submit" name="generate_fee" class="btn btn-primary">Generate Fee</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('payment_type').addEventListener('change', function() {
    var el = document.getElementById('installment_options');
    el.style.display = this.value === 'installments' ? 'block' : 'none';
});
</script>

<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
