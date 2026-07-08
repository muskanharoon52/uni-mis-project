<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
// Check if user is Finance Officer (role_id = 3)
if ($_SESSION['role_id'] != 3) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}
// ... baaki code
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

$error = '';
$success = '';
$search_results = [];
$search_term = '';
$selected_fee = null;

// --- SEARCH LOGIC ---
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = "SELECT 
                   sf.student_fee_id,
                   s.student_id,
                   s.full_name,
                   s.roll_no,
                   sf.remaining_amount
                   FROM student_fee sf
                   JOIN students s ON s.student_id = sf.student_id
                   WHERE sf.remaining_amount > 0 
                   AND (s.full_name LIKE '%$search_term%' 
                        OR s.roll_no LIKE '%$search_term%')
                   ORDER BY s.full_name LIMIT 20";
    $search_results = mysqli_query($conn, $search_sql);
}

// --- SELECTED FEE RECORD ---
if (isset($_GET['fee_id']) && !empty($_GET['fee_id'])) {
    $fee_id = mysqli_real_escape_string($conn, $_GET['fee_id']);
    $sel_sql = "SELECT 
                sf.student_fee_id,
                s.student_id,
                s.full_name,
                s.roll_no,
                sf.remaining_amount
                FROM student_fee sf
                JOIN students s ON s.student_id = sf.student_id
                WHERE sf.student_fee_id = '$fee_id' AND sf.remaining_amount > 0";
    $sel_result = mysqli_query($conn, $sel_sql);
    if (mysqli_num_rows($sel_result) > 0) {
        $selected_fee = mysqli_fetch_assoc($sel_result);
    }
}

// --- FORM SUBMISSION ---
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
                $success = "Payment received successfully!";
                header("refresh:2;url=index.php?msg=Payment received successfully!");
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-money-bill-wave"></i> Receive Payment</h4>
                </div>
                <div class="card-body">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="card mb-4 bg-light">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-search"></i> Search Student with Pending Fee
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by Student Name or Roll No..." value="<?php echo htmlspecialchars($search_term); ?>">
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                                </div>
                            </form>
                            <?php if(isset($_GET['search']) && !empty($_GET['search']) && mysqli_num_rows($search_results) > 0): ?>
                                <div class="mt-3">
                                    <table class="table table-sm table-hover">
                                        <thead><tr><th>Student</th><th>Roll No</th><th class="text-end">Remaining</th><th>Action</th></tr></thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></td>
                                                <td class="text-end">PKR <?php echo number_format($row['remaining_amount'], 2); ?></td>
                                                <td><a href="add.php?fee_id=<?php echo $row['student_fee_id']; ?>" class="btn btn-sm btn-success">Select</a></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($selected_fee): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-user-check"></i> Selected Student: <?php echo htmlspecialchars($selected_fee['full_name']); ?></h5>
                            <p>Roll No: <?php echo htmlspecialchars($selected_fee['roll_no'] ?? 'N/A'); ?><br>
                            Remaining: <strong>PKR <?php echo number_format($selected_fee['remaining_amount'], 2); ?></strong></p>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="student_fee_id" value="<?php echo $selected_fee['student_fee_id']; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $selected_fee['student_id']; ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Amount to Pay</label>
                                    <input type="number" class="form-control" name="amount_paid" placeholder="Enter amount" max="<?php echo $selected_fee['remaining_amount']; ?>" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank">Bank Transfer</option>
                                        <option value="Card">Card</option>
                                        <option value="Online">Online</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Transaction Reference</label>
                                    <input type="text" class="form-control" name="transaction_ref" placeholder="e.g. Txn-12345">
                                </div>
                            </div>
                            <button type="submit" name="receive_payment" class="btn btn-success">Receive Payment</button>
                            <a href="add.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>