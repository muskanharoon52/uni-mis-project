<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireSSO();

// ============================================
// ALL PROCESSING FIRST
// ============================================

global $conn;

$id = isset($_GET['id']) ? $_GET['id'] : '';

// ID Check
if (empty($id)) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

$table_name = 'admission_students';

// Check if student exists
$query = "SELECT s.student_id, s.father_name, s.full_name, s.student_name, s.email
          FROM $table_name s
          WHERE s.student_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    header("Location: index.php?error=Database error");
    exit;
}

$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: index.php?error=Student not found");
    exit;
}

$display_name = $student['full_name'] ?? $student['student_name'] ?? 'N/A';

// If confirm deletion
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        $student_id = $student['student_id'];
        
        // ============================================
        // STEP 1: Get all student_fee IDs for this student
        // ============================================
        $fee_ids = [];
        $get_fee_ids = "SELECT student_fee_id FROM student_fee WHERE student_id = ?";
        $stmt = $conn->prepare($get_fee_ids);
        if ($stmt) {
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $fee_result = $stmt->get_result();
            while ($row = $fee_result->fetch_assoc()) {
                $fee_ids[] = $row['student_fee_id'];
            }
            $stmt->close();
        }
        
        // ============================================
        // STEP 2: Get all payment IDs for these fees
        // ============================================
        $payment_ids = [];
        if (!empty($fee_ids)) {
            $fee_ids_str = implode(',', $fee_ids);
            
            // Get payment IDs from payments table
            $check_table = "SHOW TABLES LIKE 'payments'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $get_payments = "SELECT payment_id FROM payments WHERE student_fee_id IN ($fee_ids_str)";
                $payment_result = mysqli_query($conn, $get_payments);
                while ($row = mysqli_fetch_assoc($payment_result)) {
                    $payment_ids[] = $row['payment_id'];
                }
            }
        }
        
        // ============================================
        // STEP 3: Delete from payment_reversals first (foreign key constraint)
        // ============================================
        if (!empty($payment_ids)) {
            $payment_ids_str = implode(',', $payment_ids);
            
            // Delete from payment_reversals table
            $check_table = "SHOW TABLES LIKE 'payment_reversals'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $delete_reversals = "DELETE FROM payment_reversals WHERE payment_id IN ($payment_ids_str)";
                mysqli_query($conn, $delete_reversals);
            }
        }
        
        // ============================================
        // STEP 4: Delete receipts (foreign key constraint)
        // ============================================
        if (!empty($payment_ids)) {
            $payment_ids_str = implode(',', $payment_ids);
            
            // Delete from receipts table
            $check_table = "SHOW TABLES LIKE 'receipts'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $delete_receipts = "DELETE FROM receipts WHERE payment_id IN ($payment_ids_str)";
                mysqli_query($conn, $delete_receipts);
            }
        }
        
        // ============================================
        // STEP 5: Delete payments
        // ============================================
        if (!empty($fee_ids)) {
            $fee_ids_str = implode(',', $fee_ids);
            
            // Delete from payments table
            $check_table = "SHOW TABLES LIKE 'payments'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $delete_payments = "DELETE FROM payments WHERE student_fee_id IN ($fee_ids_str)";
                mysqli_query($conn, $delete_payments);
            }
            
            // Delete from student_fee_details
            $check_table = "SHOW TABLES LIKE 'student_fee_details'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $delete_details = "DELETE FROM student_fee_details WHERE student_fee_id IN ($fee_ids_str)";
                mysqli_query($conn, $delete_details);
            }
            
            // Delete from installments
            $check_table = "SHOW TABLES LIKE 'installments'";
            if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
                $delete_inst = "DELETE FROM installments WHERE student_fee_id IN ($fee_ids_str)";
                mysqli_query($conn, $delete_inst);
            }
        }
        
        // ============================================
        // STEP 6: Delete from student_fee table
        // ============================================
        $check_table = "SHOW TABLES LIKE 'student_fee'";
        if (mysqli_num_rows(mysqli_query($conn, $check_table)) > 0) {
            $delete_fee = "DELETE FROM student_fee WHERE student_id = ?";
            $stmt = $conn->prepare($delete_fee);
            if ($stmt) {
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // ============================================
        // STEP 7: Delete from other related tables with collation fix
        // ============================================
        
        // Fix: Use CAST to handle collation mismatch
        $related_tables = [
            'student_courses' => 'student_id',
            'attendance' => 'student_id',
            'admission_applications' => 'student_id',
            'admission_scholarship_applications' => 'student_id',
            'admission_scholarships' => 'student_id'
        ];
        
        foreach ($related_tables as $table => $column) {
            $check_table = "SHOW TABLES LIKE '$table'";
            $table_exists = mysqli_query($conn, $check_table);
            if (mysqli_num_rows($table_exists) > 0) {
                $check_column = "SHOW COLUMNS FROM $table LIKE '$column'";
                $column_exists = mysqli_query($conn, $check_column);
                if (mysqli_num_rows($column_exists) > 0) {
                    // Use CAST to convert collation
                    $delete_sql = "DELETE FROM $table WHERE $column = ?";
                    $stmt = $conn->prepare($delete_sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $student_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
        
        // ============================================
        // STEP 8: Delete from users table if email exists
        // ============================================
        if (!empty($student['email'])) {
            $delete_user = "DELETE FROM users WHERE email = ?";
            $stmt = $conn->prepare($delete_user);
            if ($stmt) {
                $stmt->bind_param("s", $student['email']);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // ============================================
        // STEP 9: Delete the student
        // ============================================
        $delete_query = "DELETE FROM $table_name WHERE student_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        
        if ($delete_stmt === false) {
            throw new Exception("Error in delete query: " . $conn->error);
        }
        
        $delete_stmt->bind_param("s", $student_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Error deleting student: " . $delete_stmt->error);
        }
        $delete_stmt->close();
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect with success message
        header("Location: index.php?success=Student " . urlencode($display_name) . " deleted successfully");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ============================================
// SHOW DELETE CONFIRMATION PAGE
// ============================================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 20px; }
    .delete-container { max-width: 600px; margin: 50px auto; }
    .delete-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
    .delete-icon { font-size: 64px; color: #e74c3c; margin-bottom: 20px; }
    .student-name { font-size: 20px; font-weight: 600; color: #2c3e50; }
    .warning-text { background: #fef3cd; padding: 15px; border-radius: 8px; color: #856404; margin: 20px 0; text-align: left; }
    .btn-group-delete { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
    .alert-danger-custom { background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 20px 0; border: 1px solid #f5c6cb; }
    .student-info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6; }
    .student-info-box .label { color: #6c757d; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    .student-info-box .value { font-size: 18px; font-weight: 500; }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="delete-container">
            <div class="delete-card">
                <div class="delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Delete Student</h4>
                <p class="text-muted">You are about to delete the following student:</p>
                
                <div class="student-info-box">
                    <div class="label">Student Name</div>
                    <div class="student-name">
                        <?php echo htmlspecialchars($display_name); ?>
                    </div>
                </div>
                
                <div class="student-info-box">
                    <div class="label">Student ID</div>
                    <div class="value">
                        <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="student-info-box">
                    <div class="label">Father's Name</div>
                    <div class="value">
                        <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="alert-danger-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Warning!</strong> This action cannot be undone!
                </div>
                
                <div class="warning-text">
                    <strong><i class="fas fa-info-circle"></i> Note:</strong>
                    <p class="mb-0 mt-1">Deleting this student will also remove all associated records including:</p>
                    <ul class="mb-0 mt-1" style="text-align: left;">
                        <li>Payment reversals</li>
                        <li>Receipts</li>
                        <li>Payments and fee records</li>
                        <li>Attendance records</li>
                        <li>Application records</li>
                        <li>Scholarship records</li>
                        <li>Course enrollment records</li>
                        <li>User account (if linked)</li>
                    </ul>
                </div>
                
                <div class="btn-group-delete">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="?id=<?php echo urlencode($id); ?>&confirm=yes" 
                       class="btn btn-danger"
                       onclick="return confirm('Are you absolutely sure you want to delete this student? This action cannot be undone!')">
                        <i class="fas fa-trash"></i> Yes, Delete Student
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>