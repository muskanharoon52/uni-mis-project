<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';

$error = '';
$success = '';
$fee_structure = [];
$student_name = '';
$total_amount = 0;
$generated_fee_id = 0;
$search_results = [];
$search_term = '';
$selected_student = null;

// --- SEARCH LOGIC ---
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = "SELECT student_id, full_name, roll_no, program_id 
                   FROM students 
                   WHERE status = 'Active' 
                   AND (full_name LIKE '%$search_term%' 
                        OR roll_no LIKE '%$search_term%' 
                        OR student_id LIKE '%$search_term%')
                   ORDER BY full_name LIMIT 20";
    $search_results = mysqli_query($conn, $search_sql);
}

// --- SELECTED STUDENT ---
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['student_id']);
    $sel_sql = "SELECT student_id, full_name, roll_no, program_id 
                FROM students WHERE student_id = '$student_id' AND status = 'Active'";
    $sel_result = mysqli_query($conn, $sel_sql);
    if (mysqli_num_rows($sel_result) > 0) {
        $selected_student = mysqli_fetch_assoc($sel_result);
    }
}

// Fetch semesters and sessions
$semester_sql = "SELECT * FROM semesters ORDER BY semester_number";
$semester_result = mysqli_query($conn, $semester_sql);

$session_sql = "SELECT * FROM sessions WHERE status = 'Active' ORDER BY session_name";
$session_result = mysqli_query($conn, $session_sql);

// Fetch fee heads for dropdown
$fee_heads_sql = "SELECT fee_head_id, fee_head_name, description FROM fee_heads WHERE status = 'Active' AND deleted_at IS NULL ORDER BY fee_head_name";
$fee_heads_result = mysqli_query($conn, $fee_heads_sql);

