<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

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
    $search_sql = "SELECT student_id, full_name, roll_no, program_id, current_semester_id, current_session_id 
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
    $sel_sql = "SELECT student_id, full_name, roll_no, program_id, current_semester_id, current_session_id 
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

// --- GENERATE FEE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_fee'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $semester_id = mysqli_real_escape_string($conn, $_POST['semester_id']);
    $session_id = mysqli_real_escape_string($conn, $_POST['session_id']);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $installment_count = isset($_POST['installment_count']) ? intval($_POST['installment_count']) : 1;
    $generated_by = 1;

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

            // Calculate due date (30 days from now)
            $due_date = date('Y-m-d', strtotime('+30 days'));

            // Insert into student_fee
            $insert_sql = "INSERT INTO student_fee 
                          (student_id, semester_id, session_id, fee_structure_id, 
                           total_amount, paid_amount, due_date, generated_by) 
                          VALUES ('$student_id', '$semester_id', '$session_id', 
                                  '$fee_structure_id', '$total_amount', 0, '$due_date', '$generated_by')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $student_fee_id = mysqli_insert_id($conn);
                
                // Insert fee details
                $detail_sql = "SELECT fee_head_id, amount FROM fee_structure_details 
                               WHERE fee_structure_id = '$fee_structure_id'";
                $detail_result = mysqli_query($conn, $detail_sql);
                
                while ($detail_row = mysqli_fetch_assoc($detail_result)) {
                    $sfd_sql = "INSERT INTO student_fee_details 
                               (student_fee_id, fee_head_id, amount, discount_amount) 
                               VALUES ('$student_fee_id', '{$detail_row['fee_head_id']}', '{$detail_row['amount']}', 0)";
                    mysqli_query($conn, $sfd_sql);
                }

                // --- INSTALLMENT LOGIC (Max 3 Installments) ---
                if ($payment_type == 'installments' && $installment_count > 1) {
                    $installment_amount = round($total_amount / $installment_count, 2);
                    $remainder = round($total_amount - ($installment_amount * $installment_count), 2);
                    
                    // Adjust last installment to include remainder
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

                $generated_fee_id = $student_fee_id;
                $success = "Fee generated successfully!";
                
                // Get student name
                $name_sql = "SELECT full_name FROM students WHERE student_id = '$student_id'";
                $name_result = mysqli_query($conn, $name_sql);
                $name_row = mysqli_fetch_assoc($name_result);
                $student_name = $name_row['full_name'];
                
                // Get fee structure details
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Student Fee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <!-- ===== CARD HEADER WITH CROSS BUTTON ===== -->
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-file-invoice"></i> Generate Student Fee</h4>
                    <a href="index.php" class="btn btn-light btn-sm" title="Close">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> <?php echo $success; ?></h5>
                            <p>Student: <strong><?php echo htmlspecialchars($student_name); ?></strong></p>
                            <p>Total Amount: <strong>PKR <?php echo number_format($total_amount, 2); ?></strong></p>
                            <hr>
                            <h6>Fee Breakdown:</h6>
                            <ul>
                                <?php foreach($fee_structure as $item): ?>
                                    <li><?php echo htmlspecialchars($item['fee_head_name']); ?>: PKR <?php echo number_format($item['amount'], 2); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if(isset($_POST['payment_type']) && $_POST['payment_type'] == 'installments'): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-calendar-alt"></i> Installment Plan: <?php echo $installment_count; ?> installments
                                </div>
                            <?php endif; ?>
                            <a href="view.php?id=<?php echo $generated_fee_id; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Fee Details
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> View All Fees
                            </a>
                            <a href="generate.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Generate Another Fee
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if(empty($success)): ?>
                    
                    <!-- ===== SEARCH SECTION ===== -->
                    <div class="card mb-4 bg-light">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-search"></i> Search Student</span>
                            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                                <a href="generate.php" class="btn btn-light btn-sm" title="Clear Search">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           placeholder="Search by Student Name, Roll No, or Student ID..." 
                                           value="<?php echo htmlspecialchars($search_term); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </form>

                            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                                <?php if(mysqli_num_rows($search_results) > 0): ?>
                                    <div class="mt-3">
                                        <h6><i class="fas fa-users"></i> Search Results (<?php echo mysqli_num_rows($search_results); ?> found)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Student ID</th>
                                                        <th>Name</th>
                                                        <th>Roll No</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                                                    <tr>
                                                        <td><?php echo $row['student_id']; ?></td>
                                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <a href="generate.php?student_id=<?php echo $row['student_id']; ?>" 
                                                               class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Select
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> No students found matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                                        <br>
                                        <a href="generate.php" class="btn btn-sm btn-secondary mt-2">
                                            <i class="fas fa-times"></i> Clear Search
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ===== SELECTED STUDENT ===== -->
                    <?php if($selected_student): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div>
                                <h5><i class="fas fa-user-check"></i> Selected Student</h5>
                                <p class="mb-0">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($selected_student['full_name']); ?><br>
                                    <strong>Roll No:</strong> <?php echo htmlspecialchars($selected_student['roll_no'] ?? 'N/A'); ?><br>
                                    <strong>Student ID:</strong> <?php echo $selected_student['student_id']; ?>
                                </p>
                            </div>
                            <a href="generate.php" class="btn btn-light btn-sm" title="Clear Selection">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- ===== FEE GENERATION FORM ===== -->
                    <form method="POST" action="" id="feeForm">
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
                                <small class="text-muted">Or use the search box above to find a student.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-select" name="semester_id" required>
                                    <option value="">Select Semester</option>
                                    <?php 
                                    mysqli_data_seek($semester_result, 0);
                                    while($row = mysqli_fetch_assoc($semester_result)): 
                                    ?>
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
                                    <?php 
                                    mysqli_data_seek($session_result, 0);
                                    while($row = mysqli_fetch_assoc($session_result)): 
                                    ?>
                                        <option value="<?php echo $row['session_id']; ?>">
                                            <?php echo htmlspecialchars($row['session_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_type" id="payment_type" required onchange="toggleInstallments()">
                                    <option value="full">Full Payment (One Time)</option>
                                    <option value="installments">Installments</option>
                                </select>
                            </div>
                        </div>

                        <!-- Installment Options (Hidden by default) - MAX 3 INSTALLMENTS -->
                        <div class="row" id="installment_options" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Number of Installments <span class="text-danger">*</span></label>
                                <select class="form-select" name="installment_count">
                                    <option value="2">2 Installments</option>
                                    <option value="3" selected>3 Installments</option>
                                </select>
                                <small class="text-muted">Each installment will be due monthly (Max 3)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Installment Plan:</strong> Total fee will be divided into equal installments.
                                    <br>First installment due immediately, remaining monthly.
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                            <button type="submit" name="generate_fee" class="btn btn-success"><i class="fas fa-file-invoice"></i> Generate Fee</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleInstallments() {
    var paymentType = document.getElementById('payment_type').value;
    var installmentOptions = document.getElementById('installment_options');
    if (paymentType === 'installments') {
        installmentOptions.style.display = 'flex';
    } else {
        installmentOptions.style.display = 'none';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>