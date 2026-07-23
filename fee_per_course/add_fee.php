<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Set collation for connection
$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = utf8mb4_unicode_ci");

// Fetch students for dropdown
$students_query = "SELECT s.student_id, s.roll_no, u.full_name 
                   FROM students s 
                   LEFT JOIN users u ON s.user_id = u.user_id 
                   ORDER BY u.full_name";
$students = $conn->query($students_query);

// Fetch courses for dropdown
$courses_query = "SELECT course_id, course_code, course_name, credit_hours, fee_amount 
                  FROM courses 
                  ORDER BY course_code";
$courses = $conn->query($courses_query);

// Fetch semesters for dropdown
$semesters_query = "SELECT semester_id, semester_name 
                    FROM semesters 
                    ORDER BY semester_name";
$semesters = $conn->query($semesters_query);

// Initialize variables
$student_id = $course_id = $semester_id = '';
$fee_amount = $paid_amount = 0;
$status = 'Unpaid';
$due_date = '';
$payment_date = '';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : '';
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;
    $fee_amount = isset($_POST['fee_amount']) ? (float)$_POST['fee_amount'] : 0;
    $paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Unpaid';
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
    $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : NULL;
    
    // Validation
    $errors = [];
    
    if (empty($student_id)) {
        $errors[] = "Please select a student.";
    }
    
    if ($course_id <= 0) {
        $errors[] = "Please select a course.";
    }
    
    if ($semester_id <= 0) {
        $errors[] = "Please select a semester.";
    }
    
    if ($fee_amount <= 0) {
        $errors[] = "Fee amount must be greater than 0.";
    }
    
    if ($paid_amount < 0) {
        $errors[] = "Paid amount cannot be negative.";
    }
    
    if ($paid_amount > $fee_amount) {
        $errors[] = "Paid amount cannot exceed total fee amount.";
    }
    
    // Auto-set status based on payment
    if ($paid_amount == 0) {
        $status = 'Unpaid';
    } elseif ($paid_amount >= $fee_amount) {
        $status = 'Paid';
    } else {
        $status = 'Partially Paid';
    }
    
    // Check if empty payment_date
    if (empty($payment_date)) {
        $payment_date = NULL;
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $insert_sql = "INSERT INTO student_course_fees 
                       (student_id, course_id, semester_id, fee_amount, paid_amount, 
                        status, due_date, payment_date, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_sql);
        
        if ($stmt) {
            $stmt->bind_param("siiddsss", $student_id, $course_id, $semester_id, 
                              $fee_amount, $paid_amount, $status, $due_date, $payment_date);
            
            if ($stmt->execute()) {
                $success_message = "Fee record added successfully!";
                $_SESSION['message'] = $success_message;
                $_SESSION['message_type'] = 'success';
                
                // Clear form fields
                $student_id = $course_id = $semester_id = '';
                $fee_amount = $paid_amount = 0;
                $status = 'Unpaid';
                $due_date = '';
                $payment_date = '';
                
                // Redirect after 2 seconds
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'view_fees.php';
                        }, 2000);
                      </script>";
            } else {
                $error_message = "Error saving record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Fee';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .report-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-container h4 {
        color: #2c3e50;
        border-bottom: 3px solid #3498db;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.15);
    }
    
    .btn-submit {
        background: #3498db;
        color: white;
        padding: 12px 40px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-submit:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52,152,219,0.3);
    }
    
    .btn-reset {
        background: #95a5a6;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-reset:hover {
        background: #7f8c8d;
    }
    
    .status-preview {
        padding: 10px 15px;
        border-radius: 5px;
        background: #f8f9fa;
        border-left: 4px solid #3498db;
    }
    
    .required {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    .success-box {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid #28a745;
        margin-bottom: 20px;
    }
    
    .error-box {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid #dc3545;
        margin-bottom: 20px;
    }
    
    .auto-status {
        font-size: 14px;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-block;
        font-weight: 600;
    }
    
    .auto-status.Paid {
        background: #d4edda;
        color: #155724;
    }
    
    .auto-status.Unpaid {
        background: #f8d7da;
        color: #721c24;
    }
    
    .auto-status.Partially\ Paid {
        background: #fff3cd;
        color: #856404;
    }
    
    @media (max-width: 768px) {
        .report-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
        
        .btn-submit, .btn-reset {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<div class="report-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4><i class="fas fa-plus-circle text-success"></i> Add New Fee Record</h4>
                <p class="text-muted mb-0">Create a new fee record for a student</p>
            </div>
            <div class="no-print">
                <a href="view_fees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Display Session Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Show Error/Success Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-box">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success:</strong> <?= $success_message ?>
                <br><small>Redirecting to fee list...</small>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-container">
            <h4><i class="fas fa-money-bill-wave text-primary me-2"></i>Fee Information</h4>
            
            <form method="POST" action="">
                <div class="row">
                    <!-- Student -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-graduate me-1"></i> Student <span class="required">*</span>
                        </label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php if ($students && $students->num_rows > 0): ?>
                                <?php while($row = $students->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['student_id']) ?>" 
                                        <?= $student_id == $row['student_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['full_name']) ?> 
                                        (<?= htmlspecialchars($row['roll_no']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (!$students || $students->num_rows == 0): ?>
                            <small class="text-danger">No students found. Please add students first.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Course -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-book me-1"></i> Course <span class="required">*</span>
                        </label>
                        <select name="course_id" class="form-select" required onchange="updateFeeAmount(this)">
                            <option value="0">-- Select Course --</option>
                            <?php if ($courses && $courses->num_rows > 0): ?>
                                <?php while($row = $courses->fetch_assoc()): ?>
                                    <option value="<?= $row['course_id'] ?>" 
                                        data-fee="<?= $row['fee_amount'] ?>"
                                        <?= $course_id == $row['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['course_code']) ?> - 
                                        <?= htmlspecialchars($row['course_name']) ?> 
                                        (Rs. <?= number_format($row['fee_amount'], 2) ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (!$courses || $courses->num_rows == 0): ?>
                            <small class="text-danger">No courses found. Please add courses first.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Semester -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i> Semester <span class="required">*</span>
                        </label>
                        <select name="semester_id" class="form-select" required>
                            <option value="0">-- Select Semester --</option>
                            <?php if ($semesters && $semesters->num_rows > 0): ?>
                                <?php while($row = $semesters->fetch_assoc()): ?>
                                    <option value="<?= $row['semester_id'] ?>" 
                                        <?= $semester_id == $row['semester_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['semester_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (!$semesters || $semesters->num_rows == 0): ?>
                            <small class="text-danger">No semesters found. Please add semesters first.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Fee Amount -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-money-bill me-1"></i> Total Fee (Rs.) <span class="required">*</span>
                        </label>
                        <input type="number" name="fee_amount" id="fee_amount" 
                               class="form-control" step="0.01" min="0" 
                               value="<?= $fee_amount ?>" 
                               placeholder="Enter fee amount" required
                               onchange="updateStatus()" onkeyup="updateStatus()">
                    </div>

                    <!-- Paid Amount -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-hand-holding-usd me-1"></i> Paid Amount (Rs.)
                        </label>
                        <input type="number" name="paid_amount" id="paid_amount" 
                               class="form-control" step="0.01" min="0" 
                               value="<?= $paid_amount ?>" 
                               placeholder="Enter paid amount"
                               onchange="updateStatus()" onkeyup="updateStatus()">
                    </div>

                    <!-- Status (Auto-calculated) -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-info-circle me-1"></i> Status (Auto-calculated)
                        </label>
                        <div class="status-preview">
                            <span id="status_display" class="auto-status Unpaid">Unpaid</span>
                            <small class="text-muted ms-2">(Auto-calculated based on payment)</small>
                        </div>
                        <input type="hidden" name="status" id="status_hidden" value="<?= $status ?>">
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-times me-1"></i> Due Date
                        </label>
                        <input type="date" name="due_date" class="form-control" 
                               value="<?= $due_date ?>">
                    </div>

                    <!-- Payment Date -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-check me-1"></i> Payment Date
                        </label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?= $payment_date ?>">
                    </div>

                    <!-- Remaining Amount -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calculator me-1"></i> Remaining Amount
                        </label>
                        <div class="form-control" style="background: #f8f9fa; font-weight: bold;">
                            <span id="remaining_display">Rs. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="d-flex gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save me-2"></i> Save Fee Record
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-undo me-2"></i> Reset Form
                    </button>
                    <a href="view_fees.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle text-primary"></i> Help & Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Status</strong> is automatically calculated based on paid amount.</li>
                        <li><strong>Total Fee</strong> must be greater than 0.</li>
                        <li><strong>Paid Amount</strong> cannot exceed the total fee.</li>
                        <li><strong>Due Date</strong> is the deadline for fee payment.</li>
                        <li><strong>Payment Date</strong> is when the student made the payment.</li>
                        <li>If <strong>Paid Amount</strong> equals <strong>Total Fee</strong>, status becomes "Paid".</li>
                        <li>If <strong>Paid Amount</strong> is 0, status becomes "Unpaid".</li>
                        <li>If <strong>Paid Amount</strong> is between 0 and Total Fee, status becomes "Partially Paid".</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Update fee amount when course is selected
function updateFeeAmount(select) {
    var selectedOption = select.options[select.selectedIndex];
    var feeAmount = selectedOption.getAttribute('data-fee');
    if (feeAmount) {
        document.getElementById('fee_amount').value = feeAmount;
        updateStatus();
    }
}

// Update status and remaining amount
function updateStatus() {
    var totalFee = parseFloat(document.getElementById('fee_amount').value) || 0;
    var paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
    var remaining = totalFee - paidAmount;
    
    // Update remaining display
    document.getElementById('remaining_display').textContent = 'Rs. ' + remaining.toFixed(2);
    
    // Determine status
    var status = 'Unpaid';
    var statusClass = 'Unpaid';
    
    if (totalFee > 0) {
        if (paidAmount >= totalFee) {
            status = 'Paid';
            statusClass = 'Paid';
        } else if (paidAmount > 0) {
            status = 'Partially Paid';
            statusClass = 'Partially Paid';
        } else {
            status = 'Unpaid';
            statusClass = 'Unpaid';
        }
    }
    
    // Update status display
    var statusDisplay = document.getElementById('status_display');
    statusDisplay.textContent = status;
    statusDisplay.className = 'auto-status ' + statusClass;
    
    // Update hidden status field
    document.getElementById('status_hidden').value = status;
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        let bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Initialize on page load
window.onload = function() {
    updateStatus();
};
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>