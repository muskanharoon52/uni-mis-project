<?php
// fee_management/assign_to_student.php - Assign Fee to Student (AUTO AMOUNT)

require_once __DIR__ . '../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin', 'account'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$message = '';
$message_type = '';

// Get students with scholarship
$students = [];
$student_result = mysqli_query($conn, "SELECT s.student_id, u.full_name, s.scholarship_percentage 
                                       FROM students s 
                                       LEFT JOIN users u ON s.user_id = u.user_id 
                                       ORDER BY u.full_name");
if ($student_result) {
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students[] = $row;
    }
}

// Get fee structures
$structures = [];
$struct_result = mysqli_query($conn, "SELECT fs.*, p.program_name, s.semester_name 
                                       FROM fee_structures fs
                                       LEFT JOIN programs p ON fs.program_id = p.program_id
                                       LEFT JOIN semesters s ON fs.semester_id = s.semester_id
                                       WHERE fs.status = 'Active'
                                       ORDER BY fs.created_at DESC");
if ($struct_result) {
    while ($row = mysqli_fetch_assoc($struct_result)) {
        $structures[] = $row;
    }
}

// Get sessions
$sessions = [];
$session_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($session_result) {
    while ($row = mysqli_fetch_assoc($session_result)) {
        $sessions[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fee_structure_id = (int)$_POST['fee_structure_id'];
    $total_amount = floatval($_POST['total_amount']);
    $semester_id = (int)$_POST['semester_id'];
    $session_id = (int)$_POST['session_id'];
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $user_id = $_SESSION['user_id'];
    
    // Get student's scholarship
    $scholarship_query = "SELECT scholarship_percentage FROM students WHERE student_id = '$student_id'";
    $scholarship_result = mysqli_query($conn, $scholarship_query);
    $scholarship_percent = 0;
    if ($scholarship_result && mysqli_num_rows($scholarship_result) > 0) {
        $sch_row = mysqli_fetch_assoc($scholarship_result);
        $scholarship_percent = (int)($sch_row['scholarship_percentage'] ?? 0);
    }
    
    $discount_amount = ($total_amount * $scholarship_percent) / 100;
    $net_amount = $total_amount - $discount_amount;
    
    // Check if already assigned
    $check_query = "SELECT student_fee_id FROM student_fee WHERE student_id = '$student_id' AND semester_id = $semester_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $message = "Fee already assigned to this student for this semester!";
        $message_type = 'danger';
    } else {
        $query = "INSERT INTO student_fee (student_id, semester_id, session_id, fee_structure_id, total_amount, paid_amount, due_date, status, generated_by) 
                  VALUES ('$student_id', $semester_id, $session_id, $fee_structure_id, $net_amount, 0, '$due_date', 'Unpaid', $user_id)";
        
        if (mysqli_query($conn, $query)) {
            $record_query = "INSERT INTO fee_records (student_id, semester, total_fee, paid_amount, due_date, status) 
                             VALUES ('$student_id', $semester_id, $net_amount, 0, '$due_date', 'pending')";
            mysqli_query($conn, $record_query);
            
            if ($scholarship_percent > 0) {
                $sch_query = "INSERT INTO scholarships (student_id, scholarship_type, awarding_body, semester_id, discount_kind, discount_value, approved_by, remarks, status) 
                              VALUES ('$student_id', 'Applied', 'Fee Assignment', $semester_id, 'Percentage', $scholarship_percent, $user_id, 'Applied on fee - Original: $total_amount, Net: $net_amount', 'Active')";
                mysqli_query($conn, $sch_query);
            }
            
            $message = "✅ Fee assigned successfully!<br>
                        <strong>Original Fee:</strong> Rs. " . number_format($total_amount, 2) . "<br>
                        <strong>Scholarship:</strong> $scholarship_percent% (Rs. " . number_format($discount_amount, 2) . ")<br>
                        <strong>Net Fee:</strong> Rs. " . number_format($net_amount, 2);
            $message_type = 'success';
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .fee-content { margin-left: 250px; padding: 20px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0; font-weight: 600; }
    .btn-save { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; color: white; padding: 10px 30px; border-radius: 10px; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4); color: white; }
    .scholarship-info { background: #e8f5e9; padding: 15px; border-radius: 10px; border-left: 4px solid #28a745; }
    .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; overflow-y: auto; z-index: 1000; }
    .sidebar .brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar .brand h4 { font-weight: 700; margin: 0; }
    .sidebar .brand small { color: #a8a8b3; }
    .sidebar .nav-link { color: #a8a8b3; padding: 12px 20px; border-radius: 0; transition: all 0.3s; }
    .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
    .sidebar .nav-link.active { color: white; background: rgba(102, 126, 234, 0.3); border-left: 3px solid #667eea; }
    .sidebar .nav-link i { width: 20px; margin-right: 10px; }
    .topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .fee-structure-amount { font-size: 18px; font-weight: 700; color: #28a745; }
    @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .fee-content { margin-left: 0; } }
</style>

<div class="fee-content">
    <div class="container-fluid">
        <div class="topbar">
            <div><h5 class="mb-0"><i class="fas fa-user-plus text-success"></i> Assign Fee to Student</h5></div>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="scholarship-info mb-3">
            <i class="fas fa-graduation-cap"></i>
            <strong>Scholarship Info:</strong> 
            <span id="scholarship_display">Select a student to see scholarship</span>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-money-bill-wave me-2"></i> Assign Fee to Student</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Select Student</label>
                            <select name="student_id" id="student_id" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>" data-scholarship="<?php echo $student['scholarship_percentage'] ?? 0; ?>">
                                    <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')'); ?>
                                    <?php if (($student['scholarship_percentage'] ?? 0) > 0): ?>
                                        - <?php echo $student['scholarship_percentage']; ?>% Scholarship
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Fee Structure</label>
                            <select name="fee_structure_id" id="fee_structure_id" class="form-select" required>
                                <option value="">-- Select Fee Structure --</option>
                                <?php foreach ($structures as $struct): ?>
                                <option value="<?php echo $struct['fee_structure_id']; ?>" data-amount="<?php echo $struct['total_amount']; ?>">
                                    <?php echo htmlspecialchars($struct['program_name'] . ' - ' . $struct['semester_name'] . ' (Rs.' . number_format($struct['total_amount'], 0) . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Original Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="total_amount" id="total_amount" class="form-control" readonly style="background:#f8f9fa; font-weight:700; color:#2c3e50;" required>
                            </div>
                            <small class="text-muted">Auto-loaded from fee structure</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Net Amount (After Scholarship)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="text" id="net_amount" class="form-control" readonly style="font-weight:700; color:#28a745; font-size:18px;">
                            </div>
                            <small id="discount_info" class="text-muted"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Semester</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">-- Select Semester --</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?><?php echo ($i == 1) ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th')); ?> Semester</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Session</label>
                            <select name="session_id" class="form-select" required>
                                <option value="">-- Select Session --</option>
                                <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['session_id']; ?>">
                                    <?php echo htmlspecialchars($session['session_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i> Assign Fee</button>
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var studentSelect = document.getElementById('student_id');
    var structureSelect = document.getElementById('fee_structure_id');
    var amountInput = document.getElementById('total_amount');
    var netAmountDisplay = document.getElementById('net_amount');
    var discountInfo = document.getElementById('discount_info');
    var scholarshipDisplay = document.getElementById('scholarship_display');
    
    function calculateNetAmount() {
        // Get scholarship from student
        var selectedStudent = studentSelect.options[studentSelect.selectedIndex];
        var scholarship = parseFloat(selectedStudent.getAttribute('data-scholarship')) || 0;
        
        // Get amount from fee structure
        var selectedStructure = structureSelect.options[structureSelect.selectedIndex];
        var total = parseFloat(selectedStructure.getAttribute('data-amount')) || 0;
        
        // Update amount input
        amountInput.value = total.toFixed(2);
        
        // Update scholarship display
        if (scholarship > 0) {
            scholarshipDisplay.innerHTML = '<span class="badge bg-success">' + scholarship + '% Scholarship Available</span>';
        } else {
            scholarshipDisplay.innerHTML = 'No scholarship available for this student';
        }
        
        // Calculate net amount
        var discount = (total * scholarship) / 100;
        var net = total - discount;
        
        if (total > 0) {
            netAmountDisplay.value = net.toFixed(2);
            if (scholarship > 0) {
                discountInfo.innerHTML = '💡 <strong>' + scholarship + '%</strong> discount applied (Rs. ' + discount.toFixed(2) + ' saved)';
                discountInfo.style.color = '#28a745';
            } else {
                discountInfo.innerHTML = 'No discount applied';
                discountInfo.style.color = '#6c757d';
            }
        } else {
            netAmountDisplay.value = '';
            discountInfo.innerHTML = 'Select a fee structure first';
        }
    }
    
    studentSelect.addEventListener('change', calculateNetAmount);
    structureSelect.addEventListener('change', calculateNetAmount);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>