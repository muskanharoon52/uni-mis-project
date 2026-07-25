<?php
// scholarship/calculate.php - GPA Based Scholarship (COMPLETE)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$message = '';
$message_type = '';

// Get all students
$students_query = "SELECT s.student_id, u.full_name, s.gpa, s.scholarship_percentage 
                   FROM students s
                   LEFT JOIN users u ON s.user_id = u.user_id
                   ORDER BY u.full_name";
$students_result = mysqli_query($conn, $students_query);
$all_students = [];
if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $all_students[] = $row;
    }
}

// Get scholarship rules
$rules_query = "SELECT * FROM scholarship_rules WHERE status = 'Active' ORDER BY min_gpa";
$rules_result = mysqli_query($conn, $rules_query);
$rules = [];
if ($rules_result) {
    while ($row = mysqli_fetch_assoc($rules_result)) {
        $rules[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $gpa = floatval($_POST['gpa']);
    $semester_id = (int)$_POST['semester_id'];
    $user_id = $_SESSION['user_id'];
    
    // Calculate scholarship based on GPA
    $scholarship_percentage = 0;
    foreach ($rules as $rule) {
        if ($gpa >= $rule['min_gpa'] && $gpa <= $rule['max_gpa']) {
            $scholarship_percentage = $rule['scholarship_percentage'];
            break;
        }
    }
    
    // Update student GPA and scholarship
    $update_query = "UPDATE students SET gpa = $gpa, scholarship_percentage = $scholarship_percentage 
                     WHERE student_id = '$student_id'";
    if (mysqli_query($conn, $update_query)) {
        // Insert into scholarships table
        $insert_query = "INSERT INTO scholarships (student_id, scholarship_type, awarding_body, semester_id, discount_kind, discount_value, approved_by, remarks, status) 
                         VALUES ('$student_id', 'Merit', 'GPA Based', $semester_id, 'Percentage', $scholarship_percentage, $user_id, 'Auto-calculated from GPA: $gpa', 'Active')";
        mysqli_query($conn, $insert_query);
        
        $message = "✅ Scholarship calculated successfully!<br>
                    <strong>Student:</strong> $student_id<br>
                    <strong>GPA:</strong> $gpa<br>
                    <strong>Scholarship:</strong> $scholarship_percentage%";
        $message_type = 'success';
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .scholarship-content { margin-left: 250px; padding: 20px; min-height: 100vh; background: #f5f6fa; }
    .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0; font-weight: 600; }
    .btn-save { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; color: white; padding: 10px 30px; border-radius: 10px; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4); color: white; }
    .btn-secondary { background: #6c757d; border: none; color: white; padding: 10px 30px; border-radius: 10px; }
    .btn-secondary:hover { background: #5a6268; color: white; }
    .rule-box { background: #f8f9fa; padding: 15px 20px; border-radius: 10px; text-align: center; border-left: 4px solid #28a745; }
    .rule-box h3 { font-weight: 700; color: #28a745; }
    .student-list { max-height: 300px; overflow-y: auto; }
    .student-list .list-item { padding: 8px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
    .student-list .list-item .gpa-badge { padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .gpa-good { background: #d4edda; color: #155724; }
    .gpa-medium { background: #fff3cd; color: #856404; }
    .gpa-low { background: #f8d7da; color: #721c24; }
    .gpa-none { background: #e2e3e5; color: #383d41; }
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
    @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .scholarship-content { margin-left: 0; } }
</style>

<div class="scholarship-content">
    <div class="container-fluid">
        <div class="topbar">
            <div><h5 class="mb-0"><i class="fas fa-calculator text-success"></i> GPA Based Scholarship</h5></div>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Scholarship Rules -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-graduation-cap me-2"></i> Scholarship Rules</div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($rules as $rule): ?>
                    <div class="col-md-4">
                        <div class="rule-box">
                            <h5>GPA: <?php echo $rule['min_gpa']; ?> - <?php echo $rule['max_gpa']; ?></h5>
                            <h3><?php echo $rule['scholarship_percentage']; ?>%</h3>
                            <small class="text-muted">Scholarship</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Calculate Scholarship Form -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user-graduate me-2"></i> Calculate Student Scholarship</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Select Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="fw-semibold">GPA</label>
                            <input type="number" name="gpa" id="gpa_input" class="form-control" placeholder="e.g., 3.50" min="0" max="4" step="0.01" required>
                            <small class="text-muted">Enter GPA between 0.00 and 4.00</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Scholarship</label>
                            <input type="text" id="scholarship_display" class="form-control" readonly placeholder="Will auto-calculate" style="font-size:18px; font-weight:700;">
                        </div>
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i> Calculate & Assign</button>
                          <a href="<?php echo BASE_URL; ?>fee_management/index.php" class="btn btn-secondary">
    <i class="fas fa-times me-2"></i> Cancel
</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- All Students with GPA -->
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i> All Students with GPA</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>GPA</th>
                                <th>Scholarship</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($all_students as $student): 
                                $gpa = $student['gpa'] ?? 0;
                                $sch_percent = $student['scholarship_percentage'] ?? 0;
                                
                                // Determine GPA status
                                if ($gpa >= 3.76) $status = 'gpa-good';
                                elseif ($gpa >= 3.33) $status = 'gpa-medium';
                                elseif ($gpa > 0) $status = 'gpa-low';
                                else $status = 'gpa-none';
                            ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="gpa-badge <?php echo $status; ?>">
                                        <?php echo $gpa > 0 ? number_format($gpa, 2) : 'Not Set'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($sch_percent > 0): ?>
                                        <span class="badge bg-success"><?php echo $sch_percent; ?>%</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate scholarship based on GPA
document.addEventListener('DOMContentLoaded', function() {
    var gpaInput = document.getElementById('gpa_input');
    var display = document.getElementById('scholarship_display');
    var rules = <?php echo json_encode($rules); ?>;
    
    gpaInput.addEventListener('input', function() {
        var gpa = parseFloat(this.value);
        
        if (isNaN(gpa)) {
            display.value = 'Enter GPA';
            display.style.color = '#6c757d';
            return;
        }
        
        var percentage = 0;
        
        for (var i = 0; i < rules.length; i++) {
            if (gpa >= rules[i].min_gpa && gpa <= rules[i].max_gpa) {
                percentage = rules[i].scholarship_percentage;
                break;
            }
        }
        
        if (percentage > 0) {
            display.value = percentage + '% Scholarship';
            display.style.color = '#28a745';
            display.style.fontWeight = '700';
        } else if (gpa > 0 && gpa < 3.33) {
            display.value = 'No Scholarship (Below 3.33 GPA)';
            display.style.color = '#dc3545';
            display.style.fontWeight = '600';
        } else {
            display.value = 'Enter valid GPA';
            display.style.color = '#6c757d';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>