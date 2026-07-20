<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';
$student_fee_id = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : 0;

if ($student_fee_id <= 0) {
    header("Location: index.php?error=Invalid fee record");
    exit;
}

// Fetch fee record
$sql = "SELECT scf.*, 
        s.student_id, s.roll_no,
        u.full_name as student_name,
        c.course_code, c.course_name,
        sem.semester_name
        FROM student_course_fees scf
        LEFT JOIN students s ON scf.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN courses c ON scf.course_id = c.course_id
        LEFT JOIN semesters sem ON scf.semester_id = sem.semester_id
        WHERE scf.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_fee_id);
$stmt->execute();
$result = $stmt->get_result();
$fee_record = $result->fetch_assoc();
$stmt->close();

if (!$fee_record) {
    header("Location: index.php?error=Fee record not found");
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $received_by = $_SESSION['user_id'] ?? 0;

    if ($amount <= 0) {
        $error = "Please enter a valid amount";
    } elseif ($amount > ($fee_record['fee_amount'] - $fee_record['paid_amount'])) {
        $error = "Amount exceeds the remaining balance of Rs. " . 
                 number_format($fee_record['fee_amount'] - $fee_record['paid_amount'], 2);
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update student fee record
            $new_paid = $fee_record['paid_amount'] + $amount;
            $status = 'Partially Paid';
            if ($new_paid >= $fee_record['fee_amount']) {
                $status = 'Paid';
                $payment_date = date('Y-m-d');
            }

            $update_query = "UPDATE student_course_fees SET 
                             paid_amount = ?, 
                             status = ?, 
                             payment_date = ? 
                             WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("dssi", $new_paid, $status, $payment_date, $student_fee_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Generate receipt number
            $receipt_prefix = 'RCPT';
            $receipt_no = $receipt_prefix . date('Y') . str_pad($student_fee_id, 6, '0', STR_PAD_LEFT);

            // Insert payment record
            $insert_query = "INSERT INTO fee_payments 
                            (student_course_fee_id, amount, payment_date, payment_method, 
                             transaction_id, receipt_no, received_by, remarks) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("idssssis", $student_fee_id, $amount, $payment_date, 
                                     $payment_method, $transaction_id, $receipt_no, $received_by, $remarks);
            $insert_stmt->execute();
            $insert_stmt->close();

            $conn->commit();
            header("Location: student_fees.php?student=" . $fee_record['student_id'] . 
                   "&success=Payment of Rs. " . number_format($amount, 2) . " recorded successfully!");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Record Payment';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .payment-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .fee-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #27ae60;
    }
    
    .fee-details .row {
        padding: 5px 0;
    }
    
    .btn-pay {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .amount-display {
        font-size: 1.5rem;
        font-weight: 700;
        color: #27ae60;
    }
    
    @media (max-width: 768px) {
        .payment-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="payment-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-credit-card"></i> Record Payment</h4>
            <a href="student_fees.php?student=<?= $fee_record['student_id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Student Fees
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <!-- Fee Details -->
            <div class="fee-details">
                <div class="row">
                    <div class="col-md-6"><strong>Student:</strong> <?= htmlspecialchars($fee_record['student_name']) ?></div>
                    <div class="col-md-6"><strong>Student ID:</strong> <?= htmlspecialchars($fee_record['student_id']) ?></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Course:</strong> <?= htmlspecialchars($fee_record['course_code']) ?></div>
                    <div class="col-md-6"><strong>Semester:</strong> <?= htmlspecialchars($fee_record['semester_name']) ?></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Total Fee:</strong> Rs. <?= number_format($fee_record['fee_amount'], 2) ?></div>
                    <div class="col-md-6"><strong>Paid:</strong> Rs. <?= number_format($fee_record['paid_amount'], 2) ?></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Remaining:</strong> 
                        <span class="amount-display">Rs. <?= number_format($fee_record['fee_amount'] - $fee_record['paid_amount'], 2) ?></span>
                    </div>
                    <div class="col-md-6"><strong>Status:</strong> 
                        <span class="badge bg-<?= $fee_record['status'] == 'Paid' ? 'success' : ($fee_record['status'] == 'Partially Paid' ? 'warning' : 'danger') ?>">
                            <?= $fee_record['status'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($fee_record['status'] != 'Paid'): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" 
                               step="0.01" min="0.01" 
                               max="<?= $fee_record['fee_amount'] - $fee_record['paid_amount'] ?>" 
                               placeholder="Enter amount to pay" required>
                        <small class="text-muted">Max: Rs. <?= number_format($fee_record['fee_amount'] - $fee_record['paid_amount'], 2) ?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Online">Online Payment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" 
                               placeholder="Enter transaction/reference ID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" 
                                  placeholder="Any additional notes..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-pay">
                            <i class="fas fa-check"></i> Record Payment
                        </button>
                        <a href="student_fees.php?student=<?= $fee_record['student_id'] ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <h5>This fee is fully paid!</h5>
                    <p class="mb-0">Total paid: Rs. <?= number_format($fee_record['paid_amount'], 2) ?></p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>