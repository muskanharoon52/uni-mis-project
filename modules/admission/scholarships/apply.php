<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Apply Scholarship';
include __DIR__ . '/../includes/header.php';

// FIXED: Get students from the CORRECT table 'students' not 'admission_students'
$students = $pdo->query("
    SELECT 
        s.student_id,
        s.full_name,
        s.roll_no,
        s.email,
        d.department_name 
    FROM students s 
    LEFT JOIN departments d ON s.program_id = d.department_id 
    WHERE s.status = 'Active' 
    ORDER BY s.full_name
")->fetchAll();

// Get active scholarships
$scholarships = $pdo->query("SELECT * FROM admission_scholarships WHERE status='active' ORDER BY scholarship_id DESC")->fetchAll();

// Also get scholarships with different statuses
$all_scholarships = $pdo->query("SELECT * FROM admission_scholarships ORDER BY scholarship_id DESC")->fetchAll();

// Define calculateScholarship function if not exists
if (!function_exists('calculateScholarship')) {
    function calculateScholarship($percentage, $fee_amount = 100000) {
        $fee_amount = $fee_amount > 0 ? $fee_amount : 100000;
        
        if ($percentage >= 90) {
            $scholarship_percentage = 100;
            $label = 'Full Scholarship';
        } elseif ($percentage >= 80) {
            $scholarship_percentage = 75;
            $label = '75% Scholarship';
        } elseif ($percentage >= 70) {
            $scholarship_percentage = 50;
            $label = '50% Scholarship';
        } elseif ($percentage >= 60) {
            $scholarship_percentage = 25;
            $label = '25% Scholarship';
        } else {
            $scholarship_percentage = 0;
            $label = 'No Scholarship';
        }
        
        $amount = ($scholarship_percentage / 100) * $fee_amount;
        $fee_after_scholarship = $fee_amount - $amount;
        
        return [
            'percentage' => $scholarship_percentage,
            'label' => $label,
            'amount' => $amount,
            'fee_after_scholarship' => $fee_after_scholarship
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = (int)$_POST['student_id'];
        $scholarship_id = (int)$_POST['scholarship_id'];
        $marks_obtained = (float)$_POST['marks_obtained'];
        $total_marks = (float)$_POST['total_marks'];
        $percentage = ($total_marks > 0) ? ($marks_obtained / $total_marks) * 100 : 0;
        
        // FIXED: Verify student exists in the CORRECT table
        $studentCheck = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ? AND status = 'Active'");
        $studentCheck->execute([$student_id]);
        if (!$studentCheck->fetch()) {
            setFlash('error', 'Invalid student selected. Please select a valid student.');
            header('Location: apply.php');
            exit();
        }
        
        // Check if already applied
        $check = $pdo->prepare("SELECT id FROM admission_scholarship_applications WHERE student_id = ? AND scholarship_id = ?");
        $check->execute([$student_id, $scholarship_id]);
        if ($check->fetch()) {
            setFlash('error', 'Student has already applied for this scholarship');
            header('Location: apply.php');
            exit();
        }
        
        // Get fee amount
        $fee_amount = 100000; // Default fee amount
        
        // Try to get fee from fee_structures
        try {
            $feeStmt = $pdo->prepare("SELECT total_amount FROM fee_structures WHERE program_id = (SELECT program_id FROM students WHERE student_id = ?) LIMIT 1");
            $feeStmt->execute([$student_id]);
            $feeData = $feeStmt->fetch();
            if ($feeData) {
                $fee_amount = (float)$feeData['total_amount'];
            }
        } catch (Exception $e) {
            error_log("Could not fetch fee: " . $e->getMessage());
        }
        
        // Calculate scholarship
        $scholarship_result = calculateScholarship($percentage, $fee_amount);
        
        // FIXED: Remove 'fee_after_scholarship' if column doesn't exist
        $data = [
            'student_id' => $student_id,
            'scholarship_id' => $scholarship_id,
            'marks_obtained' => $marks_obtained,
            'total_marks' => $total_marks,
            'percentage' => $percentage,
            'scholarship_percentage' => $scholarship_result['percentage'],
            'scholarship_amount' => $scholarship_result['amount'],
            'status' => 'pending'
        ];
        
        // Add fee_after_scholarship only if column exists
        try {
            $tableColumns = $pdo->query("DESCRIBE admission_scholarship_applications")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('fee_after_scholarship', $tableColumns)) {
                $data['fee_after_scholarship'] = $scholarship_result['fee_after_scholarship'];
            }
        } catch (Exception $e) {
            // If describe fails, use default columns
        }
        
        // Filter data to only include existing columns
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $tableColumns ?? [])) {
                $filteredData[$key] = $value;
            }
        }
        
        // If no columns found, use the data directly
        if (empty($filteredData)) {
            $filteredData = $data;
        }
        
        $sql = "INSERT INTO admission_scholarship_applications SET ";
        $set_parts = [];
        foreach ($filteredData as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts);
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($filteredData)) {
            setFlash('success', 
                'Scholarship application submitted!<br>' .
                'Percentage: ' . number_format($percentage, 2) . '%<br>' .
                'Scholarship: ' . $scholarship_result['label'] . ' (' . $scholarship_result['percentage'] . '%)<br>' .
                'Amount: ' . formatCurrency($scholarship_result['amount'])
            );
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header"><h5><i class="fas fa-hand-holding-heart"></i> Apply for Scholarship</h5></div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-calculator"></i> Scholarship Application Form</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Scholarship is calculated based on your 12th class percentage:
                    <ul class="mb-0 mt-1">
                        <li>90%+ → 100% Scholarship</li>
                        <li>80%+ → 75% Scholarship</li>
                        <li>70%+ → 50% Scholarship</li>
                        <li>60%+ → 25% Scholarship</li>
                        <li>Below 60% → No Scholarship</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Student *</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach($students as $s): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name'] ?? 'N/A') ?> 
                                (Roll No: <?= $s['roll_no'] ?? 'N/A' ?>)
                                - <?= $s['department_name'] ?? 'N/A' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($students)): ?>
                            <small class="text-danger">No active students found. Please add students first.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Scholarship *</label>
                        <select name="scholarship_id" class="form-select" required id="scholarshipSelect">
                            <option value="">-- Select Scholarship --</option>
                            <?php foreach($all_scholarships as $s): ?>
                            <option value="<?= $s['scholarship_id'] ?>">
                                <?= htmlspecialchars($s['scholarship_name']) ?> - <?= $s['scholarship_type'] ?? 'Merit' ?> (<?= $s['percentage'] ?>%)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Marks Obtained (12th) *</label>
                                <input type="number" step="0.01" name="marks_obtained" class="form-control" required id="marksObtained" placeholder="e.g. 776">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" step="0.01" name="total_marks" class="form-control" required id="totalMarks" placeholder="e.g. 1100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Percentage</small>
                                        <h5><span id="percentageDisplay">0.00%</span></h5>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Scholarship</small>
                                        <h5><span id="scholarshipLabelDisplay">-</span></h5>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Amount</small>
                                        <h5><span id="scholarshipAmountDisplay">PKR 0</span></h5>
                                    </div>
                                </div>
                                <div id="eligibilityDisplay" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" <?= empty($students) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white"><h6 class="mb-0">Scholarship Slabs</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Percentage</th><th>Scholarship</th></tr>
                    </thead>
                    <tbody>
                        <tr class="table-success"><td>90% - 100%</td><td>100%</td></tr>
                        <tr class="table-primary"><td>80% - 89.99%</td><td>75%</td></tr>
                        <tr class="table-info"><td>70% - 79.99%</td><td>50%</td></tr>
                        <tr class="table-warning"><td>60% - 69.99%</td><td>25%</td></tr>
                        <tr class="table-secondary"><td>Below 60%</td><td>No Scholarship</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('marksObtained').addEventListener('input', calculateScholarship);
