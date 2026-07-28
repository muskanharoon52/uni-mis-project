<?php
$pageTitle = 'Receive Payment';
include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/lms_sync.php';

$error = '';
$success = '';
$search_results = [];
$search_term = '';
$selected_fee = null;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = "SELECT sf.student_fee_id, s.student_id, s.full_name, s.roll_no, sf.remaining_amount
                   FROM student_fee sf
                   JOIN students s ON s.student_id = sf.student_id
                   WHERE sf.remaining_amount > 0 
                   AND (s.full_name LIKE '%$search_term%' OR s.roll_no LIKE '%$search_term%')
                   ORDER BY s.full_name LIMIT 20";
    $search_results = mysqli_query($conn, $search_sql);
}

if (isset($_GET['fee_id']) && !empty($_GET['fee_id'])) {
    $fee_id = mysqli_real_escape_string($conn, $_GET['fee_id']);
    $sel_sql = "SELECT sf.student_fee_id, s.student_id, s.full_name, s.roll_no, sf.remaining_amount
                FROM student_fee sf
                JOIN students s ON s.student_id = sf.student_id
                WHERE sf.student_fee_id = '$fee_id' AND sf.remaining_amount > 0";
    $sel_result = mysqli_query($conn, $sel_sql);
    if (mysqli_num_rows($sel_result) > 0) {
        $selected_fee = mysqli_fetch_assoc($sel_result);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receive_payment'])) {
    $student_fee_id = mysqli_real_escape_string($conn, $_POST['student_fee_id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $amount_paid = mysqli_real_escape_string($conn, $_POST['amount_paid']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_ref = mysqli_real_escape_string($conn, $_POST['transaction_ref']);
    $received_by = $_SESSION['user_id'] ?? 1;

    if (empty($amount_paid) || $amount_paid <= 0) {
        $error = "Please enter a valid amount!";
    } else {
        $rem_sql = "SELECT remaining_amount FROM student_fee WHERE student_fee_id = '$student_fee_id'";
        $rem_result = mysqli_query($conn, $rem_sql);
        $rem_row = mysqli_fetch_assoc($rem_result);
        $remaining = $rem_row['remaining_amount'];

        if ($amount_paid > $remaining) {
            $error = "Amount cannot exceed remaining amount (PKR " . number_format($remaining, 2) . ")";
        } else {
            $insert_sql = "INSERT INTO payments 
                          (student_fee_id, student_id, amount_paid, payment_method, transaction_ref, received_by) 
                          VALUES ('$student_fee_id', '$student_id', '$amount_paid', '$payment_method', '$transaction_ref', '$received_by')";
            
            if (mysqli_query($conn, $insert_sql)) {
                syncPaymentToLMS($conn, intval($student_id), intval($student_fee_id));
                $success = "Payment received successfully!";
                header("refresh:2;url=index.php?msg=Payment received successfully!");
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Payments</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Search Student with Pending Fee</h3>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div style="display:flex;gap:8px;">
            <input type="text" name="search" placeholder="Search by Student Name or Roll No..." value="<?= htmlspecialchars($search_term) ?>" style="flex:1;">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="add.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (isset($_GET['search']) && !empty($_GET['search']) && mysqli_num_rows($search_results) > 0): ?>
        <div class="table-responsive" style="padding:0 22px 18px;">
            <table>
                <thead>
                    <tr><th>Student</th><th>Roll No</th><th style="text-align:right">Remaining</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($search_results)): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td class="muted"><?= htmlspecialchars($row['roll_no'] ?? 'N/A') ?></td>
                        <td style="text-align:right;">PKR <?= number_format($row['remaining_amount'], 2) ?></td>
                        <td><a href="add.php?fee_id=<?= $row['student_fee_id'] ?>" class="btn btn-sm btn-primary">Select</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($selected_fee): ?>
    <div class="alert alert-success">
        <div style="display:flex;justify-content:space-between;align-items:start;">
            <div>
                <strong style="font-size:.95rem;">Selected Student</strong>
                <div style="margin-top:6px;">
                    <span class="muted">Name:</span> <strong><?= htmlspecialchars($selected_fee['full_name']) ?></strong> &nbsp;
                    <span class="muted">Roll No:</span> <?= htmlspecialchars($selected_fee['roll_no'] ?? 'N/A') ?> &nbsp;
                    <span class="muted">Remaining:</span> <strong>PKR <?= number_format($selected_fee['remaining_amount'], 2) ?></strong>
                </div>
            </div>
            <a href="add.php" class="btn btn-ghost btn-sm">Clear Selection</a>
        </div>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header">
            <h3>Payment Details</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="student_fee_id" value="<?= $selected_fee['student_fee_id'] ?>">
            <input type="hidden" name="student_id" value="<?= $selected_fee['student_id'] ?>">
            <div style="padding:22px;">
                <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                    <div class="field" style="margin-bottom:0;">
                        <label>Amount to Pay <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount_paid" placeholder="Enter amount" max="<?= $selected_fee['remaining_amount'] ?>" step="0.01" required>
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
                <div class="actions" style="margin-top:20px;">
                    <button type="submit" name="receive_payment" class="btn btn-primary">Receive Payment</button>
                    <a href="add.php" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (!$selected_fee && !isset($_GET['search'])): ?>
    <div class="alert alert-info">Use the search box above to find a student with pending fee.</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
