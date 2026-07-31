<?php
$pageTitle = 'Fee Receive';
include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/lms_sync.php';

if (!isset($_SESSION['user_id'])) { header('Location: /uni-mis-project/'); exit(); }
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) { header('Location: /uni-mis-project/'); exit(); }

$error = '';
$success = '';
$student = null;
$fee_records = [];
$search_term = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receive_payment'])) {
    $student_fee_id = intval($_POST['student_fee_id']);
    $student_id = intval($_POST['student_id']);
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_ref = mysqli_real_escape_string($conn, $_POST['transaction_ref']);
    $received_by = $_SESSION['user_id'] ?? 1;

    if ($amount_paid <= 0) {
        $error = "Amount must be greater than zero.";
    } else {
        $fee_result = mysqli_query($conn, "SELECT * FROM student_fee WHERE student_fee_id = '$student_fee_id' AND student_id = '$student_id'");
        if (mysqli_num_rows($fee_result) == 0) {
            $error = "Fee record not found.";
        } else {
            $fee_row = mysqli_fetch_assoc($fee_result);
            $remaining = $fee_row['total_amount'] - $fee_row['paid_amount'];

            if ($amount_paid > $remaining) {
                $error = "Amount exceeds remaining balance (PKR " . number_format($remaining, 2) . ").";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $new_paid = $fee_row['paid_amount'] + $amount_paid;
                    $new_status = ($new_paid >= $fee_row['total_amount']) ? 'Paid' : 'Partially Paid';

                    mysqli_query($conn, "UPDATE student_fee SET paid_amount = '$new_paid', status = '$new_status' WHERE student_fee_id = '$student_fee_id'");

                    mysqli_query($conn, "INSERT INTO payments (student_fee_id, student_id, amount_paid, payment_method, transaction_ref, received_by)
                        VALUES ('$student_fee_id', '$student_id', '$amount_paid', '$payment_method', '$transaction_ref', '$received_by')");

                    syncPaymentToLMS($conn, $student_id, $student_fee_id);

                    mysqli_commit($conn);
                    $success = "Payment of PKR " . number_format($amount_paid, 2) . " received successfully.";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// =============================================
// FIXED SEARCH LOGIC (TRIMS SPACES & SEARCHES UPPERCASE)
// =============================================
if (isset($_GET['search']) && !empty($_GET['search'])) {
    // CRITICAL FIX: trim() removes the accidental spaces before and after!
    $search_term = trim(mysqli_real_escape_string($conn, $_GET['search']));
    
    // FIX: Search the exact table with Case-Insensitive (UPPER) matching
    $student_query = "
        SELECT s.id, s.student_id, s.student_name as full_name, s.father_name, d.department_name
        FROM admission_students s
        LEFT JOIN departments d ON s.program_id = d.department_id
        WHERE s.status = 'active'
        AND (UPPER(s.student_name) LIKE UPPER('%$search_term%') 
             OR UPPER(s.student_id) LIKE UPPER('%$search_term%') 
             OR UPPER(s.father_name) LIKE UPPER('%$search_term%'))
        ORDER BY s.student_name LIMIT 20
    ";
    
    $student_result = mysqli_query($conn, $student_query);
    
    if ($student_result && mysqli_num_rows($student_result) > 0) {
        $student = mysqli_fetch_assoc($student_result); // Takes the first exact match
    }
}

// If no search result, check if a student_id was passed directly via URL
if (!$student && isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $sid = mysqli_real_escape_string($conn, $_GET['student_id']);
    $student_query = "
        SELECT s.id, s.student_id, s.student_name as full_name, s.father_name, d.department_name
        FROM admission_students s
        LEFT JOIN departments d ON s.program_id = d.department_id
        WHERE s.student_id = '$sid' AND s.status = 'active'
    ";
    $student_result = mysqli_query($conn, $student_query);
    if ($student_result && mysqli_num_rows($student_result) > 0) {
        $student = mysqli_fetch_assoc($student_result);
    }
}

// Fetch Fee Records if student is found
if ($student) {
    // We use $student['id'] (the internal AUTO_INCREMENT ID) because student_fee links to that
    $fee_result = mysqli_query($conn, "
        SELECT sf.*, ss.session_name, sem.semester_name
        FROM student_fee sf
        JOIN sessions ss ON ss.session_id = sf.session_id
        JOIN semesters sem ON sem.semester_id = sf.semester_id
        WHERE sf.student_id = '{$student['id']}'
        ORDER BY sf.generated_at DESC
    ");
    while ($row = mysqli_fetch_assoc($fee_result)) {
        $fee_records[] = $row;
    }
}
// =============================================
?>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Search Student</h3>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div style="display:flex;gap:8px;">
            <input type="text" name="search" placeholder="Search by Student ID, Name, or Father Name..." value="<?= htmlspecialchars($search_term) ?>" style="flex:1;">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="index.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($student): ?>
    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <h3>Student Information</h3>
        </div>
        <div style="padding:18px 22px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div><span class="muted" style="font-size:.82rem;">Student ID</span><br><strong><?= htmlspecialchars($student['student_id']) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;">Name</span><br><strong><?= htmlspecialchars($student['full_name']) ?></strong></div>
                <div><span class="muted" style="font-size:.82rem;">Department</span><br><strong><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></strong></div>
            </div>
        </div>
    </div>

    <?php if (empty($fee_records)): ?>
        <div class="card" style="margin-top:18px;">
            <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
                <p>No fee records found for this student. <a href="/uni-mis-project/modules/finance/fee_generate/" style="font-weight:600;">Generate a fee first.</a></p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($fee_records as $fee): ?>
            <?php $remaining = $fee['total_amount'] - $fee['paid_amount']; ?>
            <div class="card" style="margin-top:18px;">
                <div class="card-header">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <h3><?= htmlspecialchars($fee['semester_name']) ?> - <?= htmlspecialchars($fee['session_name']) ?></h3>
                        <span class="badge <?= $remaining <= 0 ? 'badge-active' : ($fee['paid_amount'] > 0 ? 'badge-outline' : 'badge-inactive') ?>">
                            <?= $remaining <= 0 ? 'Paid' : ($fee['paid_amount'] > 0 ? 'Partially Paid' : 'Unpaid') ?>
                        </span>
                    </div>
                </div>
                <div style="padding:18px 22px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;">
                        <div>
                            <span class="muted" style="font-size:.82rem;">Total Amount</span><br>
                            <strong style="font-size:1.1rem;">PKR <?= number_format($fee['total_amount'], 2) ?></strong>
                        </div>
                        <div>
                            <span class="muted" style="font-size:.82rem;">Paid Amount</span><br>
                            <strong style="font-size:1.1rem;color:#10b981;">PKR <?= number_format($fee['paid_amount'], 2) ?></strong>
                        </div>
                        <div>
                            <span class="muted" style="font-size:.82rem;">Remaining</span><br>
                            <strong style="font-size:1.1rem;color:#ef4444;">PKR <?= number_format($remaining, 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($remaining > 0): ?>
                        <div style="border-top:1px solid var(--border);padding-top:16px;">
                            <h4 style="font-size:.9rem;margin-bottom:12px;">Receive Payment</h4>
                            <form method="POST">
                                <input type="hidden" name="student_fee_id" value="<?= $fee['student_fee_id'] ?>">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>"> <!-- Uses internal ID -->
                                <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                                    <div class="field" style="margin-bottom:0;">
                                        <label>Amount <span style="color:var(--danger);">*</span></label>
                                        <input type="number" name="amount_paid" placeholder="0.00" step="0.01" min="0.01" max="<?= $remaining ?>" required>
                                    </div>
                                    <div class="field" style="margin-bottom:0;">
                                        <label>Payment Method <span style="color:var(--danger);">*</span></label>
                                        <select name="payment_method" required>
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
                                <div class="actions" style="margin-top:14px;">
                                    <button type="submit" name="receive_payment" class="btn btn-primary">Receive Payment</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php elseif (isset($_GET['search']) && !empty($_GET['search'])): ?>
    <div class="card" style="margin-top:18px;">
        <div class="alert" style="margin:16px;">No active student found matching "<strong><?= htmlspecialchars($search_term) ?></strong>"</div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>