document.getElementById('totalMarks').addEventListener('input', calculateScholarship);

function calculateScholarship() {
    const obtained = parseFloat(document.getElementById('marksObtained').value) || 0;
    const total = parseFloat(document.getElementById('totalMarks').value) || 1;
    const percentage = (obtained / total) * 100;
    
    document.getElementById('percentageDisplay').textContent = percentage.toFixed(2) + '%';
    
    let scholarshipPercentage = 0;
    let label = 'No Scholarship';
    let amount = 0;
    
    if (percentage >= 90) {
        scholarshipPercentage = 100;
        label = 'Full Scholarship';
        amount = 100000;
    } else if (percentage >= 80) {
        scholarshipPercentage = 75;
        label = '75% Scholarship';
        amount = 75000;
    } else if (percentage >= 70) {
        scholarshipPercentage = 50;
        label = '50% Scholarship';
        amount = 50000;
    } else if (percentage >= 60) {
        scholarshipPercentage = 25;
        label = '25% Scholarship';
        amount = 25000;
    } else {
        scholarshipPercentage = 0;
        label = 'No Scholarship';
        amount = 0;
    }
    
    document.getElementById('scholarshipLabelDisplay').textContent = label;
    document.getElementById('scholarshipAmountDisplay').textContent = 'PKR ' + amount.toLocaleString();
    
    const eligibilityDiv = document.getElementById('eligibilityDisplay');
    if (scholarshipPercentage > 0) {
        eligibilityDiv.innerHTML = '<span class="text-success">✅ Eligible for ' + label + '</span>';
    } else {
        eligibilityDiv.innerHTML = '<span class="text-danger">❌ Not eligible for any scholarship (Need 60% minimum)</span>';
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>