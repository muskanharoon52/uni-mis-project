<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Apply Scholarship';
include __DIR__ . '/../includes/header.php';

$students = $pdo->query("
    SELECT s.*, d.department_name 
    FROM admission_students s 
    LEFT JOIN departments d ON s.program_id = d.department_id 
    WHERE s.status='active' 
    ORDER BY s.student_name
")->fetchAll();

$scholarships = $pdo->query("SELECT * FROM admission_scholarships WHERE status='active' ORDER BY deadline ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = $_POST['student_id'];
        $scholarship_id = $_POST['scholarship_id'];
        $marks_obtained = $_POST['marks_obtained'];
        $total_marks = $_POST['total_marks'];
        $percentage = ($total_marks > 0) ? ($marks_obtained / $total_marks) * 100 : 0;
        
        $check = $pdo->prepare("SELECT id FROM admission_scholarship_applications WHERE student_id = ? AND scholarship_id = ?");
        $check->execute([$student_id, $scholarship_id]);
        if ($check->fetch()) {
            setFlash('error', 'Student has already applied for this scholarship');
            header('Location: apply.php');
            exit();
        }
        
        $student = $pdo->prepare("SELECT program_id FROM admission_students WHERE id = ?");
        $student->execute([$student_id]);
        $student_data = $student->fetch();
        
        $fee = $pdo->prepare("SELECT fee_amount FROM departments WHERE department_id = ?");
        $fee->execute([$student_data['program_id'] ?? 0]);
        $fee_data = $fee->fetch();
        $fee_amount = $fee_data['fee_amount'] ?? 0;
        
        $scholarship_result = calculateScholarship($percentage, $fee_amount);
        
        $data = [
            'student_id' => $student_id,
            'scholarship_id' => $scholarship_id,
            'marks_obtained' => $marks_obtained,
            'total_marks' => $total_marks,
            'percentage' => $percentage,
            'scholarship_percentage' => $scholarship_result['percentage'],
            'scholarship_amount' => $scholarship_result['amount'],
            'fee_after_scholarship' => $scholarship_result['fee_after_scholarship'],
            'status' => 'pending'
        ];
        
        $sql = "INSERT INTO admission_scholarship_applications SET ";
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts);
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            setFlash('success', 
                'Scholarship application submitted! Percentage: ' . number_format($percentage, 2) . '% | Scholarship: ' . $scholarship_result['label']
            );
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Apply for Scholarship</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Scholarship Application Form</h3>
                <p>Submit student credentials for scholarship evaluation</p>
            </div>
        </div>
        <div class="card-content">
            <div class="alert" style="background:var(--accent-light);color:var(--accent);border:1px solid var(--info-border);margin-bottom:16px;">
                <strong>Scholarship Slabs:</strong> 90%+ = 100% | 80%+ = 75% | 70%+ = 50% | 60%+ = 25% | Below 60% = None
            </div>
            
            <form method="POST">
                <div class="field" style="margin-bottom:16px;">
                    <label>Select Student *</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach($students as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['student_name']) ?> (<?= htmlspecialchars($s['student_id']) ?>) - <?= htmlspecialchars($s['department_name'] ?? 'N/A') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="field" style="margin-bottom:16px;">
                    <label>Select Scholarship *</label>
                    <select name="scholarship_id" required id="scholarshipSelect">
                        <option value="">-- Select Scholarship --</option>
                        <?php foreach($scholarships as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['scholarship_name']) ?> - <?= htmlspecialchars($s['scholarship_type'] ?? 'Merit') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="field">
                        <label>Marks Obtained (12th) *</label>
                        <input type="number" step="0.01" name="marks_obtained" required id="marksObtained" placeholder="e.g. 776">
                    </div>
                    <div class="field">
                        <label>Total Marks *</label>
                        <input type="number" step="0.01" name="total_marks" required id="totalMarks" placeholder="e.g. 1100">
                    </div>
                </div>
                
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div>
                            <div class="muted">Percentage</div>
                            <div style="font-size:1.1rem;font-weight:700;"><span id="percentageDisplay">0.00%</span></div>
                        </div>
                        <div>
                            <div class="muted">Scholarship</div>
                            <div style="font-size:1.1rem;font-weight:700;"><span id="scholarshipLabelDisplay">-</span></div>
                        </div>
                        <div>
                            <div class="muted">Amount</div>
                            <div style="font-size:1.1rem;font-weight:700;"><span id="scholarshipAmountDisplay">PKR 0</span></div>
                        </div>
                    </div>
                    <div id="eligibilityDisplay" style="margin-top:8px;font-size:.88rem;"></div>
                </div>
                
                <div class="form-actions" style="border-top:1px solid var(--border);padding-top:16px;">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Application</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Scholarship Slabs</h3>
                <p>Standard qualification threshold slabs</p>
            </div>
        </div>
        <div class="card-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Percentage Range</th><th>Award Concession</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>90% - 100%</td><td><span class="status-badge approved">100% Concession</span></td></tr>
                        <tr><td>80% - 89.99%</td><td><span class="status-badge active">75% Concession</span></td></tr>
                        <tr><td>70% - 79.99%</td><td><span class="status-badge active">50% Concession</span></td></tr>
                        <tr><td>60% - 69.99%</td><td><span class="status-badge pending">25% Concession</span></td></tr>
                        <tr><td>Below 60%</td><td><span class="badge badge-outline">Ineligible</span></td></tr>
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
    
    if (percentage >= 90) { scholarshipPercentage = 100; label = 'Full Scholarship'; amount = 100000; }
    else if (percentage >= 80) { scholarshipPercentage = 75; label = '75% Scholarship'; amount = 75000; }
    else if (percentage >= 70) { scholarshipPercentage = 50; label = '50% Scholarship'; amount = 50000; }
    else if (percentage >= 60) { scholarshipPercentage = 25; label = '25% Scholarship'; amount = 25000; }
    else { scholarshipPercentage = 0; label = 'No Scholarship'; amount = 0; }
    
    document.getElementById('scholarshipLabelDisplay').textContent = label;
    document.getElementById('scholarshipAmountDisplay').textContent = 'PKR ' + amount.toLocaleString();
    
    const eligibilityDiv = document.getElementById('eligibilityDisplay');
    if (scholarshipPercentage > 0) {
        eligibilityDiv.innerHTML = '<span style="color:var(--success);">Eligible for ' + label + '</span>';
    } else {
        eligibilityDiv.innerHTML = '<span style="color:var(--danger);">Not eligible (Need 60% minimum)</span>';
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