// --- GENERATE FEE + PAYMENT LOGIC ---
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

    // Check if fee already exists
    $check_sql = "SELECT * FROM student_fee WHERE student_id = '$student_id' AND semester_id = '$semester_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Fee already generated for this student for this semester!";
    } else {
        // Get student's program
        $prog_sql = "SELECT program_id FROM students WHERE student_id = '$student_id'";
        $prog_result = mysqli_query($conn, $prog_sql);
        $prog_row = mysqli_fetch_assoc($prog_result);
        $program_id = $prog_row['program_id'];

        // Get fee structure
        $fs_sql = "SELECT fee_structure_id, total_amount 
                   FROM fee_structures 
                   WHERE program_id = '$program_id' 
                   AND session_id = '$session_id' 
                   AND semester_id = '$semester_id' 
                   AND status = 'Active'";
        $fs_result = mysqli_query($conn, $fs_sql);
        
        if (mysqli_num_rows($fs_result) == 0) {
            $error = "No fee structure found for this student's program, session, and semester!";
        } else {
            $fs_row = mysqli_fetch_assoc($fs_result);
            $fee_structure_id = $fs_row['fee_structure_id'];
            $total_amount = $fs_row['total_amount'];

            // Get fee head amount
            $fee_head_amount = 0;
            if ($fee_head_id > 0) {
                $head_sql = "SELECT amount FROM fee_structure_details WHERE fee_structure_id = '$fee_structure_id' AND fee_head_id = '$fee_head_id'";
                $head_result = mysqli_query($conn, $head_sql);
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
                                  (student_id, semester_id, session_id, fee_structure_id, 
                                   total_amount, paid_amount, due_date, generated_by) 
                                  VALUES ('$student_id', '$semester_id', '$session_id', 
                                          '$fee_structure_id', '$total_amount', '$amount_paid', '$due_date', '$generated_by')";
                    
                    if (mysqli_query($conn, $insert_sql)) {
                        $student_fee_id = mysqli_insert_id($conn);
                        
                        $detail_sql = "SELECT fee_head_id, amount FROM fee_structure_details 
                                       WHERE fee_structure_id = '$fee_structure_id'";
                        $detail_result = mysqli_query($conn, $detail_sql);
                        
                        while ($detail_row = mysqli_fetch_assoc($detail_result)) {
                            $sfd_sql = "INSERT INTO student_fee_details 
                                       (student_fee_id, fee_head_id, amount, discount_amount) 
                                       VALUES ('$student_fee_id', '{$detail_row['fee_head_id']}', '{$detail_row['amount']}', 0)";
                            mysqli_query($conn, $sfd_sql);
                        }

                        if ($payment_type == 'installments' && $installment_count > 1) {
                            $installment_amount = round($total_amount / $installment_count, 2);
                            $remainder = round($total_amount - ($installment_amount * $installment_count), 2);
                            
                            for ($i = 1; $i <= $installment_count; $i++) {
                                $amount = $installment_amount;
                                if ($i == $installment_count) {
                                    $amount += $remainder;
                                }
                                $due_date_installment = date('Y-m-d', strtotime("+$i months"));
                                
                                $inst_sql = "INSERT INTO installments 
                                            (student_fee_id, installment_no, amount, due_date, paid_amount, status) 
                                            VALUES ('$student_fee_id', '$i', '$amount', '$due_date_installment', 0, 'Pending')";
                                mysqli_query($conn, $inst_sql);
                            }
                        }

                        if ($amount_paid > 0) {
                            $payment_sql = "INSERT INTO payments 
                                           (student_fee_id, student_id, amount_paid, payment_method, transaction_ref, received_by) 
                                           VALUES ('$student_fee_id', '$student_id', '$amount_paid', '$payment_method', '$transaction_ref', '$generated_by')";
                            mysqli_query($conn, $payment_sql);
                        }

                        $generated_fee_id = $student_fee_id;
                        $success = "Fee generated successfully!";
                        
                        $name_sql = "SELECT full_name FROM students WHERE student_id = '$student_id'";
                        $name_result = mysqli_query($conn, $name_sql);
                        $name_row = mysqli_fetch_assoc($name_result);
                        $student_name = $name_row['full_name'];
                        
                        $fs_detail_sql = "SELECT fh.fee_head_name, fsd.amount 
                                          FROM fee_structure_details fsd
                                          JOIN fee_heads fh ON fh.fee_head_id = fsd.fee_head_id
                                          WHERE fsd.fee_structure_id = '$fee_structure_id'";
                        $fs_detail_result = mysqli_query($conn, $fs_detail_sql);
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-file-invoice text-success"></i> Generate Student Fee</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
</div>

<?php if(!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if(!empty($success)): ?>
    <div class="alert alert-success">
        <h5><i class="fas fa-check-circle"></i> <?php echo $success; ?></h5>
        <p>Student: <strong><?php echo htmlspecialchars($student_name); ?></strong></p>
        <p>Total Amount: <strong>PKR <?php echo number_format($total_amount, 2); ?></strong></p>
        <p>Amount Paid: <strong>PKR <?php echo number_format($amount_paid ?? 0, 2); ?></strong></p>
        <p>Remaining: <strong>PKR <?php echo number_format($total_amount - ($amount_paid ?? 0), 2); ?></strong></p>
        <hr>
        <h6>Fee Breakdown:</h6>
        <ul>
            <?php foreach($fee_structure as $item): ?>
                <li><?php echo htmlspecialchars($item['fee_head_name']); ?>: PKR <?php echo number_format($item['amount'], 2); ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="view.php?id=<?php echo $generated_fee_id; ?>" class="btn btn-primary">View Fee Details</a>
        <a href="index.php" class="btn btn-secondary">View All Fees</a>
        <a href="generate.php" class="btn btn-success">Generate Another Fee</a>
    </div>
<?php endif; ?>

<?php if(empty($success)): ?>

<!-- SEARCH SECTION -->
<div class="card mb-4 bg-light">
    <div class="card-header bg-info text-white">
        <i class="fas fa-search"></i> Search Student
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by Student Name, Roll No, or Student ID..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="generate.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
            <?php if(mysqli_num_rows($search_results) > 0): ?>
                <div class="mt-3">
                    <h6>Search Results (<?php echo mysqli_num_rows($search_results); ?> found)</h6>
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr><th>Student ID</th><th>Name</th><th>Roll No</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                            <tr>
                                <td><?php echo $row['student_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="generate.php?student_id=<?php echo $row['student_id']; ?>" 
                                       class="btn btn-sm btn-success">Select</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">
                    No students found matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- SELECTED STUDENT -->
<?php if($selected_student): ?>
    <div class="alert alert-success">
        <h5><i class="fas fa-user-check"></i> Selected Student</h5>
        <p>
            <strong>Name:</strong> <?php echo htmlspecialchars($selected_student['full_name']); ?><br>
            <strong>Roll No:</strong> <?php echo htmlspecialchars($selected_student['roll_no'] ?? 'N/A'); ?><br>
            <strong>Student ID:</strong> <?php echo $selected_student['student_id']; ?>
        </p>
        <a href="generate.php" class="btn btn-light btn-sm">Clear Selection</a>
    </div>
<?php endif; ?>

<!-- FEE GENERATION FORM -->
<form method="POST" action="">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Student <span class="text-danger">*</span></label>
            <select class="form-select" name="student_id" required>
                <option value="">-- Select Student --</option>
                <?php
                $all_students = mysqli_query($conn, "SELECT student_id, full_name, roll_no FROM students WHERE status = 'Active' ORDER BY full_name");
                while($row = mysqli_fetch_assoc($all_students)):
                    $selected = ($selected_student && $selected_student['student_id'] == $row['student_id']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $row['student_id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($row['full_name'] . ' (' . ($row['roll_no'] ?? 'No Roll') . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Semester <span class="text-danger">*</span></label>
            <select class="form-select" name="semester_id" required>
                <option value="">Select Semester</option>
                <?php while($row = mysqli_fetch_assoc($semester_result)): ?>
                    <option value="<?php echo $row['semester_id']; ?>">
                        <?php echo htmlspecialchars($row['semester_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Session <span class="text-danger">*</span></label>
            <select class="form-select" name="session_id" required>
                <option value="">Select Session</option>
                <?php while($row = mysqli_fetch_assoc($session_result)): ?>
                    <option value="<?php echo $row['session_id']; ?>">
                        <?php echo htmlspecialchars($row['session_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Select Fee Head <span class="text-danger">*</span></label>
            <select class="form-select" name="fee_head_id" required>
                <option value="">-- Select Fee Head --</option>
                <?php while($row = mysqli_fetch_assoc($fee_heads_result)): ?>
                    <option value="<?php echo $row['fee_head_id']; ?>">
                        <?php echo htmlspecialchars($row['fee_head_name']); ?>
                        <?php if($row['description']): ?>
                            (<?php echo htmlspecialchars($row['description']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Payment Type <span class="text-danger">*</span></label>
            <select class="form-select" name="payment_type" id="payment_type" required>
                <option value="full">Full Payment (One Time)</option>
                <option value="installments">Installments</option>
            </select>
        </div>
        <div class="col-md-6 mb-3" id="installment_options" style="display: none;">
            <label class="form-label">Number of Installments</label>
            <select class="form-select" name="installment_count">
                <option value="2">2 Installments</option>
                <option value="3" selected>3 Installments</option>
            </select>
        </div>
    </div>

    <hr>
    <h5><i class="fas fa-money-bill-wave text-success"></i> Payment Details</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Amount Paying Now</label>
            <input type="number" class="form-control" name="amount_paid" placeholder="0.00" step="0.01" min="0" value="0">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select" name="payment_method">
                <option value="Cash">Cash</option>
                <option value="Bank">Bank Transfer</option>
                <option value="Card">Card</option>
                <option value="Online">Online</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Transaction Reference</label>
            <input type="text" class="form-control" name="transaction_ref" placeholder="e.g. Txn-12345">
        </div>
    </div>

    <button type="submit" name="generate_fee" class="btn btn-success"><i class="fas fa-file-invoice"></i> Generate Fee</button>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
</form>

<script>
document.getElementById('payment_type').addEventListener('change', function() {
    var options = document.getElementById('installment_options');
    if (this.value === 'installments') {
        options.style.display = 'block';
    } else {
        options.style.display = 'none';
    }
});
</script>

<?php endif; ?>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/footer.php'; ?>